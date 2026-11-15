<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\DriverTimeLog;
use App\Models\User;
use App\Services\DispatchMetrics;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DriverDayController extends Controller
{
    public function show(DispatchMetrics $dispatch): View
    {
        $today = now('Europe/Amsterdam');
        $paymentsStart = $today->copy()->startOfDay()->utc();
        $paymentsEnd = $today->copy()->addDay()->startOfDay()->utc();
        $todayBookings = Booking::whereDate('ride_date', $today->toDateString());
        $rides = (clone $todayBookings)->whereIn('booking_status', Booking::OPEN_STATUSES)->orderBy('ride_time')->get();
        $currentTime = $today->format('H:i');
        $overdueBooking = $rides->first(fn (Booking $booking): bool => substr($booking->ride_time, 0, 5) < $currentTime);
        $nextBooking = $rides->first(fn (Booking $booking): bool => substr($booking->ride_time, 0, 5) >= $currentTime)
            ?? Booking::whereDate('ride_date', '>', $today->toDateString())
                ->whereIn('booking_status', Booking::OPEN_STATUSES)
                ->orderBy('ride_date')->orderBy('ride_time')->first();
        $shift = $dispatch->activeShift() ?? DriverTimeLog::where('driver_name', DispatchMetrics::DRIVER_NAME)
            ->where('clocked_out_at', '>=', $paymentsStart)
            ->latest('clocked_out_at')
            ->first();

        return view('driver.dashboard', [
            'today' => $today,
            'driverName' => DispatchMetrics::DRIVER_NAME,
            'shift' => $shift?->load('breaks'),
            'rides' => $rides,
            'overdueBooking' => $overdueBooking,
            'nextBooking' => $nextBooking,
            'ridesCompleted' => (clone $todayBookings)->where('booking_status', 'completed')->count(),
            'openAmount' => (clone $todayBookings)
                ->whereIn('booking_status', [...Booking::OPEN_STATUSES, 'completed'])
                ->whereIn('payment_status', ['pay_later', 'pending'])->sum('quoted_amount'),
            'receivedToday' => Booking::where('payment_status', 'paid')
                ->where('payment_collected_at', '>=', $paymentsStart)
                ->where('payment_collected_at', '<', $paymentsEnd)
                ->sum('quoted_amount'),
            'activeBooking' => Booking::whereIn('booking_status', Booking::ACTIVE_STATUSES)
                ->latest('updated_at')->first(),
        ]);
    }

    public function clock(Request $request, DispatchMetrics $dispatch): RedirectResponse
    {
        $action = $request->validate(['action' => ['required', 'in:in,out,break,resume']])['action'];

        $message = DB::transaction(function () use ($action, $dispatch, $request): string {
            User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $shift = $dispatch->activeShift();
            $now = now();

            if (! $shift) {
                if ($action !== 'in') {
                    return 'Start eerst je dienst.';
                }
                DriverTimeLog::create(['driver_name' => DispatchMetrics::DRIVER_NAME, 'clocked_in_at' => $now]);

                return 'Je dienst is gestart.';
            }

            $break = $shift->breaks()->whereNull('ended_at')->first();
            if ($action === 'out') {
                $break?->update(['ended_at' => $now]);
                $shift->update(['clocked_out_at' => $now]);

                return 'Je dienst is beëindigd.';
            }
            if ($action === 'break' && ! $break) {
                $shift->breaks()->create(['started_at' => $now]);

                return 'Je pauze is gestart.';
            }
            if ($action === 'resume' && $break) {
                $break->update(['ended_at' => $now]);

                return 'Je dienst is hervat.';
            }

            return match ($action) {
                'in' => 'Je dienst loopt al.',
                'break' => 'Je hebt al pauze.',
                default => 'Er staat geen pauze open.',
            };
        });

        return back()->with('status', $message);
    }
}
