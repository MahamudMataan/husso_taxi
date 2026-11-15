<?php

namespace Tests\Feature;

use App\Models\DriverBreakRequest;
use App\Models\DriverTimeLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_can_clock_in_and_out(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user)
            ->post(route('dashboard.driver.clock'), ['action' => 'in'])
            ->assertRedirect();

        $this->assertDatabaseHas('driver_time_logs', [
            'driver_name' => 'Omar Hussein',
            'clocked_out_at' => null,
        ]);

        $this->actingAs($user)
            ->post(route('dashboard.driver.clock'), ['action' => 'out'])
            ->assertRedirect();

        $this->assertNotNull(DriverTimeLog::first()->clocked_out_at);
    }

    public function test_booking_availability_tracks_the_drivers_shift(): void
    {
        $availability = route('bookings.availability');
        $this->getJson($availability)->assertOk()->assertExactJson(['driver_online' => false]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post(route('dashboard.driver.clock'), ['action' => 'in'])->assertRedirect();
        $this->getJson($availability)->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertExactJson(['driver_online' => true]);

        $this->post(route('dashboard.driver.clock'), ['action' => 'out'])->assertRedirect();
        $this->getJson($availability)->assertOk()->assertExactJson(['driver_online' => false]);
    }

    public function test_driver_can_submit_break_request(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user)
            ->post(route('dashboard.driver.breaks.store'), [
                'break_date' => '2026-06-08',
                'starts_at' => '12:00',
                'ends_at' => '12:30',
                'reason' => 'Lunch',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('driver_break_requests', [
            'driver_name' => 'Omar Hussein',
            'break_date' => '2026-06-08 00:00:00',
            'starts_at' => '12:00',
            'ends_at' => '12:30',
            'reason' => 'Lunch',
            'status' => 'requested',
        ]);

        $this->assertEquals('Omar Hussein', DriverBreakRequest::first()->driver_name);
    }
}
