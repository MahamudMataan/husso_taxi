<?php

namespace Tests\Feature;

use App\Models\DriverBreakRequest;
use App\Models\DriverTimeLog;
use App\Models\DriverShiftBreak;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_today_allows_the_administrator_to_record_a_single_pause_and_finish_a_shift(): void
    {
        $clock = route('dashboard.driver.clock');
        $this->post($clock, ['action' => 'in'])->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get(route('driver.dashboard'))->assertForbidden();
        $this->post($clock, ['action' => 'in'])->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->get(route('driver.dashboard'))->assertOk()->assertSee('Dienst starten');
        $this->post($clock, ['action' => 'in'])->assertRedirect();
        $this->post($clock, ['action' => 'in'])->assertRedirect();
        $this->post($clock, ['action' => 'break'])->assertRedirect();
        $this->post($clock, ['action' => 'break'])->assertRedirect();
        $this->get(route('driver.dashboard'))->assertSee('Dienst hervatten');
        $this->assertDatabaseCount('driver_time_logs', 1);
        $this->assertDatabaseCount('driver_shift_breaks', 1);
        $this->post($clock, ['action' => 'out'])->assertRedirect();
        $this->assertNotNull(DriverShiftBreak::firstOrFail()->ended_at);
        $this->assertNotNull(DriverTimeLog::firstOrFail()->clocked_out_at);
    }

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
