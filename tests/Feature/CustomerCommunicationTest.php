<?php

namespace Tests\Feature;

use App\Jobs\SendBookingNotification;
use App\Mail\BookingConfirmation;
use App\Mail\BookingStatusUpdate;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CustomerCommunicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_tracking_page_shows_customer_trip_status(): void
    {
        $booking = $this->booking([
            'booking_status' => 'driver_en_route',
            'driver_eta_min' => 6,
        ]);

        $response = $this->get(route('bookings.track', $booking->tracking_token));

        $response->assertOk();
        $response->assertSee('Volg je rit');
        $response->assertSee('Chauffeur onderweg');
        $response->assertSee('Omar Hussein');
        $response->assertSee($booking->reference);
        $response->assertSee('6 min');
    }

    public function test_lifecycle_actions_queue_customer_email_updates(): void
    {
        Queue::fake();

        $user = User::factory()->create(['role' => 'admin']);
        $booking = $this->booking();

        $this->actingAs($user)
            ->patch(route('dashboard.bookings.review', $booking), [
                'admin_status' => 'accepted',
            ])
            ->assertRedirect();

        Queue::assertPushed(SendBookingNotification::class, 1);
        Queue::assertPushedOn(SendBookingNotification::BOOKING_MAIL_QUEUE, SendBookingNotification::class);
        Queue::assertPushed(SendBookingNotification::class, fn (SendBookingNotification $job) => $job->event === 'driver_assigned' && $job->channel === 'email');
    }

    public function test_notification_job_sends_email_and_records_audit_row(): void
    {
        Mail::fake();

        $booking = $this->booking([
            'booking_status' => 'driver_en_route',
            'driver_eta_min' => 5,
        ]);

        (new SendBookingNotification($booking->id, 'driver_arriving', 'email'))->handle();

        Mail::assertSent(BookingStatusUpdate::class, fn (BookingStatusUpdate $mail) => $mail->event === 'driver_arriving');

        $this->assertDatabaseHas('booking_notifications', [
            'booking_id' => $booking->id,
            'event' => 'driver_arriving',
            'channel' => 'email',
            'recipient' => $booking->customer_email,
            'status' => 'sent',
        ]);

    }

    public function test_booking_confirmation_email_includes_tracking_and_receipt_links(): void
    {
        $booking = $this->booking([
            'tracking_token' => 'tracking-token-for-confirmation-email',
        ]);

        $html = (new BookingConfirmation($booking))->render();

        $this->assertStringContainsString(route('bookings.track', $booking->tracking_token), $html);
        $this->assertStringContainsString(route('bookings.receipt', $booking->tracking_token), $html);
        $this->assertStringContainsString('Open ritbon', $html);
    }

    public function test_new_booking_email_copies_husso_and_status_updates_link_to_the_receipt(): void
    {
        Mail::fake();
        $booking = $this->booking();
        (new SendBookingNotification($booking->id, 'booking_created', 'email'))->handle();

        Mail::assertSent(BookingConfirmation::class, fn (BookingConfirmation $mail) => $mail->hasTo($booking->customer_email)
            && $mail->hasBcc('info@husso.nl'));
        $statusMail = (new BookingStatusUpdate($booking, 'payment_failed'))->render();
        $this->assertStringContainsString(route('bookings.track', $booking->tracking_token), $statusMail);
        $this->assertStringContainsString(route('bookings.receipt', $booking->tracking_token), $statusMail);
    }

    private function booking(array $overrides = []): Booking
    {
        return Booking::create(array_merge([
            'reference' => 'HUSSO-260610-COMMS',
            'tracking_token' => 'tracking-token-for-customer-comms',
            'language' => 'nl',
            'customer_name' => 'Test Klant',
            'customer_email' => 'klant@example.nl',
            'phone' => '+31 6 86463268',
            'pickup' => 'Wegastraat, Nederland',
            'pickup_latitude' => 52.1000000,
            'pickup_longitude' => 5.1000000,
            'destination' => 'Schiphol Airport, Amsterdam',
            'destination_latitude' => 52.3105000,
            'destination_longitude' => 4.7683000,
            'ride_date' => now()->toDateString(),
            'ride_time' => '14:30',
            'passengers' => '1',
            'ride_type' => 'standard',
            'distance_km' => 12.3,
            'duration_min' => 26,
            'base_fare' => 4.00,
            'km_rate' => 3.10,
            'minute_rate' => 0.85,
            'quoted_amount' => 64.67,
            'payment_method' => 'ideal',
            'payment_status' => 'pending',
            'booking_status' => 'new',
            'admin_status' => 'pending',
            'driver_name' => 'Omar Hussein',
            'driver_phone' => '+31 6 86463268',
            'driver_vehicle' => 'Husso Taxi',
            'confirmed_at' => now(),
        ], $overrides));
    }
}
