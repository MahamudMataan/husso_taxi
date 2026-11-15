<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MolliePaymentController;
use App\Http\Controllers\PayLaterBookingController;
use App\Http\Controllers\PlaceSearchController;
use App\Http\Controllers\RouteEstimateController;
use App\Models\Booking;
use App\Models\DriverBreakRequest;
use App\Models\DriverTimeLog;
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

Route::post('/payments/mollie', [MolliePaymentController::class, 'store'])->name('payments.mollie.store');
Route::get('/payments/mollie/return', [MolliePaymentController::class, 'return'])->name('payments.mollie.return');
Route::post('/payments/mollie/webhook', [MolliePaymentController::class, 'webhook'])->name('payments.mollie.webhook');

Route::post('/bookings/pay-later', [PayLaterBookingController::class, 'store'])->name('bookings.pay-later.store');
Route::get('/bookings/availability', function (DispatchMetrics $metrics): JsonResponse {
    return response()->json(['driver_online' => $metrics->activeShift() !== null])
        ->header('Cache-Control', 'private, no-store');
})->middleware('throttle:60,1')->name('bookings.availability');

Route::post('/route-estimate', [RouteEstimateController::class, 'store'])->name('route-estimate.store');
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
        'eta_min' => in_array($booking->booking_status, ['driver_en_route', 'arrived'], true)
            ? $booking->driver_eta_min
            : null,
        'driver' => [
            'name' => $booking->driver_name ?: DispatchMetrics::DRIVER_NAME,
            'phone' => $booking->driver_phone ?: DispatchMetrics::DRIVER_PHONE,
            'vehicle' => $booking->driver_vehicle ?: DispatchMetrics::DRIVER_VEHICLE,
            'plate' => DispatchMetrics::DRIVER_PLATE,
            'latitude' => $booking->driver_latitude ?: $booking->pickup_latitude,
            'longitude' => $booking->driver_longitude ?: $booking->pickup_longitude,
        ],
    ])->header('Cache-Control', 'private, no-store')->header('Referrer-Policy', 'no-referrer');
})->name('bookings.track.state');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/admin', function (DispatchMetrics $metrics) {
        return view('admin.dashboard', [
            'stats' => $metrics->overview(),
            'bookings' => $metrics->recentBookings(10),
            'activeBooking' => $metrics->activeBooking(),
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
                'eta_min' => $booking->driver_eta_min ?: $booking->duration_min,
                'driver_latitude' => $booking->driver_latitude ?: $booking->pickup_latitude,
                'driver_longitude' => $booking->driver_longitude ?: $booking->pickup_longitude,
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

    Route::get('/driver', function (DispatchMetrics $metrics) {
        $today = now()->toDateString();

        return view('driver.dashboard', [
            'driverName' => DispatchMetrics::DRIVER_NAME,
            'activeShift' => $metrics->activeShift(),
            'ridesToday' => Booking::whereDate('ride_date', $today)
                ->whereIn('booking_status', ['completed', 'picked_up', 'driver_en_route', 'arrived'])
                ->count(),
            'revenueToday' => Booking::whereDate('ride_date', $today)
                ->whereIn('payment_status', ['paid', 'pay_later'])
                ->sum('quoted_amount'),
            'activeBooking' => $metrics->activeBooking(),
        ]);
    })->name('driver.dashboard');
});

Route::get('/receipt/{token}', function (string $token) {
    $booking = Booking::where('tracking_token', $token)->firstOrFail();

    return response()->view('bookings.receipt', [
        'booking' => $booking,
    ])->header('Cache-Control', 'private, no-store')->header('Referrer-Policy', 'no-referrer');
})->name('bookings.receipt');

Route::redirect('/dashboard', '/admin')->middleware(['auth', 'verified'])->name('dashboard');

Route::post('/dashboard/driver/clock', function () {
    $driverName = 'Omar Hussein';
    $validated = request()->validate([
        'action' => ['required', 'in:in,out'],
    ]);

    $activeShift = DriverTimeLog::where('driver_name', $driverName)
        ->whereNull('clocked_out_at')
        ->latest('clocked_in_at')
        ->first();

    if ($validated['action'] === 'in') {
        if ($activeShift) {
            return back()->with('status', 'Omar Hussein is al ingeklokt.');
        }

        DriverTimeLog::create([
            'driver_name' => $driverName,
            'clocked_in_at' => now(),
        ]);

        return back()->with('status', 'Omar Hussein is ingeklokt.');
    }

    if (! $activeShift) {
        return back()->with('status', 'Omar Hussein is nog niet ingeklokt.');
    }

    $activeShift->update(['clocked_out_at' => now()]);

    return back()->with('status', 'Omar Hussein is uitgeklokt.');
})->middleware(['auth', 'verified'])->name('dashboard.driver.clock');

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
})->middleware(['auth', 'verified'])->name('dashboard.driver.breaks.store');

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
        'driver_eta_min' => 8,
        'reviewed_at' => now(),
    ]);

    if (! $changed) {
        return back()->withErrors(['dispatch' => 'Deze rit is al beoordeeld. Controleer de huidige ritstatus.']);
    }

    if ($validated['admin_status'] === 'accepted') {
        $notifications->dispatch($booking->refresh(), 'driver_assigned');
    }

    return back()->with('status', $validated['admin_status'] === 'accepted'
        ? 'Boeking geaccepteerd en toegewezen aan Omar Hussein.'
        : 'Boeking geweigerd.');
})->middleware(['auth', 'verified'])->name('dashboard.bookings.review');

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
        'driver_eta_min' => match ($nextStatus) {
            'driver_en_route' => 6,
            'arrived' => 1,
            'picked_up', 'completed', 'canceled' => null,
            default => $booking->driver_eta_min ?: 8,
        },
    ];

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
        }
    }

    $changed = Booking::whereKey($booking->id)->where('booking_status', $booking->booking_status)->update($updates);
    if (! $changed) {
        return back()->withErrors(['dispatch' => 'De ritstatus is zojuist gewijzigd. Controleer de rit en probeer opnieuw.']);
    }

    if (! $canceling) {
        $notifications->dispatch($booking->refresh(), match ($nextStatus) {
            'driver_assigned' => 'driver_assigned',
            'driver_en_route', 'arrived' => 'driver_arriving',
            'picked_up' => 'trip_started',
            'completed' => 'trip_completed',
        });
    }

    return back()->with('status', $canceling ? 'Rit geannuleerd.' : 'Ritstatus bijgewerkt.');
})->middleware(['auth', 'verified'])->name('dashboard.bookings.dispatch');

Route::patch('/dashboard/bookings/{booking}/payment', function (Booking $booking, BookingNotificationDispatcher $notifications) {
    $validated = request()->validate([
        'payment_status' => ['required', 'in:paid,pending,pay_later,failed,refunded'],
    ]);

    $booking->update([
        'payment_status' => $validated['payment_status'],
        'payment_collected_at' => $validated['payment_status'] === 'paid' ? now() : null,
    ]);

    if ($validated['payment_status'] === 'failed') {
        $notifications->dispatch($booking->refresh(), 'payment_failed');
    }

    return back()->with('status', 'Betaalstatus bijgewerkt.');
})->middleware(['auth', 'verified'])->name('dashboard.bookings.payment');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
