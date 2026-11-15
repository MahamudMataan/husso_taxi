<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class BookingCreator
{
    public function __construct(
        private readonly TripQuote $quotes,
        private readonly BookingNotificationDispatcher $notifications,
    ) {
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function create(array $validated, string $paymentMethod, string $paymentStatus = 'pending'): Booking
    {
        $token = $validated['request_token'];
        if ($existing = Booking::where('request_token', $token)->first()) {
            return $this->samePaymentMethod($existing, $paymentMethod);
        }

        $language = $validated['language'] ?? 'nl';
        $route = $this->quotes->estimate([
            'latitude' => $validated['pickup_latitude'],
            'longitude' => $validated['pickup_longitude'],
            'place_id' => $validated['pickup_place_id'] ?? null,
            'address' => $validated['pickup'],
        ], [
            'latitude' => $validated['destination_latitude'],
            'longitude' => $validated['destination_longitude'],
            'place_id' => $validated['destination_place_id'] ?? null,
            'address' => $validated['destination'],
        ], $validated['ride_date'], $validated['ride_time'], $language);
        if ((int) round((float) $validated['amount'] * 100) !== (int) round($route['quoted_amount'] * 100)) {
            throw ValidationException::withMessages(['amount' => $language === 'en'
                ? 'The price has changed. Calculate the route again to review the current total.'
                : 'De prijs is gewijzigd. Bereken de rit opnieuw om het nieuwe totaal te bekijken.']);
        }

        $attributes = [
            'reference' => $this->reference(),
            'tracking_token' => $this->trackingToken(),
            'language' => $language,
            'customer_name' => $validated['customer_name'],
            'customer_email' => $validated['customer_email'],
            'phone' => $validated['phone'],
            'pickup' => ! filled($validated['pickup_place_id'] ?? null)
                ? ($language === 'en' ? 'Current location' : 'Huidige locatie').sprintf(' (GPS: %.6F, %.6F)', $validated['pickup_latitude'], $validated['pickup_longitude'])
                : $validated['pickup'],
            'pickup_latitude' => $validated['pickup_latitude'],
            'pickup_longitude' => $validated['pickup_longitude'],
            'destination' => $validated['destination'],
            'destination_latitude' => $validated['destination_latitude'],
            'destination_longitude' => $validated['destination_longitude'],
            'ride_date' => $validated['ride_date'],
            'ride_time' => $validated['ride_time'],
            'passengers' => $validated['passengers'],
            'ride_type' => $validated['ride_type'],
            'distance_km' => $route['distance_km'],
            'duration_min' => $route['duration_min'],
            ...Arr::only($route, ['base_fare', 'km_rate', 'minute_rate', 'quoted_amount', 'fare_type', 'departure_supplement_cents']),
            'customer_notes' => $validated['customer_notes'] ?? null,
            'invoice_requested' => (bool) ($validated['invoice_requested'] ?? false),
            'invoice_details' => (bool) ($validated['invoice_requested'] ?? false) ? [
                'name' => $validated['invoice_name'],
                'address' => $validated['invoice_address'],
                'postcode' => $validated['invoice_postcode'],
                'city' => $validated['invoice_city'],
                'country' => $validated['invoice_country'] ?? 'Nederland',
                'vat' => $validated['invoice_vat'] ?? null,
                'email' => $validated['invoice_email'] ?? $validated['customer_email'],
            ] : null,
            'payment_method' => $paymentMethod,
            'payment_status' => $paymentStatus,
            'booking_status' => 'new',
        ];
        $booking = Booking::firstOrCreate(['request_token' => $token], $attributes);

        if ($booking->wasRecentlyCreated) {
            $this->notifications->dispatch($booking, 'booking_created');
            $this->notifications->dispatch($booking, 'staff_booking_created');
        }

        return $this->samePaymentMethod($booking, $paymentMethod);
    }

    private function samePaymentMethod(Booking $booking, string $paymentMethod): Booking
    {
        if ($booking->payment_method !== $paymentMethod) {
            throw ValidationException::withMessages(['amount' => $booking->language === 'en'
                ? 'This request already exists. Check your booking email or contact us to change the payment method.'
                : 'Deze ritaanvraag bestaat al. Bekijk je boekingsmail of neem contact op om de betaalmethode te wijzigen.']);
        }

        return $booking;
    }

    private function reference(): string
    {
        do {
            $reference = 'HUSSO-'.now()->format('ymd').'-'.Str::upper(Str::random(5));
        } while (Booking::where('reference', $reference)->exists());

        return $reference;
    }

    private function trackingToken(): string
    {
        do {
            $token = Str::lower(Str::random(48));
        } while (Booking::where('tracking_token', $token)->exists());

        return $token;
    }
}
