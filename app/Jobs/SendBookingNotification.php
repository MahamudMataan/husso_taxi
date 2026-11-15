<?php

namespace App\Jobs;

use App\Mail\BookingConfirmation;
use App\Mail\BookingStatusUpdate;
use App\Models\Booking;
use App\Models\BookingNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendBookingNotification implements ShouldQueue
{
    use Queueable;

    public const BOOKING_MAIL_QUEUE = 'booking-mail';

    public int $tries = 3;

    /**
     * @var array<int, int>
     */
    public array $backoff = [30, 120, 300];

    public function __construct(
        public int $bookingId,
        public string $event,
        public string $channel,
    ) {
    }

    public function handle(): void
    {
        $booking = Booking::findOrFail($this->bookingId);
        $recipient = $booking->customer_email;
        $message = $this->message($booking);

        $notification = BookingNotification::create([
            'booking_id' => $booking->id,
            'event' => $this->event,
            'channel' => $this->channel,
            'recipient' => $recipient,
            'message' => $message,
            'status' => 'queued',
        ]);

        try {
            $outgoing = Mail::to($recipient);
            if ($this->event === 'booking_created' && strcasecmp($recipient, 'info@husso.nl') !== 0) {
                $outgoing->bcc('info@husso.nl');
            }
            $outgoing->send($this->mailFor($booking));

            $notification->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            if (in_array($this->event, ['booking_created', 'booking_accepted'], true)) {
                $booking->update(['confirmation_emailed_at' => now()]);
            }
        } catch (Throwable $exception) {
            $notification->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    private function mailFor(Booking $booking): BookingConfirmation|BookingStatusUpdate
    {
        return match ($this->event) {
            'booking_created', 'booking_accepted' => new BookingConfirmation($booking),
            default => new BookingStatusUpdate($booking, $this->event),
        };
    }

    private function message(Booking $booking): string
    {
        $english = $booking->language === 'en';
        $trackingUrl = $booking->trackingUrl();
        $eta = $booking->driver_eta_min ?: $booking->duration_min;

        return match ($this->event) {
            'booking_created' => $english
                ? "Husso Taxi received your request {$booking->reference}. Track: {$trackingUrl}"
                : "Husso Taxi heeft je aanvraag {$booking->reference} ontvangen. Volg: {$trackingUrl}",
            'booking_accepted' => $english
                ? "Your Husso Taxi ride {$booking->reference} is accepted. Track: {$trackingUrl}"
                : "Je Husso Taxi rit {$booking->reference} is geaccepteerd. Volg: {$trackingUrl}",
            'driver_assigned' => $english
                ? "Driver {$booking->driver_name} is assigned to {$booking->reference}. ETA {$eta} min. {$trackingUrl}"
                : "Chauffeur {$booking->driver_name} is toegewezen aan {$booking->reference}. ETA {$eta} min. {$trackingUrl}",
            'driver_arriving' => $english
                ? "Your driver is arriving in about {$eta} min for {$booking->reference}. {$trackingUrl}"
                : "Je chauffeur arriveert over ongeveer {$eta} min voor {$booking->reference}. {$trackingUrl}",
            'trip_started' => $english
                ? "Your Husso Taxi trip {$booking->reference} has started. Follow progress: {$trackingUrl}"
                : "Je Husso Taxi rit {$booking->reference} is gestart. Volg de rit: {$trackingUrl}",
            'trip_completed' => $english
                ? "Your Husso Taxi trip {$booking->reference} is complete. Thank you for riding with us."
                : "Je Husso Taxi rit {$booking->reference} is afgerond. Bedankt voor je rit.",
            'payment_failed' => $english
                ? "Payment for {$booking->reference} failed or expired. Please contact Husso Taxi. {$trackingUrl}"
                : "De betaling voor {$booking->reference} is mislukt of verlopen. Neem contact op met Husso Taxi. {$trackingUrl}",
            default => $english
                ? "Husso Taxi update for {$booking->reference}: {$trackingUrl}"
                : "Husso Taxi update voor {$booking->reference}: {$trackingUrl}",
        };
    }
}
