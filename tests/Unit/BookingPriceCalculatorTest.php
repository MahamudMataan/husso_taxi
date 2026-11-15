<?php

namespace Tests\Unit;

use App\Models\Booking;
use App\Services\BookingPriceCalculator;
use PHPUnit\Framework\TestCase;

class BookingPriceCalculatorTest extends TestCase
{
    public function test_long_distance_rate_applies_to_the_entire_trip_above_fifty_five_kilometers(): void
    {
        $calculator = new BookingPriceCalculator;

        $this->assertSame(209.55, $calculator->quote(55.0, 60, 55000));
        $this->assertSame(172.70, $calculator->quote(55.0, 60, 55001));
        $this->assertSame(185.20, $calculator->quote(60.0, 60, 60000));
    }

    public function test_an_existing_booking_keeps_its_recorded_rates_and_total(): void
    {
        $booking = new Booking([
            'distance_km' => 60.0,
            'duration_min' => 60,
            'base_fare' => 0.00,
            'km_rate' => 3.00,
            'minute_rate' => 0.50,
            'quoted_amount' => 210.00,
        ]);

        $this->assertSame(180.00, $booking->distanceFare());
        $this->assertSame(30.00, $booking->timeFare());
        $this->assertSame(210.00, $booking->calculatedFare());
    }

    public function test_short_trip_is_topped_up_to_the_configured_minimum(): void
    {
        $calculator = new BookingPriceCalculator;

        $short = $calculator->breakdown(2.0, 5, 2000, 2000);
        $this->assertSame(12.94, $short['ride_price']);
        $this->assertSame(706, $short['departure_supplement_cents']);
        $this->assertEquals(20.0, $short['quoted_amount']);
        $this->assertSame(0, $calculator->breakdown(0.0, 0, 0, 2000, 2000)['departure_supplement_cents']);
        $this->assertSame(0, $calculator->breakdown(12.3, 26, 12340, 2000)['departure_supplement_cents']);
    }
}
