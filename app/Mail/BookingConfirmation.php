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
    ) {
    }

    public function envelope(): Envelope
    {
        $prefix = match ($this->booking->admin_status) {
            'accepted' => $this->forStaff ? 'Husso Taxi rit geaccepteerd' : 'Je Husso Taxi rit is bevestigd',
            'rejected' => $this->forStaff ? 'Husso Taxi rit geweigerd' : 'Update over je Husso Taxi aanvraag',
            default => $this->forStaff ? 'Nieuwe Husso Taxi rit' : 'Husso Taxi ritaanvraag ontvangen',
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
