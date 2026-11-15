<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DriverDayController;
use App\Http\Controllers\MolliePaymentController;
use App\Http\Controllers\PayLaterBookingController;
use App\Http\Controllers\PlaceSearchController;
use App\Http\Controllers\RouteEstimateController;
use App\Http\Controllers\RideLocationController;
use App\Models\Booking;
use App\Models\FareSetting;
use App\Models\BookingNotification;
use App\Services\BookingPriceCalculator;
use App\Models\DriverBreakRequest;
use App\Services\BookingNotificationDispatcher;
use App\Services\DispatchMetrics;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/contact', function () {
    return view('contact');
})->name('contact');

Route::get('/sitemap.xml', function () {
    return response()->view('sitemap', [
        'pages' => [url('/'), url('/?lang=en'), route('contact'), route('contact', ['lang' => 'en'])],
    ])->header('Content-Type', 'application/xml; charset=UTF-8');
})->name('sitemap');

Route::post('/payments/mollie', [MolliePaymentController::class, 'store'])->middleware('throttle:12,1')->name('payments.mollie.store');
Route::get('/payments/mollie/return', [MolliePaymentController::class, 'return'])->name('payments.mollie.return');
Route::post('/payments/mollie/webhook', [MolliePaymentController::class, 'webhook'])->name('payments.mollie.webhook');

Route::post('/bookings/pay-later', [PayLaterBookingController::class, 'store'])->middleware('throttle:12,1')->name('bookings.pay-later.store');
Route::post('/route-estimate', [RouteEstimateController::class, 'store'])->middleware('throttle:30,1')->name('route-estimate.store');
Route::post('/places/autocomplete', [PlaceSearchController::class, 'autocomplete'])->name('places.autocomplete');
Route::post('/places/details', [PlaceSearchController::class, 'details'])->name('places.details');

Route::get('/track/{token}', function (string $token) {
    $booking = Booking::where('tracking_token', $token)->firstOrFail();

    return response()->view('bookings.track', [
        'booking' => $booking,
    ])->header('Cache-Control', 'private, no-store')->header('Referrer-Policy', 'no-referrer');
})->name('bookings.track');

Route::get('/track/{token}/state', function (string $token): JsonResponse {
    $booking = Booking::where('tracking_token', $token)->firstOrFail();

    return response()->json([
        'reference' => $booking->reference,
        'booking_status' => $booking->booking_status,
        'payment_status' => $booking->payment_status,
        'eta_min' => $booking->booking_status === 'driver_en_route'
            ? $booking->driver_eta_min
            : null,
        'driver' => in_array($booking->booking_status, ['driver_assigned', ...Booking::ACTIVE_STATUSES, 'completed'], true) ? [
            'name' => $booking->driver_name ?: DispatchMetrics::DRIVER_NAME,
            'phone' => $booking->driver_phone ?: DispatchMetrics::DRIVER_PHONE,
            'vehicle' => $booking->driver_vehicle ?: DispatchMetrics::DRIVER_VEHICLE,
        ] : null,
        'driver_location' => $booking->sharedDriverLocation(),
    ])->header('Cache-Control', 'private, no-store')->header('Referrer-Policy', 'no-referrer');
})->name('bookings.track.state');

Route::middleware(['auth', 'verified', 'can:manage-rides'])->group(function () {
    Route::get('/admin', function (DispatchMetrics $metrics) {
        return view('admin.dashboard', [
            'stats' => $metrics->overview(),
            'bookings' => $metrics->recentBookings(10),
            'activeBooking' => $metrics->activeBooking(),
            'minimumFare' => (FareSetting::find(1)?->minimum_cents ?? BookingPriceCalculator::MINIMUM_CENTS) / 100,
        ]);
    })->name('admin.dashboard');

    Route::get('/admin/live', function (DispatchMetrics $metrics) {
        return view('admin.live', [
            'stats' => $metrics->overview(),
            'booking' => $metrics->activeBooking(),
        ]);
    })->name('admin.live');

    Route::get('/admin/live/state', function (DispatchMetrics $metrics): JsonResponse {
        $booking = $metrics->activeBooking();

        return response()->json([
            'driver_online' => $metrics->activeShift() !== null,
            'active_booking' => $booking ? [
                'id' => $booking->id,
                'reference' => $booking->reference,
                'customer' => $booking->customer_name,
                'pickup' => $booking->pickup,
                'destination' => $booking->destination,
                'status' => $booking->booking_status,
                'price' => $booking->calculatedFare(),
                'eta_min' => $booking->driver_eta_min,
                'driver_location' => $booking->sharedDriverLocation(),
                'pickup_latitude' => $booking->pickup_latitude,
                'pickup_longitude' => $booking->pickup_longitude,
                'destination_latitude' => $booking->destination_latitude,
                'destination_longitude' => $booking->destination_longitude,
            ] : null,
        ]);
    })->name('admin.live.state');

    Route::get('/admin/bookings', function () {
        $queryParams = request()->only(['search', 'filter']);
        $search = is_string($queryParams['search'] ?? null) ? trim($queryParams['search']) : '';
        $filter = in_array($queryParams['filter'] ?? '', ['today', 'open', 'payment'], true) ? $queryParams['filter'] : '';
        $filters = array_filter(['search' => $search, 'filter' => $filter], fn ($value) => $value !== '');

        $bookings = Booking::query()
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $matches) use ($search) {
                    $matches->where('reference', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%")
                        ->orWhere('pickup', 'like', "%{$search}%")
                        ->orWhere('destination', 'like', "%{$search}%");
                    if (ctype_digit($search)) {
                        $matches->orWhereKey($search);
                    }
                });
            })
            ->when($filter === 'today', fn (Builder $query) => $query->whereDate('ride_date', now('Europe/Amsterdam')->toDateString()))
            ->when($filter === 'open', fn (Builder $query) => $query->whereIn('booking_status', Booking::OPEN_STATUSES))
            ->when($filter === 'payment', fn (Builder $query) => $query->whereIn('payment_status', ['pending', 'pay_later']))
            ->latest()
            ->paginate(20)
            ->appends($filters);

        if ($bookings->currentPage() > 1) {
            $filters['page'] = $bookings->currentPage();
        }

        return view('admin.bookings.index', [
            'bookings' => $bookings,
            'search' => $search,
            'filter' => $filter,
            'returnFilters' => $filters,
        ]);
    })->name('admin.bookings.index');

    Route::get('/admin/bookings/{booking}', function (Booking $booking) {
        return view('admin.bookings.show', [
            'booking' => $booking->load('notifications'),
            'returnFilters' => request()->only(['search', 'filter', 'page']),
        ]);
    })->name('admin.bookings.show');

    Route::get('/driver', [DriverDayController::class, 'show'])->name('driver.dashboard');
    Route::post('/dashboard/driver/clock', [DriverDayController::class, 'clock'])->name('dashboard.driver.clock');
    Route::put('/dashboard/bookings/{booking}/location', [RideLocationController::class, 'update'])
        ->middleware('throttle:12,1')->name('dashboard.bookings.location.update');
    Route::delete('/dashboard/bookings/{booking}/location', [RideLocationController::class, 'destroy'])
        ->name('dashboard.bookings.location.destroy');

    Route::patch('/admin/tarieven', function () {
        $settings = request()->validate(['minimum' => ['required', 'numeric', 'decimal:0,2', 'between:0,100']]);
        FareSetting::updateOrCreate(['id' => 1], ['minimum_cents' => (int) round($settings['minimum'] * 100)]);

        return back()->with('status', 'Minimumritprijs opgeslagen voor nieuwe ritten.');
    })->name('admin.fares.update');

    Route::post('/admin/boekingen/{booking}/mails/{notification}/opnieuw', function (Booking $booking, BookingNotification $notification, BookingNotificationDispatcher $notifications) {
        abort_unless($notification->booking_id === $booking->id && $notification->status === 'failed', 404);
        if (! $notifications->dispatch($booking, $notification->event)) {
            return back()->withErrors(['dispatch' => 'De mail kon niet worden ingepland. Probeer het later opnieuw.']);
        }

        return back()->with('status', 'De mail staat opnieuw in de wachtrij.');
    })->middleware('throttle:10,1')->name('admin.bookings.notifications.retry');
});

Route::get('/receipt/{token}', function (string $token) {
    $booking = Booking::where('tracking_token', $token)->firstOrFail();

    return response()->view('bookings.receipt', [
        'booking' => $booking,
    ])->header('Cache-Control', 'private, no-store')->header('Referrer-Policy', 'no-referrer');
})->name('bookings.receipt');

Route::redirect('/dashboard', '/admin')->middleware(['auth', 'verified'])->name('dashboard');

Route::post('/dashboard/driver/breaks', function () {
    $validated = request()->validate([
        'break_date' => ['required', 'date'],
        'starts_at' => ['required', 'date_format:H:i'],
        'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'],
        'reason' => ['nullable', 'string', 'max:255'],
    ]);

    DriverBreakRequest::create([
        'driver_name' => 'Omar Hussein',
        'break_date' => $validated['break_date'],
        'starts_at' => $validated['starts_at'],
        'ends_at' => $validated['ends_at'],
        'reason' => $validated['reason'] ?? null,
        'status' => 'requested',
    ]);

    return back()->with('status', 'Pauze-aanvraag van Omar Hussein is ingediend.');
})->middleware(['auth', 'verified', 'can:manage-rides'])->name('dashboard.driver.breaks.store');

Route::patch('/dashboard/bookings/{booking}/review', function (Booking $booking, BookingNotificationDispatcher $notifications) {
    $validated = request()->validate([
        'admin_status' => ['required', 'in:accepted,rejected'],
    ]);

    $changed = Booking::whereKey($booking->id)->where('booking_status', 'new')->update([
        'admin_status' => $validated['admin_status'],
        'booking_status' => $validated['admin_status'] === 'accepted' ? 'driver_assigned' : 'rejected',
        'driver_name' => 'Omar Hussein',
        'driver_phone' => '+31 6 86463268',
        'driver_vehicle' => 'Husso Taxi',
        'driver_eta_min' => null,
        'reviewed_at' => now(),
        'confirmed_at' => $validated['admin_status'] === 'accepted' ? now() : null,
    ]);

    if (! $changed) {
        return back()->withErrors(['dispatch' => 'Deze rit is al beoordeeld. Controleer de huidige ritstatus.']);
    }

    $notifications->dispatch($booking->refresh(), $validated['admin_status'] === 'accepted' ? 'booking_accepted' : 'booking_declined');

    return back()->with('status', $validated['admin_status'] === 'accepted'
        ? 'Boeking geaccepteerd en toegewezen aan Omar Hussein.'
        : 'Boeking geweigerd.');
})->middleware(['auth', 'verified', 'can:manage-rides'])->name('dashboard.bookings.review');

Route::patch('/dashboard/bookings/{booking}/dispatch', function (Booking $booking, BookingNotificationDispatcher $notifications) {
    $validated = request()->validate([
        'booking_status' => ['required', 'in:driver_assigned,driver_en_route,arrived,picked_up,completed,canceled'],
        'reason' => ['required_if:booking_status,canceled', 'nullable', 'string', 'max:500'],
    ]);

    $nextStatus = $validated['booking_status'];
    $canceling = $nextStatus === 'canceled';

    if ($canceling ? ! $booking->canCancelDispatch() : $booking->nextDispatchStatus() !== $nextStatus) {
        return back()->withErrors(['dispatch' => 'Deze actie past niet bij de huidige ritstatus. Controleer de rit en probeer opnieuw.']);
    }

    $updates = [
        'booking_status' => $nextStatus,
        'driver_eta_min' => null,
    ];

    if (in_array($nextStatus, ['completed', 'canceled'], true)) {
        $updates['driver_latitude'] = null;
        $updates['driver_longitude'] = null;
        $updates['driver_location_shared_at'] = null;
    }

    if ($canceling) {
        $updates['dispatch_notes'] = trim($booking->dispatch_notes."\nAnnulering: ".$validated['reason']);
    } else {
        $updates += [
            'admin_status' => 'accepted',
            'driver_name' => $booking->driver_name ?: DispatchMetrics::DRIVER_NAME,
            'driver_phone' => $booking->driver_phone ?: DispatchMetrics::DRIVER_PHONE,
            'driver_vehicle' => $booking->driver_vehicle ?: DispatchMetrics::DRIVER_VEHICLE,
        ];
        if ($nextStatus === 'driver_assigned') {
            $updates['reviewed_at'] = now();
            $updates['confirmed_at'] = now();
        }
    }

    $changed = Booking::whereKey($booking->id)->where('booking_status', $booking->booking_status)->update($updates);
    if (! $changed) {
        return back()->withErrors(['dispatch' => 'De ritstatus is zojuist gewijzigd. Controleer de rit en probeer opnieuw.']);
    }

    $notifications->dispatch($booking->refresh(), match ($nextStatus) {
        'driver_assigned' => 'booking_accepted',
        'driver_en_route' => 'driver_arriving',
        'arrived' => 'driver_arrived',
        'picked_up' => 'trip_started',
        'completed' => 'trip_completed',
        'canceled' => 'trip_canceled',
    });

    return back()->with('status', $canceling ? 'Rit geannuleerd.' : 'Ritstatus bijgewerkt.');
})->middleware(['auth', 'verified', 'can:manage-rides'])->name('dashboard.bookings.dispatch');

Route::patch('/dashboard/bookings/{booking}/payment', function (Booking $booking, BookingNotificationDispatcher $notifications) {
    $validated = request()->validate([
        'payment_status' => ['required', 'in:paid,pending,pay_later,failed,refunded'],
    ]);

    $booking->update([
        'payment_status' => $validated['payment_status'],
        'payment_collected_at' => $validated['payment_status'] === 'paid' ? ($booking->payment_collected_at ?? now()) : null,
    ]);

    if ($validated['payment_status'] === 'failed') {
        $notifications->dispatch($booking->refresh(), 'payment_failed');
    }

    return back()->with('status', 'Betaalstatus bijgewerkt.');
})->middleware(['auth', 'verified', 'can:manage-rides'])->name('dashboard.bookings.payment');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
