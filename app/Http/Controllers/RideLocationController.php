<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RideLocationController extends Controller
{
    public function update(Request $request, Booking $booking): Response
    {
        $position = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $count = Booking::whereKey($booking->id)->whereIn('booking_status', Booking::ACTIVE_STATUSES)->toBase()->update([
            'driver_latitude' => $position['latitude'],
            'driver_longitude' => $position['longitude'],
            'driver_location_shared_at' => now(),
        ]);
        if (! $count) {
            $booking->refresh();
            abort_unless(in_array($booking->booking_status, Booking::ACTIVE_STATUSES, true), 409);
        }

        return response()->noContent();
    }

    public function destroy(Booking $booking): Response
    {
        Booking::whereKey($booking->id)->toBase()->update([
            'driver_latitude' => null,
            'driver_longitude' => null,
            'driver_location_shared_at' => null,
        ]);

        return response()->noContent();
    }
}
