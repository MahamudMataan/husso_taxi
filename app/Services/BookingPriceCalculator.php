<?php

namespace App\Services;

class BookingPriceCalculator
{
    public const BASE_FARE = 0.00;
    public const KM_RATE = 3.00;
    public const MINUTE_RATE = 0.50;

    public function quote(float $distanceKm, int $durationMin): float
    {
        return round(($distanceKm * self::KM_RATE) + ($durationMin * self::MINUTE_RATE), 2);
    }
}
