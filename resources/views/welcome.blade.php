@php
    $isEnglish = request('lang') === 'en';
    $homeUrl = $isEnglish ? url('/?lang=en') : url('/');
    $languageUrl = $isEnglish ? url('/') : url('/?lang=en');
    $rideUrl = fn (string $type) => url('/').'?'.http_build_query(($isEnglish ? ['lang' => 'en'] : []) + ['ride' => $type]).'#booking';
    $description = $isEnglish
        ? 'Book a taxi in advance with Husso Taxi. Check the price for airport transfers, city rides and longer trips.'
        : 'Reserveer je taxi vooraf bij Husso Taxi. Bekijk de prijs voor luchthavenvervoer, stadsritten en lange afstanden.';
    $services = [
        ['airport', 'Luchthavenvervoer', 'Airport transfers', 'Van en naar Schiphol en andere luchthavens.', 'To and from Schiphol and other airports.'],
        ['standard', 'Stadsritten', 'City rides', 'Van het station of hotel naar je afspraak, of na een avond uit naar huis.', 'From the station or hotel to your appointment, or home after an evening out.'],
        ['business', 'Zakelijke ritten', 'Business rides', 'Voor afspraken, kantoorritten en het ophalen van gasten.', 'For meetings, office trips and guest pickups.'],
        ['long_distance', 'Lange afstand', 'Long-distance rides', 'Reis buiten de stad rechtstreeks naar je bestemming.', 'Travel straight to your destination outside the city.'],
    ];
    $serviceDetails = [
        'airport' => $isEnglish ? 'Enter your pickup address and airport to see a fare estimate. Contact us to arrange a separate return pickup.' : 'Vul je ophaaladres en luchthaven in voor een prijsindicatie. Neem contact op om een terugrit af te spreken.',
        'standard' => $isEnglish ? 'Pick an address, station or hotel as your starting point and choose when you want to leave.' : 'Kies een adres, station of hotel als vertrekpunt en geef aan hoe laat je opgehaald wilt worden.',
        'business' => $isEnglish ? 'Reserve a ride to a meeting or arrange a pickup for a guest. Select the departure time and leave a contact number.' : 'Reserveer vervoer naar een afspraak of laat een gast ophalen. Kies een ophaaltijd en laat een bereikbaar telefoonnummer achter.',
        'long_distance' => $isEnglish ? 'Enter the full departure and destination addresses to see the estimated fare for a longer trip.' : 'Vul voor een langere rit het volledige vertrek- en bestemmingsadres in om de geschatte prijs te zien.',
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
        <link rel="preload" href="{{ asset('fonts/instrument-serif-latin.woff2') }}" as="font" type="font/woff2" crossorigin>
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
            <div class="taxi-opening">
                <x-husso.hero :is-english="$isEnglish" :ride-url="$rideUrl" />
                <div class="husso-wrap taxi-booking-anchor">
                    <div class="taxi-booking-intro">
                        <h2>{{ $isEnglish ? 'Plan your ride.' : 'Plan je rit.' }}</h2>
                        <p>{{ $isEnglish ? 'Choose your pickup and destination to see an estimated fare.' : 'Kies je vertrekpunt en bestemming om de geschatte ritprijs te zien.' }}</p>
                        <div class="taxi-booking-help">
                            <h3>{{ $isEnglish ? 'Need a return trip?' : 'Ook een terugrit nodig?' }}</h3>
                            <a href="{{ route('contact', $isEnglish ? ['lang' => 'en'] : []) }}">{{ $isEnglish ? 'Arrange your return trip' : 'Regel je terugrit' }} <span class="husso-arrow" aria-hidden="true"></span></a>
                        </div>
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
            </div>

            <section class="site-services" id="services" aria-labelledby="services-title">
                <div class="husso-wrap service-layout">
                    <div>
                        <h2 id="services-title">{{ $isEnglish ? 'Our taxi services' : 'Onze taxidiensten' }}</h2>
                        <p>{{ $isEnglish ? 'Questions about a ride?' : 'Een vraag over je rit?' }} <a href="{{ route('contact', $isEnglish ? ['lang' => 'en'] : []) }}">Contact</a></p>
                    </div>
                    <ul class="service-list" role="list">
                        @foreach ($services as [$type, $labelNl, $labelEn, $textNl, $textEn])
                            <li x-data>
                                <a
                                    href="{{ $rideUrl($type) }}"
                                    aria-haspopup="dialog"
                                    @click="if ($refs.service.showModal && !$event.metaKey && !$event.ctrlKey && !$event.shiftKey && !$event.altKey) { $event.preventDefault(); $refs.service.showModal(); }"
                                >
                                    <span>
                                        <strong>{{ $isEnglish ? $labelEn : $labelNl }}</strong>
                                        <span>{{ $isEnglish ? $textEn : $textNl }}</span>
                                    </span>
                                    <span class="husso-arrow" aria-hidden="true"></span>
                                </a>
                                <dialog x-ref="service" class="taxi-service-dialog" aria-labelledby="service-{{ $type }}"
                                    @click.self="$refs.service.close()">
                                    <div>
                                        <button type="button" class="taxi-service-close" @click="$refs.service.close()" aria-label="{{ $isEnglish ? 'Close' : 'Sluiten' }}">
                                            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg>
                                        </button>
                                        <h3 id="service-{{ $type }}">{{ $isEnglish ? $labelEn : $labelNl }}</h3>
                                        <p>{{ $serviceDetails[$type] }}</p>
                                        <a class="husso-button husso-button--primary" href="{{ $rideUrl($type) }}"
                                            @click="$refs.service.close(); chooseRideType($event, @js($type))">
                                            {{ $isEnglish ? 'Book this ride' : 'Boek deze rit' }}
                                            <span class="husso-arrow" aria-hidden="true"></span>
                                        </a>
                                    </div>
                                </dialog>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </section>

            <section class="taxi-story" id="about">
                <div class="husso-wrap taxi-story-inner">
                    <img src="{{ asset('images/hero-city-768.webp') }}" width="768" height="512" alt="{{ $isEnglish ? 'Illustration of a city ride' : 'Illustratie van een stadsrit' }}" loading="lazy" decoding="async">
                    <div>
                        <h2>{{ $isEnglish ? 'From your door to your destination.' : 'Van je voordeur tot je bestemming.' }}</h2>
                        <p>{{ $isEnglish ? 'Book ahead for Schiphol, a city ride or a longer trip. See the estimate before you book and follow your request with the link on your confirmation page.' : 'Reserveer vooraf voor Schiphol, een stadsrit of een langere afstand. Bekijk de prijsindicatie en volg je aanvraag via de link op de bevestigingspagina.' }}</p>
                        <a class="husso-button husso-button--inverse" href="#booking">{{ $isEnglish ? 'Book a ride' : 'Boek een rit' }}</a>
                    </div>
                </div>
            </section>

            <div class="husso-wrap taxi-information">
                <section class="taxi-text-section taxi-faq" id="faq">
                    <h2>{{ $isEnglish ? 'Frequently asked questions' : 'Veelgestelde vragen' }}</h2>
                    <div>
                    <details><summary>{{ $isEnglish ? 'How much does a ride cost?' : 'Wat kost mijn rit?' }}</summary><p>{{ $isEnglish ? 'Enter your route to see an estimate before booking.' : 'Vul je route in om vóór het boeken een prijsindicatie te bekijken.' }}</p></details>
                    <details><summary>{{ $isEnglish ? 'How do I arrange a return trip?' : 'Hoe regel ik een retourrit?' }}</summary><p>{{ $isEnglish ? 'Contact us to arrange your return pickup and price.' : 'Neem contact op om de ophaaltijd en prijs voor je terugrit af te spreken.' }}</p></details>
                    <details><summary>{{ $isEnglish ? 'Can I pay in the taxi?' : 'Kan ik in de taxi betalen?' }}</summary><p>{{ $isEnglish ? 'Yes. Choose “Book, pay later” when you confirm your booking.' : 'Ja. Kies bij het bevestigen voor ‘Boek, betaal later’.' }}</p></details>
                    <details><summary>{{ $isEnglish ? 'Can I book for more than five people?' : 'Kan ik voor meer dan vijf personen boeken?' }}</summary><p>{{ $isEnglish ? 'Contact us to arrange an extra taxi.' : 'Neem contact op om een extra taxi te regelen.' }}</p></details>
                    </div>
                </section>
            </div>

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
        <x-husso.whatsapp :icon-only="true" class="husso-quick-chat" />

    </body>
</html>
