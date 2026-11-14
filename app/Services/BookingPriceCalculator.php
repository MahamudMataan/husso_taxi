<?php

namespace App\Services;

class BookingPriceCalculator
{
    public const BASE_FARE = 4.00;
    public const KM_RATE = 3.17;
    public const MINUTE_RATE = 0.52;
    public const LONG_DISTANCE_KM = 55;
    public const LONG_DISTANCE_KM_RATE = 2.50;

    public function kilometerRate(int $distanceMeters): float
    {
        return $distanceMeters > self::LONG_DISTANCE_KM * 1000 ? self::LONG_DISTANCE_KM_RATE : self::KM_RATE;
    }

    public function quote(float $distanceKm, int $durationMin, int $distanceMeters): float
    {
        return round(self::BASE_FARE + ($distanceKm * $this->kilometerRate($distanceMeters)) + ($durationMin * self::MINUTE_RATE), 2);
    }
}
