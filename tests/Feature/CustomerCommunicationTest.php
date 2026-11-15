<?php

namespace Tests\Feature;

use App\Jobs\SendBookingNotification;
use App\Mail\BookingConfirmation;
use App\Mail\BookingStatusUpdate;
use App\Models\Booking;
use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\CallQueuedHandler;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class CustomerCommunicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_whatsapp_links_point_to_the_dispatch_number(): void
    {
        $this->withoutVite();
        $target = 'https://wa.me/31686463268';
        $pages = [
            '/' => ['//section[@id="contact"]', '//form', '//footer'],
            '/contact' => ['//main', '//footer'],
        ];

        foreach (['nl', 'en'] as $language) {
            foreach ($pages as $path => $areas) {
                $response = $this->get($path.'?lang='.$language)->assertOk();
                $document = new DOMDocument;
                $document->loadHTML($response->getContent(), LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
                $xpath = new DOMXPath($document);

                foreach ($areas as $area) {
                    $links = $xpath->query($area.'//a[@href="'.$target.'"]');
                    $this->assertSame(1, $links->length, "Expected one WhatsApp link in {$area} on {$path} ({$language}).");
                }
            }
        }
    }

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
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $events = [
            'driver_en_route' => 'driver_arriving',
            'arrived' => 'driver_arrived',
            'picked_up' => 'trip_started',
            'completed' => 'trip_completed',
        ];
        foreach ($events as $status => $event) {
            $this->patch(route('dashboard.bookings.dispatch', $booking), ['booking_status' => $status])
                ->assertRedirect()
                ->assertSessionHasNoErrors();
        }

        Queue::assertPushed(SendBookingNotification::class, 1 + count($events));
        foreach (['booking_accepted', ...array_values($events)] as $event) {
            Queue::assertPushedOn('booking-mail', SendBookingNotification::class, fn (SendBookingNotification $job) =>
                $job->bookingId === $booking->id && $job->event === $event && $job->channel === 'email');
        }
    }

    public function test_declining_or_canceling_a_booking_queues_the_corresponding_customer_email(): void
    {
        Queue::fake();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $declined = $this->booking();
        $canceled = $this->booking([
            'reference' => 'HUSSO-260610-CANCEL',
            'tracking_token' => 'tracking-token-for-canceled-booking',
            'admin_status' => 'accepted',
            'booking_status' => 'driver_assigned',
        ]);

        $this->patch(route('dashboard.bookings.review', $declined), ['admin_status' => 'rejected'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();
        $this->patch(route('dashboard.bookings.dispatch', $canceled), ['booking_status' => 'canceled', 'reason' => 'Test cancellation'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        Queue::assertPushed(SendBookingNotification::class, 2);
        Queue::assertPushedOn('booking-mail', SendBookingNotification::class, fn (SendBookingNotification $job) =>
            $job->bookingId === $declined->id && $job->event === 'booking_declined' && $job->channel === 'email');
        Queue::assertPushedOn('booking-mail', SendBookingNotification::class, fn (SendBookingNotification $job) =>
            $job->bookingId === $canceled->id && $job->event === 'trip_canceled' && $job->channel === 'email');
    }

    public function test_database_worker_processes_new_and_legacy_jobs_on_their_own_queues(): void
    {
        config([
            'queue.default' => 'database',
            'queue.connections.database.connection' => 'sqlite',
            'queue.connections.database.table' => 'jobs',
            'mail.default' => 'array',
        ]);
        $transport = Mail::mailer('array')->getSymfonyTransport();
        $booking = $this->booking();
        $originalRecipient = $booking->customer_email;
        $updatedRecipient = 'updated-customer@example.test';
        $queue = Queue::connection('database');

        // The old dispatcher serialized these four properties, with no recipient field.
        $legacyCommand = sprintf(
            'O:32:"App\Jobs\SendBookingNotification":4:{s:9:"bookingId";i:%d;s:5:"event";s:15:"driver_assigned";s:7:"channel";s:5:"email";s:5:"queue";s:13:"notifications";}',
            $booking->id,
        );
        $queue->pushRaw(json_encode([
            'uuid' => (string) Str::uuid(),
            'displayName' => SendBookingNotification::class,
            'job' => CallQueuedHandler::class.'@call',
            'maxTries' => 3,
            'backoff' => '30,120,300',
            'data' => ['commandName' => SendBookingNotification::class, 'command' => $legacyCommand],
        ], JSON_THROW_ON_ERROR), 'notifications');

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->patch(route('dashboard.bookings.review', $booking), ['admin_status' => 'accepted'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();
        $booking->update(['customer_email' => $updatedRecipient]);

        $this->assertSame(1, $queue->size('booking-mail'));
        $this->assertCount(0, $transport->messages());
        $this->artisan('queue:work database --queue=booking-mail --once --sleep=0')->assertSuccessful();

        $this->assertSame(0, $queue->size('booking-mail'));
        $this->assertSame(1, $queue->size('notifications'));
        $this->assertCount(1, $transport->messages());
        $this->assertSame($originalRecipient, $transport->messages()[0]->getEnvelope()->getRecipients()[0]->getAddress());
        $this->assertDatabaseHas('booking_notifications', [
            'booking_id' => $booking->id, 'event' => 'booking_accepted', 'recipient' => $originalRecipient, 'status' => 'sent',
        ]);

        $this->artisan('queue:work database --queue=notifications --once --sleep=0')->assertSuccessful();

        $this->assertSame(0, $queue->size('notifications'));
        $this->assertCount(2, $transport->messages());
        $this->assertSame($updatedRecipient, $transport->messages()[1]->getEnvelope()->getRecipients()[0]->getAddress());
        $this->assertDatabaseHas('booking_notifications', [
            'booking_id' => $booking->id, 'event' => 'driver_assigned', 'recipient' => $updatedRecipient, 'status' => 'sent',
        ]);
    }

    public function test_notification_job_sends_email_and_records_audit_row(): void
    {
        $transport = Mail::mailer('array')->getSymfonyTransport();
        $booking = $this->booking([
            'booking_status' => 'driver_en_route',
            'driver_eta_min' => 5,
        ]);

        (new SendBookingNotification($booking->id, 'driver_arriving', 'email'))->handle();

        $this->assertCount(1, $transport->messages());

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
        $this->assertStringContainsString('Volg mijn rit', $html);
        $this->assertStringContainsString('€ 64,67', $html);
        $staff = (new BookingConfirmation($booking, true, 'staff_booking_created'))->render();
        $update = (new BookingStatusUpdate($booking, 'trip_completed'))->render();
        foreach ([$staff, $update] as $message) {
            $this->assertStringContainsString(route('bookings.track', $booking->tracking_token), $message);
            $this->assertStringContainsString(route('bookings.receipt', $booking->tracking_token), $message);
        }
    }

    public function test_receipt_and_live_status_use_the_same_private_link_token(): void
    {
        $this->withoutVite();
        $booking = $this->booking();
        $receipt = route('bookings.receipt', $booking->tracking_token);
        $tracking = route('bookings.track', $booking->tracking_token);

        $this->get($receipt)->assertOk()->assertSee($tracking)->assertSee('€ 64,67')
            ->assertHeader('Referrer-Policy', 'no-referrer')
            ->assertHeader('Cache-Control', 'no-store, private');
        $this->get($tracking)->assertOk()->assertSee($receipt)
            ->assertHeader('Cache-Control', 'no-store, private');
        $this->getJson(route('bookings.track.state', $booking->tracking_token))->assertOk()
            ->assertJsonPath('booking_status', 'new')->assertJsonPath('driver_location', null)->assertJsonPath('driver', null);
        $this->get(route('bookings.receipt', 'invalid-token'))->assertNotFound();
        $this->getJson(route('bookings.track.state', 'invalid-token'))->assertNotFound();
    }

    public function test_only_staff_can_share_location_and_the_customer_only_sees_a_recent_active_position(): void
    {
        Queue::fake();
        $this->freezeTime();
        $booking = $this->booking(['booking_status' => 'driver_en_route']);
        $publish = route('dashboard.bookings.location.update', $booking);
        $state = route('bookings.track.state', $booking->tracking_token);
        $position = ['latitude' => 51.44, 'longitude' => 5.47];

        $this->putJson($publish, $position)->assertUnauthorized();
        $this->actingAs(User::factory()->create())->putJson($publish, $position)->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->putJson($publish, ['latitude' => 200, 'longitude' => 5])->assertUnprocessable();
        $this->putJson($publish, $position)->assertNoContent();
        $this->getJson($state)->assertJsonPath('driver_location.latitude', 51.44)
            ->assertJsonPath('driver_location.longitude', 5.47);
        $this->travel(76)->seconds();
        $this->getJson($state)->assertJsonPath('driver_location', null);
        $this->putJson($publish, $position)->assertNoContent();
        $this->deleteJson(route('dashboard.bookings.location.destroy', $booking))->assertNoContent();
        $this->getJson($state)->assertJsonPath('driver_location', null);
        $this->putJson($publish, $position)->assertNoContent();
        $this->patch(route('dashboard.bookings.dispatch', $booking), ['booking_status' => 'canceled', 'reason' => 'Test'])->assertRedirect();
        $this->putJson($publish, $position)->assertConflict();
        $this->getJson($state)->assertJsonPath('booking_status', 'canceled')->assertJsonPath('driver_location', null);
        $this->assertNull($booking->fresh()->driver_latitude);
    }

    public function test_booking_receipts_reach_both_recipients_once_and_staff_link_requires_login(): void
    {
        config(['services.booking.staff_email' => 'info@husso.nl']);
        $transport = Mail::mailer('array')->getSymfonyTransport();
        $booking = $this->booking(['admin_status' => 'accepted']);
        $customerReceipt = new SendBookingNotification($booking->id, 'booking_created', 'email');
        $staffReceipt = new SendBookingNotification($booking->id, 'staff_booking_created', 'email');
        $customerReceipt->handle();
        $staffReceipt->handle();
        $staffReceipt->handle();

        $this->assertCount(2, $transport->messages());
        $this->assertDatabaseHas('booking_notifications', ['event' => 'staff_booking_created', 'recipient' => 'info@husso.nl', 'status' => 'sent']);
        $this->assertDatabaseHas('booking_notifications', ['event' => 'booking_created', 'recipient' => $booking->customer_email, 'status' => 'sent']);
        $this->assertStringContainsString('Je ritaanvraag is ontvangen', (new BookingConfirmation($booking, false, 'booking_created'))->render());
        $this->assertStringContainsString(route('admin.bookings.show', $booking), (new BookingConfirmation($booking, true, 'staff_booking_created'))->render());
        $this->get(route('admin.bookings.show', $booking))->assertRedirect(route('login'));
    }

    public function test_a_temporary_mail_failure_is_logged_and_the_same_row_can_be_retried(): void
    {
        $manager = Mail::getFacadeRoot();
        $booking = $this->booking();
        $job = new SendBookingNotification($booking->id, 'booking_created', 'email');
        Mail::shouldReceive('to')->once()->andThrow(new \RuntimeException('Transport unavailable'));

        try {
            $job->handle();
            $this->fail('The mail failure should trigger a retry.');
        } catch (\RuntimeException) {
            $this->assertDatabaseHas('booking_notifications', ['booking_id' => $booking->id, 'status' => 'failed']);
        } finally {
            Mail::swap($manager);
        }

        $job->handle();
        $this->assertDatabaseCount('booking_notifications', 1);
        $this->assertDatabaseHas('booking_notifications', ['booking_id' => $booking->id, 'status' => 'sent', 'error_message' => null]);
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
