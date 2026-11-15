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
    $onlinePayment = filled(config('services.mollie.key'));
    $paymentOptions = ($onlinePayment ? ['ideal' => 'iDEAL'] : []) + [
        'pin' => $isEnglish ? 'Debit card in the taxi' : 'Pinnen in de taxi',
        'cash' => $isEnglish ? 'Cash in the taxi' : 'Contant in de taxi',
    ];
    $bookingConfig = [
        'language' => $isEnglish ? 'en' : 'nl',
        'rideTypes' => $rideTypes,
        'requestedRide' => is_string($requestedRide) && isset($rideTypes[$requestedRide]) ? $requestedRide : null,
        'onlinePayment' => $onlinePayment,
        'requestToken' => old('request_token') ?: (string) \Illuminate\Support\Str::uuid(),
        'draft' => \Illuminate\Support\Arr::only(session()->getOldInput(), [
            'pickup', 'pickup_latitude', 'pickup_longitude',
            'destination', 'destination_latitude', 'destination_longitude',
            'customer_name', 'customer_email', 'phone', 'ride_date', 'ride_time', 'passengers', 'ride_type',
            'pickup_place_id', 'destination_place_id', 'in_taxi_method', 'invoice_requested', 'customer_notes',
        ]),
        'messages' => [
            'searching' => $isEnglish ? 'Searching for addresses…' : 'Adressen zoeken…',
            'selecting' => $isEnglish ? 'Loading address…' : 'Adres laden…',
            'results' => $isEnglish ? '{count} addresses found. Use the arrow keys to choose.' : '{count} adressen gevonden. Gebruik de pijltjestoetsen om te kiezen.',
            'noResults' => $isEnglish ? 'No addresses found. Try a street name and city.' : 'Geen adressen gevonden. Probeer een straatnaam met plaats.',
            'searchError' => $isEnglish ? 'Address search is unavailable. Try again or call us.' : 'Adres zoeken lukt even niet. Probeer opnieuw of bel ons.',
            'placeError' => $isEnglish ? 'This address could not be loaded. Choose it again or try another.' : 'Dit adres kon niet worden opgehaald. Kies het opnieuw of probeer een ander adres.',
            'chooseAddresses' => $isEnglish ? 'Choose both addresses from the suggestions before calculating the price.' : 'Kies beide adressen uit de suggesties om de prijs te berekenen.',
            'routeError' => $isEnglish ? 'We could not calculate this route. Check the addresses and try again.' : 'We konden deze route niet berekenen. Controleer de adressen en probeer opnieuw.',
            'pastDeparture' => $isEnglish ? 'Choose a pickup time in the future.' : 'Kies een ophaaltijd in de toekomst.',
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
        <link rel="preload" href="{{ asset('fonts/manrope-latin.woff2') }}" as="font" type="font/woff2" crossorigin>
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
            <x-husso.hero :is-english="$isEnglish" />
            <div class="husso-wrap taxi-booking-anchor">
                <div class="taxi-booking-intro">
                    <h2>{{ $isEnglish ? 'Book your taxi in advance.' : 'Reserveer je taxi vooraf.' }}</h2>
                    <p>{{ $isEnglish ? 'Choose your route and pickup time. See the total before sending your request.' : 'Kies je route en ophaaltijd. Bekijk het totaalbedrag voordat je je aanvraag verstuurt.' }}</p>
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

            <section class="taxi-story" id="electric">
                <div class="husso-wrap taxi-story-inner">
                    <img src="{{ asset('images/hero-electric-768.webp') }}" width="768" height="512" alt="{{ $isEnglish ? 'Illustrative electric vehicle at dusk' : 'Illustratie van een elektrische auto in de schemering' }}" loading="lazy" decoding="async">
                    <div>
                        <h2>{{ $isEnglish ? 'Electric travel' : 'Elektrisch rijden' }}</h2>
                        <p>{{ $isEnglish ? 'An electric motor makes less noise when pulling away. An electric car produces no exhaust emissions while driving.' : 'Een elektrische motor maakt minder geluid bij het wegrijden. Een elektrische auto stoot tijdens het rijden geen uitlaatgassen uit.' }}</p>
                        <a class="husso-button husso-button--plain" href="#booking">{{ $isEnglish ? 'Book a ride' : 'Boek een rit' }}</a>
                    </div>
                </div>
            </section>

            <section class="husso-wrap taxi-text-section" id="about">
                <h2>{{ $isEnglish ? 'Why Husso Taxi?' : 'Waarom Husso Taxi?' }}</h2>
                <p>{{ $isEnglish ? 'Book ahead for Schiphol, a city ride or a longer trip. See your price before you book and follow your reservation using the link in your email.' : 'Reserveer vooraf voor Schiphol, een stadsrit of een langere afstand. Je ziet je prijs vóór het boeken en volgt je ritaanvraag via de link in je e-mail.' }}</p>
            </section>

            <section class="husso-wrap taxi-text-section taxi-faq" id="faq">
                <h2>{{ $isEnglish ? 'Frequently asked questions' : 'Veelgestelde vragen' }}</h2>
                <details><summary>{{ $isEnglish ? 'How much does a ride cost?' : 'Wat kost mijn rit?' }}</summary><p>{{ $isEnglish ? 'Choose your route and pickup time to see the full price before booking.' : 'Vul je route en ophaaltijd in om de volledige prijs te zien voordat je boekt.' }}</p></details>
                <details><summary>{{ $isEnglish ? 'How do I arrange a return trip?' : 'Hoe regel ik een retourrit?' }}</summary><p>{{ $isEnglish ? 'Contact us to arrange your return pickup and price.' : 'Neem contact op om de ophaaltijd en prijs voor je terugrit af te spreken.' }}</p></details>
                <details><summary>{{ $isEnglish ? 'Can I pay in the taxi?' : 'Kan ik in de taxi betalen?' }}</summary><p>{{ $isEnglish ? 'Yes. Select cash or debit card when booking.' : 'Ja. Kies tijdens het boeken voor contant betalen of pinnen in de taxi.' }}</p></details>
                <details><summary>{{ $isEnglish ? 'Can I book for more than five people?' : 'Kan ik voor meer dan vijf personen boeken?' }}</summary><p>{{ $isEnglish ? 'Contact us to arrange an extra taxi.' : 'Neem contact op om een extra taxi te regelen.' }}</p></details>
            </section>

            <section class="taxi-contact-band" id="contact">
                <div class="husso-wrap taxi-contact-inner">
                    <h2>{{ $isEnglish ? 'Questions about your ride?' : 'Vragen over je rit?' }}</h2>
                    <div>
                        <x-husso.whatsapp :show-number="true" :inline="true" />
                        <a href="mailto:info@husso.nl">info@husso.nl</a>
                    </div>
                </div>
            </section>
        </main>

        <x-husso.footer :is-english="$isEnglish" :home-url="$homeUrl" />

    </body>
</html>
