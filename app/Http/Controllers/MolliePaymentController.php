<?php

namespace App\Http\Controllers;

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

        $paymentAmount = (float) (config('services.mollie.test_amount') ?: $booking->quoted_amount);
        $paymentAmount = max($paymentAmount, 0.01);

        $response = Http::withToken($apiKey)
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
            ]);

        if (! $response->successful()) {
            Log::warning('Mollie payment creation failed.', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            $booking->update(['payment_status' => 'checkout_failed']);
            $notifications->dispatch($booking->refresh(), 'payment_failed');

            return back()->with('payment_error', $response->json('detail') ?: 'Mollie payment could not be created.');
        }

        $checkoutUrl = $response->json('_links.checkout.href');
        $mollieId    = $response->json('id');

        if (! $checkoutUrl || ! $mollieId) {
            $booking->update(['payment_status' => 'checkout_missing']);
            $notifications->dispatch($booking->refresh(), 'payment_failed');

            return back()->with('payment_error', 'Mollie checkout link is missing.');
        }

        $booking->update(['mollie_payment_id' => $mollieId]);
        $request->session()->put('mollie_checkout.'.$booking->reference, $booking->id);

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
                $notifications->dispatch($booking->refresh(), 'booking_accepted');
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
