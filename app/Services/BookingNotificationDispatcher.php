<?php

namespace App\Services;

use App\Jobs\SendBookingNotification;
use App\Models\Booking;

class BookingNotificationDispatcher
{
    public function dispatch(Booking $booking, string $event): void
    {
        foreach (['email'] as $channel) {
            SendBookingNotification::dispatch($booking->id, $event, $channel)->onQueue('notifications');
        }
    }
}
