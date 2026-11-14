<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\DriverTimeLog;
use Illuminate\Support\Collection;

class DispatchMetrics
{
    public const DRIVER_NAME = 'Omar Hussein';
    public const DRIVER_PHONE = '+31 6 86463268';
    public const DRIVER_VEHICLE = 'Husso Taxi';
    public const DRIVER_PLATE = 'TX-001-H';

    /**
     * @return array<string, mixed>
     */
    public function overview(): array
    {
        $today = now('Europe/Amsterdam')->toDateString();
        $todayBookings = Booking::whereDate('ride_date', $today);
        $activeShift = $this->activeShift();
        $todayRevenue = (clone $todayBookings)->whereIn('payment_status', ['paid', 'pay_later'])->sum('quoted_amount');
        $todayCount = (clone $todayBookings)->count();

        return [
            'today_bookings' => $todayCount,
            'today_revenue' => $todayRevenue,
            'open_bookings' => Booking::whereIn('booking_status', Booking::OPEN_STATUSES)->count(),
            'active_rides' => Booking::whereIn('booking_status', Booking::ACTIVE_STATUSES)->count(),
            'driver_online' => (bool) $activeShift,
            'active_shift' => $activeShift,
            'average_ride_value' => $todayCount > 0 ? round($todayRevenue / $todayCount, 2) : 0,
        ];
    }

    public function activeShift(): ?DriverTimeLog
    {
        return DriverTimeLog::where('driver_name', self::DRIVER_NAME)
            ->whereNull('clocked_out_at')
            ->latest('clocked_in_at')
            ->first();
    }

    /**
     * @return Collection<int, Booking>
     */
    public function recentBookings(int $limit = 10): Collection
    {
        return Booking::latest()->limit($limit)->get();
    }

    public function activeBooking(): ?Booking
    {
        return Booking::whereIn('booking_status', ['driver_en_route', 'arrived', 'picked_up', 'driver_assigned', 'new'])
            ->orderByRaw("case booking_status when 'picked_up' then 1 when 'driver_en_route' then 2 when 'arrived' then 3 when 'driver_assigned' then 4 else 5 end")
            ->latest()
            ->first();
    }
}
