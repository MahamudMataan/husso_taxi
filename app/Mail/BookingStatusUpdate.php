<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingStatusUpdate extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Booking $booking,
        public string $event,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine().' '.$this->booking->reference,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.booking-status-update',
        );
    }

    private function subjectLine(): string
    {
        $english = $this->booking->language === 'en';

        return match ($this->event) {
            'booking_created' => $english ? 'Your Husso Taxi request was received' : 'Je Husso Taxi aanvraag is ontvangen',
            'booking_accepted' => $english ? 'Your Husso Taxi ride is accepted' : 'Je Husso Taxi rit is geaccepteerd',
            'driver_assigned' => $english ? 'Your Husso Taxi driver is assigned' : 'Je Husso Taxi chauffeur is toegewezen',
            'driver_arriving' => $english ? 'Your driver is on the way' : 'Je chauffeur is onderweg',
            'trip_started' => $english ? 'Your trip has started' : 'Je rit is gestart',
            'trip_completed' => $english ? 'Your trip is complete' : 'Je rit is afgerond',
            'payment_failed' => $english ? 'Payment needs attention' : 'Betaling vraagt aandacht',
            default => $english ? 'Husso Taxi booking update' : 'Husso Taxi boekingsupdate',
        };
    }
}
