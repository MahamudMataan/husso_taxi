<?php

namespace App\Services;

class BookingPriceCalculator
{
    public const BASE_FARE = 4.00;
    public const KM_RATE = 3.17;
    public const MINUTE_RATE = 0.52;
    public const LONG_DISTANCE_KM = 55;
    public const LONG_DISTANCE_KM_RATE = 2.50;
    public const MINIMUM_CENTS = 2000;

    public function kilometerRate(int $distanceMeters): float
    {
        return $distanceMeters > self::LONG_DISTANCE_KM * 1000 ? self::LONG_DISTANCE_KM_RATE : self::KM_RATE;
    }

    public function quote(float $distanceKm, int $durationMin, int $distanceMeters): float
    {
        return $this->breakdown($distanceKm, $durationMin, $distanceMeters, self::MINIMUM_CENTS)['quoted_amount'];
    }

    public function breakdown(float $distanceKm, int $durationMin, int $distanceMeters, int $minimumCents, ?int $fixedCents = null): array
    {
        $rate = $this->kilometerRate($distanceMeters);
        $tenthsKm = (int) round($distanceKm * 10);
        $distanceCents = intdiv($tenthsKm * (int) round($rate * 100) + 5, 10);
        $normalCents = (int) round(self::BASE_FARE * 100) + $distanceCents
            + $durationMin * (int) round(self::MINUTE_RATE * 100);
        $fareCents = $fixedCents ?? $normalCents;
        $supplement = max(0, $minimumCents - $fareCents);

        return [
            'base_fare' => $fixedCents === null ? self::BASE_FARE : $fixedCents / 100,
            'km_rate' => $fixedCents === null ? $rate : 0,
            'minute_rate' => $fixedCents === null ? self::MINUTE_RATE : 0,
            'fare_type' => $fixedCents === null ? 'metered' : 'schiphol_fixed',
            'departure_supplement_cents' => $supplement,
            'ride_price' => $fareCents / 100,
            'quoted_amount' => ($fareCents + $supplement) / 100,
        ];
    }
}
