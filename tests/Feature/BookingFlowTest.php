<?php

namespace Tests\Feature;

use App\Jobs\SendBookingNotification;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class BookingFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_booking_uses_google_route_pricing_without_manual_distance_fields(): void
    {
        config(['services.google_maps.key' => 'fake-google-key']);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Volledige naam');
        $response->assertSee('E-mail voor bevestiging');
        $response->assertSee('Geschatte prijs');
        $response->assertSee('Bereken route en prijs');
        $response->assertSee('Boeking controleren');
        $response->assertSee('Boeking overzicht');
        $response->assertSee('Boek, betaal later');
        $response->assertSee('Betaal nu met iDEAL');
        $response->assertSee('Taxi nodig? Betaalbaar, betrouwbaar en bereikbaar.');
        $response->assertSee('Gebruik mijn huidige locatie');
        $response->assertSee('Cookies en locatie');
        $response->assertSee('/places/autocomplete');
        $response->assertSee('/places/details');
        $response->assertSee('/route-estimate');
        $response->assertDontSee('fake-google-key');
        $response->assertDontSee('The Google Maps JavaScript API', false);
        $response->assertDontSee("google.maps.importLibrary('places')", false);
        $response->assertDontSee('PlaceAutocompleteElement', false);
        $response->assertDontSee('new google.maps.places.Autocomplete', false);
        $response->assertDontSee('DistanceMatrixService', false);
        $response->assertDontSee('libraries=places&callback', false);
        $response->assertDontSee('Afstand km');
        $response->assertDontSee('Minuten');
        $response->assertDontSee('Per km');
        $response->assertDontSee('Per minuut');
        $response->assertDontSee('handmatig');
        $response->assertDontSee('activeSlide');
    }

    public function test_english_homepage_booking_uses_pay_now_and_pay_later_copy(): void
    {
        config(['services.google_maps.key' => 'fake-google-key']);

        $response = $this->get('/?lang=en');

        $response->assertOk();
        $response->assertSee('Full name');
        $response->assertSee('Approximate price');
        $response->assertSee('Calculate route and price');
        $response->assertSee('Book, pay later');
        $response->assertSee('Pay now with iDEAL');
        $response->assertSee('Need a taxi? Affordable, reliable, and reachable.');
        $response->assertSee('Choose the ride that fits your day.');
        $response->assertDontSee('Distance km');
        $response->assertDontSee('Per minute');
        $response->assertDontSee('manually');
    }

    public function test_route_estimate_uses_google_routes_api(): void
    {
        config(['services.google_maps.key' => 'fake-google-key']);

        Http::fake([
            'routes.googleapis.com/directions/v2:computeRoutes' => Http::response([
                'routes' => [
                    [
                        'distanceMeters' => 12340,
                        'duration' => '1560s',
                    ],
                ],
            ]),
        ]);

        $response = $this->postJson(route('route-estimate.store'), [
            'origin' => [
                'latitude' => 52.3702,
                'longitude' => 4.8952,
            ],
            'destination' => [
                'latitude' => 52.3105,
                'longitude' => 4.7683,
            ],
        ]);

        $response->assertOk()
            ->assertJson([
                'distance_km' => 12.3,
                'duration_min' => 26,
            ]);

        Http::assertSent(function ($request) {
            $payload = $request->data();

            return $request->url() === 'https://routes.googleapis.com/directions/v2:computeRoutes'
                && $request->hasHeader('X-Goog-Api-Key', 'fake-google-key')
                && $request->hasHeader('X-Goog-FieldMask', 'routes.distanceMeters,routes.duration')
                && $payload['travelMode'] === 'DRIVE'
                && $payload['origin']['location']['latLng']['latitude'] === 52.3702;
        });
    }

    public function test_places_autocomplete_uses_server_side_google_key(): void
    {
        config(['services.google_maps.key' => 'fake-google-key']);

        Http::fake([
            'places.googleapis.com/v1/places:autocomplete' => Http::response([
                'suggestions' => [
                    [
                        'placePrediction' => [
                            'placeId' => 'ChIJ123',
                            'text' => [
                                'text' => 'Wegastraat, Nederland',
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $response = $this->postJson(route('places.autocomplete'), [
            'input' => 'Wegastraat',
            'language' => 'nl',
            'session_token' => 'test-session',
        ]);

        $response->assertOk()
            ->assertJson([
                'suggestions' => [
                    [
                        'place_id' => 'ChIJ123',
                        'text' => 'Wegastraat, Nederland',
                    ],
                ],
            ]);

        Http::assertSent(function ($request) {
            $payload = $request->data();

            return $request->url() === 'https://places.googleapis.com/v1/places:autocomplete'
                && $request->hasHeader('X-Goog-Api-Key', 'fake-google-key')
                && $request->hasHeader('X-Goog-FieldMask', 'suggestions.placePrediction.placeId,suggestions.placePrediction.text.text')
                && $payload['input'] === 'Wegastraat'
                && $payload['includedRegionCodes'] === ['nl'];
        });
    }

    public function test_place_details_returns_address_and_location_without_exposing_key(): void
    {
        config(['services.google_maps.key' => 'fake-google-key']);

        Http::fake([
            'places.googleapis.com/v1/places/ChIJ123*' => Http::response([
                'formattedAddress' => 'Wegastraat, Nederland',
                'location' => [
                    'latitude' => 52.1,
                    'longitude' => 5.1,
                ],
            ]),
        ]);

        $response = $this->postJson(route('places.details'), [
            'place_id' => 'ChIJ123',
            'language' => 'nl',
        ]);

        $response->assertOk()
            ->assertJson([
                'address' => 'Wegastraat, Nederland',
                'location' => [
                    'latitude' => 52.1,
                    'longitude' => 5.1,
                ],
            ]);
    }

    public function test_pay_later_booking_requires_customer_name(): void
    {
        $response = $this
            ->from('/#booking')
            ->post(route('bookings.pay-later.store'), $this->bookingPayload([
                'customer_name' => null,
            ]));

        $response->assertSessionHasErrors('customer_name');
    }

    public function test_pay_later_booking_redirects_back_with_success(): void
    {
        Queue::fake();

        Http::fake([
            'routes.googleapis.com/directions/v2:computeRoutes' => Http::response([
                'routes' => [
                    [
                        'distanceMeters' => 12340,
                        'duration' => '1560s',
                    ],
                ],
            ]),
        ]);

        $response = $this
            ->from('/#booking')
            ->post(route('bookings.pay-later.store'), $this->bookingPayload());

        $response->assertRedirect('/#booking');
        $response->assertSessionHas('payment_success');

        $this->assertDatabaseHas('bookings', [
            'customer_email' => 'klant@example.nl',
            'payment_method' => 'pay_later',
            'payment_status' => 'pay_later',
            'quoted_amount' => 49.90,
        ]);
        Queue::assertPushed(SendBookingNotification::class, 1);
    }

    public function test_mollie_payment_requires_customer_name(): void
    {
        $response = $this
            ->from('/#booking')
            ->post(route('payments.mollie.store'), $this->bookingPayload([
                'customer_name' => null,
            ]));

        $response->assertSessionHasErrors('customer_name');
    }

    public function test_mollie_payment_creates_ideal_checkout_with_booking_metadata(): void
    {
        config(['services.mollie.key' => 'test_fake_key']);

        Http::fake([
            'routes.googleapis.com/directions/v2:computeRoutes' => Http::response([
                'routes' => [
                    [
                        'distanceMeters' => 12340,
                        'duration' => '1560s',
                    ],
                ],
            ]),
            'api.mollie.com/v2/payments' => Http::response([
                'id' => 'tr_test_payment',
                '_links' => [
                    'checkout' => [
                        'href' => 'https://www.mollie.com/checkout/test-payment',
                    ],
                ],
            ], 201),
        ]);

        $response = $this
            ->from('/#booking')
            ->post(route('payments.mollie.store'), $this->bookingPayload());

        $response->assertRedirect('https://www.mollie.com/checkout/test-payment');

        Http::assertSent(function ($request) {
            $payload = $request->data();

            return $request->url() === 'https://api.mollie.com/v2/payments'
                && $payload['method'] === 'ideal'
                && $payload['amount']['value'] === '49.90'
                && $payload['metadata']['customer_name'] === 'Test Klant'
                && $payload['metadata']['customer_email'] === 'klant@example.nl'
                && $payload['metadata']['pickup'] === 'Wegastraat, Nederland'
                && $payload['metadata']['destination'] === 'Schiphol Airport, Amsterdam'
                && (float) $payload['metadata']['distance_km'] === 12.3
                && (int) $payload['metadata']['duration_min'] === 26
                && $payload['metadata']['ride_date'] === '2026-05-26'
                && $payload['metadata']['ride_time'] === '14:30'
                && $payload['metadata']['phone'] === '+31 6 86463268'
                && $payload['metadata']['passengers'] === '1'
                && $payload['metadata']['ride_type'] === 'standard';
        });

        $this->assertDatabaseHas('bookings', [
            'customer_email' => 'klant@example.nl',
            'payment_method' => 'ideal',
            'payment_status' => 'pending',
            'mollie_payment_id' => 'tr_test_payment',
            'quoted_amount' => 49.90,
        ]);
    }

    private function bookingPayload(array $overrides = []): array
    {
        return array_merge([
            'language' => 'nl',
            'customer_name' => 'Test Klant',
            'customer_email' => 'klant@example.nl',
            'pickup' => 'Wegastraat, Nederland',
            'pickup_latitude' => 52.1000000,
            'pickup_longitude' => 5.1000000,
            'destination' => 'Schiphol Airport, Amsterdam',
            'destination_latitude' => 52.3105000,
            'destination_longitude' => 4.7683000,
            'ride_date' => '2026-05-26',
            'ride_time' => '14:30',
            'phone' => '+31 6 86463268',
            'passengers' => '1',
            'ride_type' => 'standard',
        ], $overrides);
    }
}
