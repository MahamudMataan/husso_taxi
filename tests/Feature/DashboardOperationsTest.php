<?php

namespace Tests\Feature;

use App\Jobs\SendBookingNotification;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DashboardOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_dispatch_operations_panel(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $booking = $this->booking();

        $this->actingAs($user)->get(route('dashboard'))->assertRedirect('/admin');

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Ritbeheer');
        $response->assertSee('Ritten vandaag');
        $response->assertSee('Gem. ritwaarde');
        $response->assertSee($booking->reference);
    }

    public function test_booking_dispatch_and_payment_can_be_updated(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $booking = $this->booking(['booking_status' => 'driver_assigned']);

        $this->actingAs($user)
            ->patch(route('dashboard.bookings.dispatch', $booking), [
                'booking_status' => 'driver_en_route',
            ])
            ->assertRedirect();

        $this->actingAs($user)
            ->patch(route('dashboard.bookings.payment', $booking), [
                'payment_status' => 'paid',
            ])
            ->assertRedirect();

        $booking->refresh();

        $this->assertSame('driver_en_route', $booking->booking_status);
        $this->assertSame('paid', $booking->payment_status);
        $this->assertNotNull($booking->payment_collected_at);
    }

    public function test_dispatch_rejects_skipped_and_repeated_statuses(): void
    {
        Queue::fake();
        $user = User::factory()->create(['role' => 'admin']);
        $booking = $this->booking();
        $dispatchUrl = route('dashboard.bookings.dispatch', $booking);

        $this->actingAs($user)->patch($dispatchUrl, ['booking_status' => 'completed'])
            ->assertSessionHasErrors('dispatch');
        $this->assertSame('new', $booking->fresh()->booking_status);
        $this->actingAs($user)->patch($dispatchUrl, ['booking_status' => 'driver_assigned'])
            ->assertRedirect();
        $this->actingAs($user)->patch($dispatchUrl, ['booking_status' => 'driver_assigned'])
            ->assertSessionHasErrors('dispatch');
        $this->assertSame('driver_assigned', $booking->fresh()->booking_status);
        Queue::assertPushed(SendBookingNotification::class, 1);
    }

    public function test_search_and_payment_filter_remain_active_in_booking_details(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $booking = $this->booking();
        $paidBooking = $this->booking(['reference' => 'HUSSO-PAID', 'payment_status' => 'paid']);
        $filters = ['search' => 'Schiphol', 'filter' => 'payment'];

        $this->actingAs($user)->get(route('admin.bookings.index', $filters))
            ->assertOk()
            ->assertSee($booking->reference)
            ->assertDontSee($paidBooking->reference);
        $this->actingAs($user)->get(route('admin.bookings.show', ['booking' => $booking] + $filters))
            ->assertOk()
            ->assertSee(route('admin.bookings.index', $filters));
    }

    private function booking(array $overrides = []): Booking
    {
        return Booking::create(array_merge([
            'reference' => 'HUSSO-260610-TEST1',
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
            'confirmed_at' => now(),
        ], $overrides));
    }
}
