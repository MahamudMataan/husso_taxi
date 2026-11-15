<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Services\BookingCreator;
use App\Services\BookingNotificationDispatcher;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use RuntimeException;

class MolliePaymentController extends Controller
{
    private const PAYMENTS_URL = 'https://api.mollie.com/v2/payments';

    public function store(Request $request, BookingCreator $bookingCreator, BookingNotificationDispatcher $notifications): RedirectResponse
    {
        $validated = $request->validate([
            'language'              => ['nullable', 'in:nl,en'],
            'customer_name'         => ['required', 'string', 'max:120'],
            'customer_email'        => ['required', 'email', 'max:255'],
            'phone'                 => ['required', 'string', 'max:40'],
            'pickup'                => ['required', 'string', 'max:255'],
            'pickup_latitude'       => ['required', 'numeric', 'between:-90,90'],
            'pickup_longitude'      => ['required', 'numeric', 'between:-180,180'],
            'destination'           => ['required', 'string', 'max:255'],
            'destination_latitude'  => ['required', 'numeric', 'between:-90,90'],
            'destination_longitude' => ['required', 'numeric', 'between:-180,180'],
            'ride_date'             => ['required', 'date'],
            'ride_time'             => ['required', 'date_format:H:i'],
            'passengers'            => ['required', 'string', 'max:10'],
            'ride_type'             => ['required', 'string', 'max:40'],
        ]);

        $apiKey = config('services.mollie.key');
        $language = $validated['language'] ?? 'nl';

        if (! $apiKey) {
            return back()->withInput($validated)->with('payment_error', $language === 'en'
                ? 'Online payment is unavailable. You can choose to pay later.'
                : 'Online betalen is tijdelijk niet beschikbaar. Je kunt kiezen voor later betalen.');
        }

        $description = $language === 'en' ? 'Husso Taxi booking' : 'Husso Taxi boeking';

        try {
            $booking = $bookingCreator->create($validated, 'ideal', 'pending');
        } catch (RuntimeException) {
            return back()->withInput($validated)->with('payment_error', $language === 'en'
                ? 'The route could not be verified safely. Please choose pickup and destination again.'
                : 'De route kon niet veilig gecontroleerd worden. Kies ophaal- en bestemmingsplaats opnieuw.');
        }

        $testAmount = str_starts_with($apiKey, 'test_') ? config('services.mollie.test_amount') : null;
        $paymentAmount = (float) ($testAmount ?: $booking->quoted_amount);
        $paymentAmount = max($paymentAmount, 0.01);

        $payment = [
            'amount'      => [
                'currency' => 'EUR',
                'value'    => number_format($paymentAmount, 2, '.', ''),
            ],
            'description' => $description,
            'method'      => 'ideal',
            'redirectUrl' => URL::signedRoute('payments.mollie.return', [
                'lang' => $language,
                'booking' => $booking->reference,
            ]),
            'webhookUrl'  => route('payments.mollie.webhook'),
            'metadata'    => [
                'booking_id' => $booking->id,
                'reference' => $booking->reference,
                'language'              => $language,
                'customer_name'         => $validated['customer_name'],
                'customer_email'        => $validated['customer_email'],
                'phone'                 => $validated['phone'],
                'pickup'                => $validated['pickup'],
                'pickup_latitude'       => $validated['pickup_latitude'],
                'pickup_longitude'      => $validated['pickup_longitude'],
                'destination'           => $validated['destination'],
                'destination_latitude'  => $validated['destination_latitude'],
                'destination_longitude' => $validated['destination_longitude'],
                'distance_km'           => $booking->distance_km,
                'duration_min'          => $booking->duration_min,
                'ride_date'             => $validated['ride_date'],
                'ride_time'             => $validated['ride_time'],
                'passengers'            => $validated['passengers'],
                'ride_type'             => $validated['ride_type'],
            ],
        ];

        try {
            $response = $this->mollieRequest($apiKey)->post(self::PAYMENTS_URL, $payment);
        } catch (ConnectionException) {
            Log::warning('Mollie payment creation could not connect.');

            return $this->checkoutFailure($booking, 'checkout_failed', $notifications);
        }

        if (! $response->successful()) {
            Log::warning('Mollie payment creation failed.', [
                'status' => $response->status(),
            ]);

            return $this->checkoutFailure($booking, 'checkout_failed', $notifications);
        }

        $checkoutUrl = $response->json('_links.checkout.href');
        $mollieId = $response->json('id');

        if (! is_string($checkoutUrl) || trim($checkoutUrl) === '' || ! is_string($mollieId) || trim($mollieId) === '') {
            return $this->checkoutFailure($booking, 'checkout_missing', $notifications);
        }

        $booking->update(['mollie_payment_id' => $mollieId]);
        $request->session()->put('mollie_checkout.'.$booking->reference, $booking->id);

        return redirect()->away($checkoutUrl);
    }

    public function webhook(Request $request, BookingNotificationDispatcher $notifications): Response
    {
        $mollieId = $request->input('id');

        if (! is_string($mollieId) || $mollieId === '') {
            return response('', 200);
        }

        $booking = Booking::where('mollie_payment_id', $mollieId)->first();
        if ($booking) {
            $this->refreshPaymentStatus($booking, $notifications);
        }

        return response('', 200);
    }

    public function return(Request $request, BookingNotificationDispatcher $notifications): RedirectResponse
    {
        $booking = $this->checkoutBooking($request);

        if (! $booking) {
            $isEnglish = $request->query('lang') === 'en';

            return redirect(url($isEnglish ? '/?lang=en#booking' : '/#booking'))
                ->with('payment_error', $isEnglish
                    ? 'We could not find your checkout. Please contact us if you already booked a ride.'
                    : 'We konden je betaling niet terugvinden. Neem contact met ons op als je al een rit hebt geboekt.');
        }

        $this->refreshPaymentStatus($booking, $notifications);

        return $this->redirectToTracking($booking);
    }

    private function checkoutBooking(Request $request): ?Booking
    {
        $reference = $request->query('booking');

        if (! is_string($reference) || $reference === '') {
            return null;
        }

        $booking = Booking::where('reference', $reference);
        if (URL::hasValidSignature($request)) {
            return $booking->first();
        }

        $checkouts = $request->hasSession() ? $request->session()->get('mollie_checkout', []) : [];
        $bookingId = is_array($checkouts) ? ($checkouts[$reference] ?? null) : null;

        return is_int($bookingId) || is_string($bookingId)
            ? $booking->whereKey($bookingId)->first()
            : null;
    }

    private function refreshPaymentStatus(Booking $booking, BookingNotificationDispatcher $notifications): void
    {
        $mollieId = $booking->mollie_payment_id;
        $apiKey = config('services.mollie.key');

        if (! $mollieId || ! $apiKey) {
            return;
        }

        try {
            $response = $this->mollieRequest($apiKey)->get(self::PAYMENTS_URL.'/'.rawurlencode($mollieId));
        } catch (ConnectionException) {
            Log::warning('Mollie payment status request could not connect.', ['booking_id' => $booking->id]);

            return;
        }

        if (! $response->successful() || $response->json('id', $mollieId) !== $mollieId) {
            Log::warning('Mollie payment status could not be verified.', [
                'booking_id' => $booking->id,
                'status' => $response->status(),
            ]);

            return;
        }

        $status = $response->json('status');
        if (in_array($status, ['paid', 'expired', 'canceled', 'failed'], true)) {
            $this->updatePaymentStatus($booking, $status, $notifications);
        }
    }

    private function checkoutFailure(Booking $booking, string $status, BookingNotificationDispatcher $notifications): RedirectResponse
    {
        $this->updatePaymentStatus($booking, $status, $notifications);

        return $this->redirectToTracking($booking);
    }

    private function updatePaymentStatus(Booking $booking, string $status, BookingNotificationDispatcher $notifications): void
    {
        $attributes = ['payment_status' => $status];
        $event = 'payment_failed';
        if ($status === 'paid') {
            $attributes['payment_collected_at'] = now();
            $event = 'booking_accepted';
        }

        $booking->getConnection()->transaction(function () use ($booking, $status, $attributes, $event, $notifications) {
            $updated = $booking->newQuery()->whereKey($booking->getKey())
                ->where('mollie_payment_id', $booking->mollie_payment_id)
                ->whereNotIn('payment_status', ['paid', 'refunded', $status])
                ->update($attributes);

            if ($updated) {
                $notifications->dispatch($booking, $event);
            }
        });
    }

    private function redirectToTracking(Booking $booking): RedirectResponse
    {
        return redirect()->to($booking->trackingUrl(), 302, [
            'Cache-Control' => 'private, no-store',
            'Referrer-Policy' => 'no-referrer',
        ]);
    }

    private function mollieRequest(string $apiKey): PendingRequest
    {
        return Http::withToken($apiKey)
            ->acceptJson()
            ->connectTimeout(2)
            ->timeout(5)
            ->withoutRedirecting();
    }
}
