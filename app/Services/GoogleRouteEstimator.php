<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GoogleRouteEstimator
{
    /**
     * @param  array{latitude: numeric, longitude: numeric}  $origin
     * @param  array{latitude: numeric, longitude: numeric}  $destination
     * @return array{distance_km: float, duration_min: int}
     */
    public function estimate(array $origin, array $destination): array
    {
        $cacheKey = 'route:'
            .round((float) $origin['latitude'], 4).','
            .round((float) $origin['longitude'], 4).':'
            .round((float) $destination['latitude'], 4).','
            .round((float) $destination['longitude'], 4);

        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($origin, $destination) {
            return $this->fetchFromGoogle($origin, $destination);
        });
    }

    private function fetchFromGoogle(array $origin, array $destination): array
    {
        $apiKey = config('services.google_maps.key');

        if (! $apiKey) {
            throw new RuntimeException('Google Maps API key is not configured.');
        }

        $response = Http::timeout(5)->connectTimeout(3)->withHeaders([
            'X-Goog-Api-Key'  => $apiKey,
            'X-Goog-FieldMask' => 'routes.distanceMeters,routes.duration',
        ])->acceptJson()->post('https://routes.googleapis.com/directions/v2:computeRoutes', [
            'origin' => [
                'location' => [
                    'latLng' => [
                        'latitude'  => (float) $origin['latitude'],
                        'longitude' => (float) $origin['longitude'],
                    ],
                ],
            ],
            'destination' => [
                'location' => [
                    'latLng' => [
                        'latitude'  => (float) $destination['latitude'],
                        'longitude' => (float) $destination['longitude'],
                    ],
                ],
            ],
            'travelMode'       => 'DRIVE',
            'routingPreference' => 'TRAFFIC_UNAWARE',
            'units'            => 'METRIC',
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Route could not be calculated.');
        }

        $route           = $response->json('routes.0');
        $distanceMeters  = (int) ($route['distanceMeters'] ?? 0);
        $durationSeconds = $this->durationToSeconds((string) ($route['duration'] ?? '0s'));

        if ($distanceMeters <= 0 || $durationSeconds <= 0) {
            throw new RuntimeException('Route could not be calculated.');
        }

        return [
            'distance_km'  => round($distanceMeters / 1000, 1),
            'duration_min' => (int) ceil($durationSeconds / 60),
        ];
    }

    private function durationToSeconds(string $duration): int
    {
        if (! preg_match('/^(\d+)s$/', $duration, $matches)) {
            return 0;
        }

        return (int) $matches[1];
    }
}
