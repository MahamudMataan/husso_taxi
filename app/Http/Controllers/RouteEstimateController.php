<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\GoogleRouteEstimator;
use RuntimeException;

class RouteEstimateController extends Controller
{
    public function store(Request $request, GoogleRouteEstimator $routeEstimator): JsonResponse
    {
        $validated = $request->validate([
            'origin.latitude' => ['required', 'numeric', 'between:-90,90'],
            'origin.longitude' => ['required', 'numeric', 'between:-180,180'],
            'destination.latitude' => ['required', 'numeric', 'between:-90,90'],
            'destination.longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        try {
            return response()->json($routeEstimator->estimate($validated['origin'], $validated['destination']));
        } catch (RuntimeException) {
            return response()->json([
                'message' => 'Route could not be calculated.',
            ], 422);
        }
    }
}
