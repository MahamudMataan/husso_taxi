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

        if (! $apiKey) {
            return back()->with('payment_error', 'Mollie API key is not configured yet.');
        }

        $language    = $validated['language'] ?? 'nl';
        $description = $language === 'en' ? 'Husso Taxi booking' : 'Husso Taxi boeking';

        try {
            $booking = $bookingCreator->create($validated, 'ideal', 'pending');
        } catch (RuntimeException) {
            return back()->with('payment_error', $language === 'en'
                ? 'The route could not be verified safely. Please choose pickup and destination again.'
                : 'De route kon niet veilig gecontroleerd worden. Kies ophaal- en bestemmingsplaats opnieuw.');
        }

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->post('https://api.mollie.com/v2/payments', [
                'amount'      => [
                    'currency' => 'EUR',
                    'value'    => number_format((float) $booking->quoted_amount, 2, '.', ''),
                ],
                'description' => $description,
                'method'      => 'ideal',
                'redirectUrl' => route('payments.mollie.return', ['lang' => $language]),
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
            $booking->update(['payment_status' => 'checkout_failed']);
            $notifications->dispatch($booking->refresh(), 'payment_failed');

            return back()->with('payment_error', 'Mollie payment could not be created.');
        }

        $checkoutUrl = $response->json('_links.checkout.href');
        $mollieId    = $response->json('id');

        if (! $checkoutUrl || ! $mollieId) {
            $booking->update(['payment_status' => 'checkout_missing']);
            $notifications->dispatch($booking->refresh(), 'payment_failed');

            return back()->with('payment_error', 'Mollie checkout link is missing.');
        }

        $booking->update(['mollie_payment_id' => $mollieId]);

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

        if ($mollieStatus === 'paid' && $booking->payment_status !== 'paid') {
            $booking->update([
                'payment_status'         => 'paid',
            ]);

            $notifications->dispatch($booking->refresh(), 'booking_accepted');
        } elseif (in_array($mollieStatus, ['expired', 'canceled', 'failed'])) {
            $booking->update(['payment_status' => $mollieStatus]);
            $notifications->dispatch($booking->refresh(), 'payment_failed');
        }

        return response('', 200);
    }

    public function return(Request $request): RedirectResponse
    {
        $language = $request->query('lang') === 'en' ? 'en' : null;

        return redirect(url($language ? '/?lang=en#booking' : '/#booking'))
            ->with('payment_success', $language === 'en'
                ? 'Payment completed. You will receive a confirmation email shortly.'
                : 'Betaling geslaagd. Je ontvangt zo een bevestigingsmail.');
    }
}
