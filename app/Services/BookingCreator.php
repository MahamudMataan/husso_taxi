<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Support\Str;

class BookingCreator
{
    public function __construct(
        private readonly GoogleRouteEstimator $routeEstimator,
        private readonly BookingPriceCalculator $priceCalculator,
        private readonly BookingNotificationDispatcher $notifications,
    ) {
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function create(array $validated, string $paymentMethod, string $paymentStatus = 'pending'): Booking
    {
        $route = $this->routeEstimator->estimate([
            'latitude' => $validated['pickup_latitude'],
            'longitude' => $validated['pickup_longitude'],
        ], [
            'latitude' => $validated['destination_latitude'],
            'longitude' => $validated['destination_longitude'],
        ]);

        $quotedAmount = $this->priceCalculator->quote($route['distance_km'], $route['duration_min']);

        $booking = Booking::create([
            'reference' => $this->reference(),
            'tracking_token' => $this->trackingToken(),
            'language' => $validated['language'] ?? 'nl',
            'customer_name' => $validated['customer_name'],
            'customer_email' => $validated['customer_email'],
            'phone' => $validated['phone'],
            'pickup' => $validated['pickup'],
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
            'base_fare' => BookingPriceCalculator::BASE_FARE,
            'km_rate' => BookingPriceCalculator::KM_RATE,
            'minute_rate' => BookingPriceCalculator::MINUTE_RATE,
            'quoted_amount' => $quotedAmount,
            'payment_method' => $paymentMethod,
            'payment_status' => $paymentStatus,
            'booking_status' => 'new',
            'confirmed_at' => now(),
        ]);

        $this->notifications->dispatch($booking, 'booking_created');

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
