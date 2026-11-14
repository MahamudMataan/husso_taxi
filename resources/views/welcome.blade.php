@php
    $isEnglish = request('lang') === 'en';
    $homeUrl = $isEnglish ? url('/?lang=en') : url('/');
    $languageUrl = $isEnglish ? url('/') : url('/?lang=en');
    $description = $isEnglish
        ? 'Book a taxi in advance with Husso Taxi. Check the price for airport transfers, city rides and longer trips.'
        : 'Reserveer je taxi vooraf bij Husso Taxi. Bekijk de prijs voor luchthavenvervoer, stadsritten en lange afstanden.';
    $services = [
        ['airport', 'Luchthavenvervoer', 'Airport transfers', 'Van en naar Schiphol en andere luchthavens.', 'To and from Schiphol and other airports.'],
        ['standard', 'Stadsritten', 'City rides', 'Van het station of hotel naar je afspraak, of na een avond uit naar huis.', 'From the station or hotel to your appointment, or home after an evening out.'],
        ['business', 'Zakelijke ritten', 'Business rides', 'Voor afspraken, kantoorritten en het ophalen van gasten.', 'For meetings, office trips and guest pickups.'],
        ['long_distance', 'Lange afstand', 'Long-distance rides', 'Reis buiten de stad rechtstreeks naar je bestemming.', 'Travel straight to your destination outside the city.'],
    ];
    $rideTypes = ['standard' => $isEnglish ? 'Standard taxi' : 'Standaard taxi']
        + array_column($services, $isEnglish ? 2 : 1, 0);
    $requestedRide = request('ride');
    $bookingConfig = [
        'language' => $isEnglish ? 'en' : 'nl',
        'rideTypes' => $rideTypes,
        'requestedRide' => is_string($requestedRide) && isset($rideTypes[$requestedRide]) ? $requestedRide : null,
        'draft' => \Illuminate\Support\Arr::only(session()->getOldInput(), [
            'pickup', 'pickup_latitude', 'pickup_longitude',
            'destination', 'destination_latitude', 'destination_longitude',
            'customer_name', 'customer_email', 'phone', 'ride_date', 'ride_time', 'passengers', 'ride_type',
        ]),
        'distanceRate' => \App\Services\BookingPriceCalculator::KM_RATE,
        'timeRate' => \App\Services\BookingPriceCalculator::MINUTE_RATE,
        'messages' => [
            'searching' => $isEnglish ? 'Searching for addresses…' : 'Adressen zoeken…',
            'selecting' => $isEnglish ? 'Loading address…' : 'Adres laden…',
            'results' => $isEnglish ? '{count} addresses found. Use the arrow keys to choose.' : '{count} adressen gevonden. Gebruik de pijltjestoetsen om te kiezen.',
            'noResults' => $isEnglish ? 'No addresses found. Try a street name and city.' : 'Geen adressen gevonden. Probeer een straatnaam met plaats.',
            'searchError' => $isEnglish ? 'Address search is unavailable. Try again or call us.' : 'Adres zoeken lukt even niet. Probeer opnieuw of bel ons.',
            'placeError' => $isEnglish ? 'This address could not be loaded. Choose it again or try another.' : 'Dit adres kon niet worden opgehaald. Kies het opnieuw of probeer een ander adres.',
            'chooseAddresses' => $isEnglish ? 'Choose both addresses from the suggestions before calculating the price.' : 'Kies beide adressen uit de suggesties om de prijs te berekenen.',
            'routeError' => $isEnglish ? 'We could not calculate this route. Check the addresses and try again.' : 'We konden deze route niet berekenen. Controleer de adressen en probeer opnieuw.',
            'locationPending' => $isEnglish ? 'Waiting for your location…' : 'Wachten op je locatie…',
            'locationError' => $isEnglish ? 'Your location is unavailable. Enter a pickup address instead.' : 'Je locatie is niet beschikbaar. Vul een ophaaladres in.',
            'currentLocation' => $isEnglish ? 'Current location' : 'Huidige locatie',
        ],
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ $isEnglish ? 'en' : 'nl' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $isEnglish ? 'Book a Taxi in Advance | Husso Taxi' : 'Taxi Vooraf Reserveren | Husso Taxi' }}</title>
        <x-husso.favicon />
        <meta name="description" content="{{ $description }}">
        <link rel="canonical" href="{{ $homeUrl }}">
        <link rel="alternate" hreflang="nl" href="{{ url('/') }}">
        <link rel="alternate" hreflang="en" href="{{ url('/?lang=en') }}">
        <link rel="preload" href="{{ asset('fonts/barlow-semi-condensed-700.woff2') }}" as="font" type="font/woff2" crossorigin>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body
        class="husso site-booking"
        x-data="booking(@js($bookingConfig))"
        @pageshow.window="submitting = false"
        data-autocomplete="{{ route('places.autocomplete') }}"
        data-place-details="{{ route('places.details') }}"
        data-estimate="{{ route('route-estimate.store') }}"
    >
        <a class="husso-skip" href="#booking">{{ $isEnglish ? 'Skip to booking' : 'Direct naar boeken' }}</a>
        <x-husso.header :is-english="$isEnglish" :home-url="$homeUrl" :language-url="$languageUrl" />

        <main id="content">
            <div class="husso-wrap booking-hero">
                <div class="booking-intro">
                    <h1>{{ $isEnglish ? 'Book your taxi in advance.' : 'Reserveer je taxi vooraf.' }}</h1>
                    <p>{{ $isEnglish
                        ? 'From Schiphol to your front door. Enter your route to see the price before you book.'
                        : 'Van Schiphol tot aan je voordeur. Vul je route in en bekijk de prijs voordat je boekt.' }}</p>
                </div>

                <div class="booking-column">
                    @include('bookings.form')
                    <aside class="cookie-note" x-data="cookieNotice" x-show="visible" x-cloak aria-labelledby="cookie-title">
                        <div>
                            <h2 id="cookie-title">{{ $isEnglish ? 'Cookies and location' : 'Cookies en locatie' }}</h2>
                            <p>{{ $isEnglish
                                ? 'Your location is only requested when you use the location button. We remember when you dismiss this notice.'
                                : 'We vragen je locatie alleen via de locatieknop. We onthouden dat je deze melding hebt gelezen.' }}</p>
                        </div>
                        <button type="button" class="husso-button husso-button--plain" @click="dismiss()">{{ $isEnglish ? 'Got it' : 'Begrepen' }}</button>
                    </aside>
                </div>

                <figure class="booking-photo">
                    <img src="{{ asset('images/schiphol-platform.webp') }}" width="960" height="460" alt="{{ $isEnglish ? 'An aircraft at the gate at Schiphol' : 'Een vliegtuig aan de gate op Schiphol' }}">
                    <figcaption>
                        <span>Amsterdam Airport Schiphol</span>
                        <a href="https://commons.wikimedia.org/wiki/File:Amsterdam,_Airport,_Schiphol,_25.jpg">{{ $isEnglish ? 'Photo: Christo' : 'Foto: Christo' }}</a>
                    </figcaption>
                </figure>
            </div>

            <section class="site-services" id="services" aria-labelledby="services-title">
                <div class="husso-wrap service-layout">
                    <div>
                        <h2 id="services-title">{{ $isEnglish ? 'Our taxi services' : 'Onze taxidiensten' }}</h2>
                        <p>{{ $isEnglish ? 'Questions about a ride?' : 'Een vraag over je rit?' }} <a href="{{ route('contact', $isEnglish ? ['lang' => 'en'] : []) }}">Contact</a></p>
                    </div>
                    <ul class="service-list" role="list">
                        @foreach ($services as [$type, $labelNl, $labelEn, $textNl, $textEn])
                            <li>
                                <a
                                    href="{{ url('/') }}?{{ http_build_query(($isEnglish ? ['lang' => 'en'] : []) + ['ride' => $type]) }}#booking"
                                    @click="chooseRideType($event, @js($type))"
                                >
                                    <span>
                                        <strong>{{ $isEnglish ? $labelEn : $labelNl }}</strong>
                                        <span>{{ $isEnglish ? $textEn : $textNl }}</span>
                                    </span>
                                    <span class="husso-arrow" aria-hidden="true"></span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </section>
        </main>

        <x-husso.footer :is-english="$isEnglish" :home-url="$homeUrl" />

        <div class="site-mobile-actions">
            <a class="husso-button husso-button--inverse" href="tel:+31686463268">{{ $isEnglish ? 'Call us' : 'Direct bellen' }}</a>
            <a class="husso-button husso-button--primary" href="#booking">{{ $isEnglish ? 'Book a ride' : 'Rit boeken' }}</a>
        </div>
    </body>
</html>
