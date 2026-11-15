<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRideRequest;
use App\Models\Booking;
use App\Services\BookingCreator;
use App\Services\BookingNotificationDispatcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class MolliePaymentController extends Controller
{
    public function store(StoreRideRequest $request, BookingCreator $bookingCreator, BookingNotificationDispatcher $notifications): RedirectResponse
    {
        $validated = $request->bookingData();

        $apiKey = config('services.mollie.key');
        $language = $validated['language'] ?? 'nl';

        if (! $apiKey) {
            return back()->withInput($request->validated())->with('payment_error', $language === 'en'
                ? 'Online payment is unavailable. You can choose to pay later.'
                : 'Online betalen is tijdelijk niet beschikbaar. Je kunt kiezen voor later betalen.');
        }

        $description = $language === 'en' ? 'Husso Taxi booking' : 'Husso Taxi boeking';

        try {
            $booking = $bookingCreator->create($validated, 'ideal', 'pending');
        } catch (RuntimeException) {
            return back()->withInput($request->validated())->with('payment_error', $language === 'en'
                ? 'The route could not be verified safely. Please choose pickup and destination again.'
                : 'De route kon niet veilig gecontroleerd worden. Kies ophaal- en bestemmingsplaats opnieuw.');
        }

        $sessionKey = 'mollie_checkout_url.'.$booking->reference;
        if ($booking->mollie_payment_id) {
            $existingCheckout = $request->session()->get($sessionKey);

            return $existingCheckout && $booking->payment_status === 'pending'
                ? redirect()->away($existingCheckout)
                : redirect($booking->trackingUrl());
        }

        $testAmount = str_starts_with($apiKey, 'test_') ? config('services.mollie.test_amount') : null;
        $paymentAmount = (float) ($testAmount ?: $booking->quoted_amount);
        $paymentAmount = max($paymentAmount, 0.01);

        $response = Http::withToken($apiKey)
            ->withHeaders(['Idempotency-Key' => 'husso-'.$booking->reference])
            ->acceptJson()
            ->post('https://api.mollie.com/v2/payments', [
                'amount'      => [
                    'currency' => 'EUR',
                    'value'    => number_format($paymentAmount, 2, '.', ''),
                ],
                'description' => $description,
                'method'      => 'ideal',
                'redirectUrl' => route('payments.mollie.return', ['lang' => $language, 'booking' => $booking->reference]),
                'webhookUrl'  => route('payments.mollie.webhook'),
                'metadata'    => [
                    'booking_id' => $booking->id,
                    'reference' => $booking->reference,
                    'language'              => $language,
                    'customer_name'         => $booking->customer_name,
                    'customer_email'        => $booking->customer_email,
                    'phone'                 => $booking->phone,
                    'pickup'                => $booking->pickup,
                    'pickup_latitude'       => $booking->pickup_latitude,
                    'pickup_longitude'      => $booking->pickup_longitude,
                    'destination'           => $booking->destination,
                    'destination_latitude'  => $booking->destination_latitude,
                    'destination_longitude' => $booking->destination_longitude,
                    'distance_km'           => $booking->distance_km,
                    'duration_min'          => $booking->duration_min,
                    'ride_date'             => $booking->ride_date->format('Y-m-d'),
                    'ride_time'             => substr($booking->ride_time, 0, 5),
                    'passengers'            => $booking->passengers,
                    'ride_type'             => $booking->ride_type,
                ],
            ]);

        if (! $response->successful()) {
            Log::warning('Mollie payment creation failed.', [
                'status' => $response->status(),
            ]);

            $booking->update(['payment_status' => 'checkout_failed']);
            $notifications->dispatch($booking->refresh(), 'payment_failed');

            return back()->with('payment_error', $language === 'en'
                ? 'Online payment could not be started. Please try again or choose to pay later.'
                : 'Online betalen kon niet worden gestart. Probeer opnieuw of kies voor later betalen.');
        }

        $checkoutUrl = $response->json('_links.checkout.href');
        $mollieId    = $response->json('id');

        if (! $checkoutUrl || ! $mollieId) {
            $booking->update(['payment_status' => 'checkout_missing']);
            $notifications->dispatch($booking->refresh(), 'payment_failed');

            return back()->with('payment_error', 'Mollie checkout link is missing.');
        }

        $booking->update(['mollie_payment_id' => $mollieId, 'payment_status' => 'pending']);
        $request->session()->put('mollie_checkout.'.$booking->reference, $booking->id);
        $request->session()->put($sessionKey, $checkoutUrl);

        return redirect()->away($checkoutUrl);
    }

    public function webhook(Request $request, BookingNotificationDispatcher $notifications): \Illuminate\Http\Response
    {
        $mollieId = $request->input('id');

        if (! $mollieId) {
            return response('', 200);
        }

        $apiKey = config('services.mollie.key');

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->get("https://api.mollie.com/v2/payments/{$mollieId}");

        if (! $response->successful()) {
            Log::error('Mollie webhook: could not fetch payment', ['id' => $mollieId]);
            return response('', 200);
        }

        $mollieStatus = $response->json('status');

        $booking = Booking::where('mollie_payment_id', $mollieId)->first();

        if (! $booking) {
            Log::warning('Mollie webhook: booking not found', ['id' => $mollieId]);
            return response('', 200);
        }

        $checkout = Booking::whereKey($booking->id)->whereNotIn('payment_status', ['paid', 'refunded']);

        if ($mollieStatus === 'paid') {
            if ($checkout->update(['payment_status' => 'paid', 'payment_collected_at' => now()])) {
                $notifications->dispatch($booking->refresh(), 'payment_received');
            }
        } elseif (in_array($mollieStatus, ['expired', 'canceled', 'failed'], true)) {
            if ($checkout->where('payment_status', '!=', $mollieStatus)->update(['payment_status' => $mollieStatus])) {
                $notifications->dispatch($booking->refresh(), 'payment_failed');
            }
        }

        return response('', 200);
    }

    public function return(Request $request): RedirectResponse
    {
        $isEnglish = $request->query('lang') === 'en';
        $reference = $request->query('booking');
        $checkouts = $request->session()->get('mollie_checkout', []);
        $booking = is_string($reference) && isset($checkouts[$reference])
            ? Booking::find($checkouts[$reference])
            : null;
        $redirect = redirect(url($isEnglish ? '/?lang=en#booking' : '/#booking'))
            ->with('payment_notice', $isEnglish ? 'You are back from iDEAL.' : 'Je bent terug van iDEAL.');

        if ($booking) {
            $redirect->with('booking_tracking_url', $booking->trackingUrl())
                ->with('payment_notice', $isEnglish
                    ? 'You are back from iDEAL. Check the current payment status on your tracking page.'
                    : 'Je bent terug van iDEAL. Bekijk de actuele betaalstatus op je ritpagina.');
        }

        return $redirect;
    }
}
