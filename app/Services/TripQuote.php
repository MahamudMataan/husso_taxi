<?php

namespace App\Services;

use App\Models\FareSetting;
use Carbon\CarbonImmutable;
use RuntimeException;

class TripQuote
{
    public function __construct(
        private readonly GoogleRouteEstimator $routes,
        private readonly GooglePlaceLookup $places,
        private readonly BookingPriceCalculator $prices,
    ) {
    }

    public function estimate(array $origin, array $destination, ?string $date, ?string $time, string $language): array
    {
        $pickup = $this->verifiedPlace($origin, $language);
        $arrival = $this->verifiedPlace($destination, $language);
        $fixedFare = null;
        if ($date && $time && $pickup && $arrival && $this->isSchipholTerminal($destination)
            && $this->isAirportPlace($arrival)
            && $this->hasLocality($pickup, ['eindhoven', 'den bosch', "'s-hertogenbosch", '’s-hertogenbosch'])) {
            $departure = CarbonImmutable::createFromFormat('!Y-m-d H:i', "$date $time", 'Europe/Amsterdam');
            $minute = $departure->hour * 60 + $departure->minute;
            $peak = $departure->isWeekday() && (($minute >= 420 && $minute < 540) || ($minute >= 960 && $minute < 1080));
            $fixedFare = $peak ? 21900 : 19900;
        }

        $route = $this->routes->estimate($origin, $destination);
        $minimum = (int) (FareSetting::find(1)?->minimum_cents ?? BookingPriceCalculator::MINIMUM_CENTS);

        return $route + $this->prices->breakdown($route['distance_km'], $route['duration_min'], $route['distance_meters'], $minimum, $fixedFare);
    }

    private function verifiedPlace(array $endpoint, string $language): ?array
    {
        if (empty($endpoint['place_id'])) {
            return null;
        }
        $place = $this->places->find($endpoint['place_id'], $language);
        if (! isset($place['location']['latitude'], $place['location']['longitude'])) {
            throw new RuntimeException('Unable to verify the selected address.');
        }
        if (abs((float) $endpoint['latitude'] - $place['location']['latitude']) > 0.0001
            || abs((float) $endpoint['longitude'] - $place['location']['longitude']) > 0.0001
            || (isset($endpoint['address']) && $endpoint['address'] !== ($place['formattedAddress'] ?? data_get($place, 'displayName.text')))) {
            throw new RuntimeException('The chosen address no longer matches the route.');
        }

        return $place;
    }

    private function isSchipholTerminal(array $destination): bool
    {
        return abs((float) $destination['latitude'] - 52.3095) <= 0.008
            && abs((float) $destination['longitude'] - 4.7622) <= 0.013;
    }

    private function hasLocality(array $place, array $names): bool
    {
        foreach ($place['addressComponents'] ?? [] as $component) {
            if (array_intersect($component['types'] ?? [], ['locality', 'postal_town'])
                && in_array(mb_strtolower($component['longText'] ?? ''), $names, true)) {
                return true;
            }
        }

        return false;
    }

    private function isAirportPlace(array $place): bool
    {
        $name = mb_strtolower((string) data_get($place, 'displayName.text'));
        if (preg_match('/parking|hotel|cargo|parkeren|\bp[1-6]\b/u', $name)) {
            return false;
        }

        return in_array('airport', $place['types'] ?? [], true)
            || in_array($name, ['schiphol', 'amsterdam airport schiphol', 'luchthaven schiphol'], true)
            || preg_match('/\b(departures|arrivals|vertrekhal|aankomsthal|terminal)\b/u', $name) === 1;
    }
}
