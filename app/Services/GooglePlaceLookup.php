<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\ConnectionException;
use Throwable;

class GooglePlaceLookup
{
    public function find(string $id, string $language = 'nl'): ?array
    {
        $key = config('services.google_maps.key');
        if (! $key) {
            return null;
        }

        $fetch = function () use ($key, $id, $language): ?array {
            try {
                $result = Http::timeout(4)->connectTimeout(2)->withHeaders([
                    'X-Goog-Api-Key' => $key,
                    'X-Goog-FieldMask' => 'id,formattedAddress,location,displayName,addressComponents,types',
                ])->acceptJson()->get('https://places.googleapis.com/v1/places/'.rawurlencode($id), [
                    'languageCode' => $language,
                ]);
            } catch (ConnectionException) {
                return null;
            }
            if (! $result->successful()) {
                Log::warning('Google Places detail request failed.', ['status' => $result->status()]);

                return null;
            }

            return $result->json();
        };

        try {
            return Cache::remember('place-details:v2:'.hash('sha256', $language.':'.$id), now()->addDay(), $fetch);
        } catch (Throwable) {
            return $fetch();
        }
    }
}
