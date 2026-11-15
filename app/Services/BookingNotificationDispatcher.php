<?php

namespace App\Services;

use App\Jobs\SendBookingNotification;
use App\Models\Booking;
use App\Models\BookingNotification;
use Illuminate\Support\Facades\Log;
use Throwable;

class BookingNotificationDispatcher
{
    public function dispatch(Booking $booking, string $event): bool
    {
        $recipient = $event === 'staff_booking_created' ? config('services.booking.staff_email') : $booking->customer_email;
        if (! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            Log::warning('Booking notification has no valid recipient.', ['booking_id' => $booking->id, 'event' => $event]);

            return false;
        }

        $notice = null;
        try {
            $notice = BookingNotification::firstOrCreate([
                'booking_id' => $booking->id,
                'event' => $event,
                'channel' => 'email',
                'recipient' => $recipient,
            ], ['status' => 'queued']);
            if ($notice->status === 'sent') {
                return true;
            }
            $notice->update(['status' => 'queued', 'error_message' => null]);
            dispatch((new SendBookingNotification($booking->id, $event, 'email', $recipient))
                ->onQueue(config('queue.booking_mail_queue'))
                ->afterCommit());

            return true;
        } catch (Throwable $exception) {
            if ($notice && $notice->status !== 'sent') {
                try {
                    $notice->update(['status' => 'failed', 'error_message' => $exception::class]);
                } catch (Throwable $statusError) {
                    Log::warning('Booking notification status could not be updated.', [
                        'notification_id' => $notice->id,
                        'exception_type' => $statusError::class,
                    ]);
                }
            }
            Log::warning('Booking notification could not be processed.', [
                'booking_id' => $booking->id,
                'event' => $event,
                'exception_type' => $exception::class,
            ]);

            return false;
        }
    }
}
