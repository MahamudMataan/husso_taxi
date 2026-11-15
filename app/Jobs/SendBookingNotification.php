<?php

namespace App\Jobs;

use App\Mail\BookingConfirmation;
use App\Mail\BookingStatusUpdate;
use App\Models\Booking;
use App\Models\BookingNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Throwable;

class SendBookingNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @var array<int, int>
     */
    public array $backoff = [30, 120, 300];

    public function __construct(
        public int $bookingId,
        public string $event,
        public string $channel,
        public ?string $recipient = null,
    ) {
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping($this->bookingId.':'.$this->event.':'.$this->channel))->releaseAfter(30)->expireAfter(180)];
    }

    public function handle(): void
    {
        $booking = Booking::findOrFail($this->bookingId);
        $forStaff = $this->event === 'staff_booking_created';
        $recipient = $this->recipient ?? ($forStaff ? config('services.booking.staff_email') : $booking->customer_email);
        if (! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $criteria = [
            'booking_id' => $booking->id,
            'event' => $this->event,
            'channel' => $this->channel,
            'recipient' => $recipient,
        ];
        if (BookingNotification::where($criteria)->where('status', 'sent')->exists()) {
            return;
        }
        $notification = BookingNotification::firstOrCreate($criteria, [
            'status' => 'queued',
        ]);

        try {
            $sent = Mail::to($recipient)->send($this->mailFor($booking));
            if (! $sent) {
                throw new \RuntimeException('The mail transport did not accept the message.');
            }

            $notification->update([
                'status' => 'sent',
                'sent_at' => now(),
                'error_message' => null,
            ]);

            if (in_array($this->event, ['booking_created', 'booking_accepted'], true)) {
                $booking->update(['confirmation_emailed_at' => now()]);
            }
        } catch (Throwable $exception) {
            $notification->update([
                'status' => 'failed',
                'error_message' => $exception::class,
            ]);

            throw $exception;
        }
    }

    private function mailFor(Booking $booking): BookingConfirmation|BookingStatusUpdate
    {
        if ($this->event === 'booking_accepted' && ($booking->admin_status !== 'accepted' || $booking->booking_status === 'canceled')) {
            return new BookingStatusUpdate($booking, $booking->payment_status === 'paid' ? 'payment_received' : 'booking_status_changed');
        }

        return match ($this->event) {
            'staff_booking_created' => new BookingConfirmation($booking, true, $this->event),
            'booking_created', 'booking_accepted', 'booking_declined' => new BookingConfirmation($booking, false, $this->event),
            default => new BookingStatusUpdate($booking, $this->event),
        };
    }
}
