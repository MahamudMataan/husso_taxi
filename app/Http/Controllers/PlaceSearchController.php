<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class PlaceSearchController extends Controller
{
    public function autocomplete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'input' => ['required', 'string', 'min:2', 'max:255'],
            'language' => ['nullable', 'in:nl,en'],
            'session_token' => ['nullable', 'string', 'max:120'],
        ]);

        $apiKey = config('services.google_maps.key');

        if (! $apiKey) {
            return response()->json(['message' => 'Google Maps API key is not configured.'], 422);
        }

        $language = ($validated['language'] ?? 'nl') === 'en' ? 'en' : 'nl';
        $input = trim($validated['input']);
        $cacheKey = 'autocomplete:'.md5($language.':'.strtolower($input));

        $fetchSuggestions = function () use ($validated, $apiKey, $language, $input): ?array {
            $response = Http::timeout(5)->connectTimeout(3)->withHeaders([
                'X-Goog-Api-Key'   => $apiKey,
                'X-Goog-FieldMask' => 'suggestions.placePrediction.placeId,suggestions.placePrediction.text.text',
            ])->acceptJson()->post('https://places.googleapis.com/v1/places:autocomplete', [
                'input'               => $input,
                'includedRegionCodes' => ['nl'],
                'languageCode'        => $language,
                'sessionToken'        => $validated['session_token'] ?? null,
            ]);

            if (! $response->successful()) {
                Log::warning('Google Places autocomplete failed.', [
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);

                return null;
            }

            return $response->json('suggestions', []);
        };

        try {
            $rawSuggestions = Cache::remember($cacheKey, now()->addHour(), $fetchSuggestions);
        } catch (Throwable $exception) {
            Log::warning('Places autocomplete cache failed; fetching without cache.', [
                'message' => $exception->getMessage(),
            ]);

            $rawSuggestions = $fetchSuggestions();
        }

        if ($rawSuggestions === null) {
            return response()->json(['message' => 'Address search is unavailable.'], 502);
        }

        $suggestions = collect($rawSuggestions)
            ->map(function (array $suggestion): ?array {
                $prediction = $suggestion['placePrediction'] ?? null;

                if (! $prediction || ! isset($prediction['placeId'], $prediction['text']['text'])) {
                    return null;
                }

                return [
                    'place_id' => $prediction['placeId'],
                    'text' => $prediction['text']['text'],
                ];
            })
            ->filter()
            ->take(6)
            ->values();

        return response()->json([
            'suggestions' => $suggestions,
        ]);
    }

    public function details(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'place_id' => ['required', 'string', 'max:255'],
            'language' => ['nullable', 'in:nl,en'],
        ]);

        $apiKey = config('services.google_maps.key');

        if (! $apiKey) {
            return response()->json(['message' => 'Google Maps API key is not configured.'], 422);
        }

        $language = ($validated['language'] ?? 'nl') === 'en' ? 'en' : 'nl';
        $cacheKey = 'place-details:'.md5($language.':'.$validated['place_id']);

        $fetchPlace = function () use ($validated, $apiKey, $language): ?array {
            $response = Http::timeout(3)->connectTimeout(1)->withHeaders([
                'X-Goog-Api-Key' => $apiKey,
                'X-Goog-FieldMask' => 'id,formattedAddress,location,displayName',
            ])->acceptJson()->get('https://places.googleapis.com/v1/places/'.$validated['place_id'], [
                'languageCode' => $language,
            ]);

            if (! $response->successful()) {
                Log::warning('Google Place details failed.', [
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);

                return null;
            }

            return $response->json();
        };

        try {
            $place = Cache::remember($cacheKey, now()->addDay(), $fetchPlace);
        } catch (Throwable $exception) {
            Log::warning('Place details cache failed; fetching without cache.', [
                'message' => $exception->getMessage(),
            ]);

            $place = $fetchPlace();
        }

        if (! $place) {
            return response()->json(['message' => 'Place details could not be loaded.'], 422);
        }

        return response()->json([
            'address' => data_get($place, 'formattedAddress') ?: data_get($place, 'displayName.text'),
            'location' => [
                'latitude' => data_get($place, 'location.latitude'),
                'longitude' => data_get($place, 'location.longitude'),
            ],
        ]);
    }
}
