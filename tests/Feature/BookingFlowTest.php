<?php

namespace Tests\Feature;

use App\Jobs\SendBookingNotification;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class BookingFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(now()->setDate(2026, 11, 14)->startOfDay());
        config(['services.mollie.key' => 'test_fake_key']);
        Http::fake([
            'places.googleapis.com/v1/places/test_booking_pickup*' => Http::response([
                'formattedAddress' => 'Wegastraat, Nederland',
                'location' => ['latitude' => 52.1, 'longitude' => 5.1],
            ]),
            'places.googleapis.com/v1/places/test_booking_destination*' => Http::response([
                'formattedAddress' => 'Schiphol Airport, Amsterdam',
                'location' => ['latitude' => 52.3105, 'longitude' => 4.7683],
            ]),
        ]);
        Http::preventStrayRequests();
    }

    public function test_homepage_booking_uses_google_route_pricing_without_manual_distance_fields(): void
    {
        config(['services.google_maps.key' => 'fake-google-key']);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Volledige naam');
        $response->assertSee('E-mail voor bevestiging');
        $response->assertSee('Uitrijkosten (aanvulling minimumritprijs)');
        $response->assertDontSee('€ 2,50/km');
        $response->assertSee('Bereken route en prijs');
        $response->assertSee('Boeking controleren');
        $response->assertSee('Boekingsoverzicht');
        $response->assertSee('Contant in de taxi');
        $response->assertSee('Pinnen in de taxi');
        $response->assertSee('Verder naar iDEAL');
        $response->assertSee('Reserveer je taxi vooraf.');
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
        $response->assertSee('Minimum fare adjustment');
        $response->assertDontSee('€ 2.50/km');
        $response->assertSee('Calculate route and price');
        $response->assertSee('Cash in the taxi');
        $response->assertSee('Continue to iDEAL');
        $response->assertSee('Book your taxi in advance.');
        $response->assertSee('Our taxi services');
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
                'quoted_amount' => 56.51,
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
                        'distanceMeters' => 55001,
                        'duration' => '1560s',
                    ],
                ],
            ]),
        ]);

        $response = $this
            ->from('/#booking')
            ->post(route('bookings.pay-later.store'), $this->bookingPayload(['amount' => '155.02']));

        $response->assertRedirect('/#booking');
        $response->assertSessionHas('payment_success');
        $response->assertSessionHas('booking_tracking_url');

        $this->assertDatabaseHas('bookings', [
            'customer_email' => 'klant@example.nl',
            'payment_method' => 'pay_later',
            'payment_status' => 'pay_later',
            'base_fare' => 4.00,
            'km_rate' => 2.50,
            'minute_rate' => 0.52,
            'quoted_amount' => 155.02,
        ]);
        Queue::assertPushed(SendBookingNotification::class, 2);
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
        config([
            'services.mollie.key' => 'live_fake_key',
            'services.mollie.test_amount' => 0.01,
            'services.google_maps.key' => 'fake-google-key',
        ]);

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
                && $payload['amount']['value'] === '56.51'
                && $payload['metadata']['customer_name'] === 'Test Klant'
                && $payload['metadata']['customer_email'] === 'klant@example.nl'
                && $payload['metadata']['pickup'] === 'Wegastraat, Nederland'
                && $payload['metadata']['destination'] === 'Schiphol Airport, Amsterdam'
                && (float) $payload['metadata']['distance_km'] === 12.3
                && (int) $payload['metadata']['duration_min'] === 26
                && $payload['metadata']['ride_date'] === '2026-11-19'
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
            'quoted_amount' => 56.51,
        ]);

        $booking = Booking::firstOrFail();
        $this->get(route('payments.mollie.return', ['booking' => $booking->reference]))
            ->assertRedirect(url('/#booking'))
            ->assertSessionHas('booking_tracking_url', $booking->trackingUrl())
            ->assertSessionMissing('payment_success');
        $this->assertSame('pending', $booking->fresh()->payment_status);
    }

    public function test_mollie_webhook_only_updates_each_payment_status_once(): void
    {
        config(['services.mollie.key' => 'test_fake_key', 'services.google_maps.key' => 'fake-google-key']);
        Queue::fake();
        Http::fake([
            'routes.googleapis.com/directions/v2:computeRoutes' => Http::response([
                'routes' => [['distanceMeters' => 12340, 'duration' => '1560s']],
            ]),
            'api.mollie.com/v2/payments' => Http::response([
                'id' => 'tr_status_test',
                '_links' => ['checkout' => ['href' => 'https://www.mollie.com/checkout/status-test']],
            ], 201),
            'api.mollie.com/v2/payments/tr_status_test' => Http::sequence()
                ->push(['status' => 'canceled'])
                ->push(['status' => 'canceled'])
                ->push(['status' => 'paid'])
                ->push(['status' => 'paid'])
                ->push(['status' => 'failed']),
        ]);
        $this->post(route('payments.mollie.store'), $this->bookingPayload())->assertRedirect();
        $booking = Booking::firstOrFail();
        $webhook = route('payments.mollie.webhook');

        foreach (['canceled', 'canceled', 'paid', 'paid', 'paid'] as $expectedStatus) {
            $this->post($webhook, ['id' => 'tr_status_test'])->assertOk();
            $this->assertSame($expectedStatus, $booking->fresh()->payment_status);
        }

        Queue::assertPushed(SendBookingNotification::class, fn ($job) => $job->event === 'payment_failed');
        Queue::assertPushed(SendBookingNotification::class, fn ($job) => $job->event === 'payment_received');
        Queue::assertPushed(SendBookingNotification::class, 4);
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
            'pickup_place_id' => 'test_booking_pickup',
            'destination' => 'Schiphol Airport, Amsterdam',
            'destination_latitude' => 52.3105000,
            'destination_longitude' => 4.7683000,
            'destination_place_id' => 'test_booking_destination',
            'ride_date' => '2026-11-19',
            'ride_time' => '14:30',
            'phone' => '+31 6 86463268',
            'passengers' => '1',
            'ride_type' => 'standard',
            'amount' => '56.51',
            'request_token' => (string) Str::uuid(),
        ], $overrides);
    }

    public function test_schiphol_quote_uses_verified_places_and_peak_pickup_time(): void
    {
        config(['services.google_maps.key' => 'fake-google-key']);
        $origin = ['latitude' => 51.4416, 'longitude' => 5.4697, 'place_id' => 'city_pickup'];
        $destination = ['latitude' => 52.3095, 'longitude' => 4.7622, 'place_id' => 'airport_departures'];
        Http::preventStrayRequests();
        Http::fake([
            'places.googleapis.com/v1/places/city_pickup*' => Http::response([
                'location' => $origin,
                'formattedAddress' => 'Stationsplein, Eindhoven',
                'addressComponents' => [['types' => ['locality'], 'longText' => 'Eindhoven']],
            ]),
            'places.googleapis.com/v1/places/airport_departures*' => Http::response([
                'location' => $destination,
                'formattedAddress' => 'Vertrekhal, Schiphol',
                'displayName' => ['text' => 'Vertrekhal 1'],
            ]),
            'routes.googleapis.com/directions/v2:computeRoutes' => Http::response([
                'routes' => [['distanceMeters' => 120000, 'duration' => '4500s']],
            ]),
        ]);

        foreach ([
            ['2026-11-19', '06:59', 199],
            ['2026-11-19', '07:00', 219],
            ['2026-11-19', '09:00', 199],
            ['2026-11-19', '16:00', 219],
            ['2026-11-19', '18:00', 199],
            ['2026-11-21', '08:00', 199],
        ] as [$date, $time, $price]) {
            $this->postJson(route('route-estimate.store'), [
                'origin' => $origin, 'destination' => $destination, 'ride_date' => $date, 'ride_time' => $time,
            ])->assertOk()->assertJsonPath('quoted_amount', $price)->assertJsonPath('fare_type', 'schiphol_fixed');
        }

        $this->postJson(route('route-estimate.store'), [
            'origin' => $origin, 'destination' => [...$destination, 'latitude' => 52.3115],
            'ride_date' => '2026-11-19', 'ride_time' => '08:00',
        ])->assertStatus(422);
    }

    public function test_checkout_rejects_overcapacity_and_changed_price_without_creating_a_booking(): void
    {
        config(['services.google_maps.key' => 'fake-google-key']);
        Http::preventStrayRequests();
        Http::fake([
            'routes.googleapis.com/directions/v2:computeRoutes' => Http::response([
                'routes' => [['distanceMeters' => 2000, 'duration' => '300s']],
            ]),
        ]);

        $url = route('bookings.pay-later.store');
        $this->postJson($url, $this->bookingPayload(['passengers' => 6]))->assertUnprocessable()->assertJsonValidationErrors('passengers');
        $this->postJson($url, $this->bookingPayload(['amount' => '1.00']))->assertUnprocessable()->assertJsonValidationErrors('amount');
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_pin_booking_reuses_the_same_request_and_stores_optional_invoice(): void
    {
        config(['services.google_maps.key' => 'fake-google-key']);
        Queue::fake();
        Http::preventStrayRequests();
        Http::fake([
            'places.googleapis.com/v1/places/pickup*' => Http::response([
                'formattedAddress' => 'Wegastraat, Nederland',
                'location' => ['latitude' => 52.1, 'longitude' => 5.1],
            ]),
            'places.googleapis.com/v1/places/destination*' => Http::response([
                'formattedAddress' => 'Schiphol Airport, Amsterdam',
                'location' => ['latitude' => 52.3105, 'longitude' => 4.7683],
            ]),
            'routes.googleapis.com/directions/v2:computeRoutes' => Http::response([
                'routes' => [['distanceMeters' => 2000, 'duration' => '300s']],
            ]),
        ]);
        $payload = $this->bookingPayload([
            'pickup_place_id' => 'pickup',
            'destination_place_id' => 'destination',
            'request_token' => (string) Str::uuid(),
            'amount' => '20.00',
            'in_taxi_method' => 'pin',
            'passengers' => '5',
            'invoice_requested' => '1',
        ]);
        $url = route('bookings.pay-later.store');
        $this->postJson($url, $payload)->assertUnprocessable()->assertJsonValidationErrors('invoice_name');
        $payload += ['invoice_name' => 'Voorbeeld BV', 'invoice_address' => 'Teststraat 12', 'invoice_postcode' => '1000 AB', 'invoice_city' => 'Amsterdam'];
        $this->withSession(['_token' => 'stable-test-token'])->post($url, $payload)->assertRedirect('/#booking');
        $this->post($url, $payload)->assertRedirect('/#booking');
        $this->assertDatabaseCount('bookings', 1);
        $booking = Booking::firstOrFail();
        $this->assertSame('pin', $booking->payment_method);
        $this->assertSame(706, $booking->departure_supplement_cents);
        $this->assertTrue($booking->invoice_requested);
        $this->assertSame($payload['customer_email'], $booking->invoice_details['email']);
        Queue::assertPushed(SendBookingNotification::class, 2);
    }

    public function test_minimum_fare_can_be_changed_by_the_administrator_only(): void
    {
        $this->actingAs(\App\Models\User::factory()->create())
            ->patch(route('admin.fares.update'), ['minimum' => 22.50])->assertForbidden();
        $this->actingAs(\App\Models\User::factory()->create(['role' => 'admin']))
            ->patch(route('admin.fares.update'), ['minimum' => '22.50'])->assertRedirect();
        $this->assertDatabaseHas('fare_settings', ['id' => 1, 'minimum_cents' => 2250]);
    }
}
