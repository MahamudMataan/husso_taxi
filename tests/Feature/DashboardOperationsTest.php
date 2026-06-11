<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $response->assertSee('Dispatch control room');
        $response->assertSee('Ritten vandaag');
        $response->assertSee('Gem. ritwaarde');
        $response->assertSee($booking->reference);
    }

    public function test_booking_dispatch_and_payment_can_be_updated(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $booking = $this->booking();

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
