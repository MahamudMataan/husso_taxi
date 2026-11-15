<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Booking $booking,
        public bool $forStaff = false,
        public ?string $event = null,
    ) {
    }

    public function envelope(): Envelope
    {
        $prefix = $this->forStaff ? 'Nieuwe reservering' : match ($this->event) {
            'booking_accepted' => 'Je Husso Taxi rit is bevestigd',
            'booking_declined' => 'Update over je Husso Taxi aanvraag',
            default => 'Husso Taxi ritaanvraag ontvangen',
        };

        return new Envelope(
            subject: $prefix.' '.$this->booking->reference,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.booking-confirmation',
        );
    }
}
