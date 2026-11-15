<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\TripQuote;
use RuntimeException;

class RouteEstimateController extends Controller
{
    public function store(Request $request, TripQuote $quotes): JsonResponse
    {
        $validated = $request->validate([
            'origin.latitude' => ['required', 'numeric', 'between:-90,90'],
            'origin.longitude' => ['required', 'numeric', 'between:-180,180'],
            'destination.latitude' => ['required', 'numeric', 'between:-90,90'],
            'destination.longitude' => ['required', 'numeric', 'between:-180,180'],
            'origin.place_id' => ['nullable', 'string', 'regex:/^[a-zA-Z0-9_-]{1,255}$/'],
            'destination.place_id' => ['nullable', 'string', 'regex:/^[a-zA-Z0-9_-]{1,255}$/'],
            'ride_date' => ['nullable', 'required_with:ride_time', 'date_format:Y-m-d'],
            'ride_time' => ['nullable', 'required_with:ride_date', 'date_format:H:i'],
            'language' => ['nullable', 'in:nl,en'],
        ]);

        try {
            return response()->json($quotes->estimate(
                $validated['origin'],
                $validated['destination'],
                $validated['ride_date'] ?? null,
                $validated['ride_time'] ?? null,
                $validated['language'] ?? 'nl',
            ));
        } catch (RuntimeException) {
            return response()->json([
                'message' => 'Route could not be calculated.',
            ], 422);
        }
    }
}
