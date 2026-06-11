@php
    $isEnglish = request('lang') === 'en';
    $homeUrl = $isEnglish ? url('/?lang=en') : url('/');
    $contactUrl = $isEnglish ? route('contact', ['lang' => 'en']) : route('contact');
    $languageUrl = $isEnglish ? url('/') : url('/?lang=en');
@endphp

<!DOCTYPE html>
<html lang="{{ $isEnglish ? 'en' : 'nl' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $isEnglish ? 'Husso Taxi | Reliable Private Taxi Booking' : 'Husso Taxi | Betrouwbare taxi reserveren' }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            [x-cloak] { display: none !important; }
            gmp-place-autocomplete { display: block; width: 100%; color-scheme: light; }
        </style>
    </head>
    <body class="bg-[#0d0d10] pb-20 font-sans text-zinc-950 antialiased md:pb-0" style="font-family: Inter, ui-sans-serif, system-ui, sans-serif;">
        <main class="min-h-screen overflow-hidden">
            <section class="relative min-h-screen bg-[radial-gradient(circle_at_16%_12%,rgba(250,204,21,0.42),transparent_34%),radial-gradient(circle_at_88%_8%,rgba(255,255,255,0.38),transparent_28%),linear-gradient(135deg,#141414_0%,#26200f_46%,#f8f4e8_100%)]">
                <div class="absolute inset-0 bg-[linear-gradient(90deg,rgba(0,0,0,0.76),rgba(0,0,0,0.42),rgba(255,255,255,0.08))]"></div>
                <img
                    src="{{ asset('images/husso-taxi-logo-transparent.png') }}"
                    alt="Husso Taxi logo"
                    class="absolute right-[-8rem] top-20 hidden rotate-3 opacity-[0.22] blur-[1px] md:block md:w-[30rem] lg:right-[-9rem] lg:top-14 lg:w-[44rem]"
                >
                <div class="absolute inset-x-0 bottom-0 h-44 bg-gradient-to-t from-[#f8f4e8] to-transparent"></div>

                <header class="relative z-10 mx-auto flex max-w-7xl items-center justify-between px-5 py-5 sm:px-8">
                    <a href="{{ $homeUrl }}" class="flex items-center gap-3">
                        <img src="{{ asset('images/husso-taxi-logo-transparent.png') }}" alt="Husso Taxi logo" class="h-12 w-12 object-contain drop-shadow-lg">
                        <span class="text-white">
                            <span class="block text-lg font-bold leading-none">Husso Taxi</span>
                            <span class="block text-xs font-medium text-zinc-300">{{ $isEnglish ? 'Airport & city transfers' : 'Luchthaven & stadsritten' }}</span>
                        </span>
                    </a>

                    <nav class="hidden items-center gap-8 text-sm font-medium text-zinc-200 md:flex">
                        <a href="#services" class="transition hover:text-yellow-300">{{ $isEnglish ? 'Services' : 'Diensten' }}</a>
                        <a href="#booking" class="transition hover:text-yellow-300">{{ $isEnglish ? 'Book now' : 'Boek nu' }}</a>
                        <a href="{{ $contactUrl }}" class="transition hover:text-yellow-300">Contact</a>
                    </nav>

                    <div class="flex items-center gap-3">
                        @if (Route::has('login'))
                            @auth
                                <a href="{{ url('/dashboard') }}" class="hidden rounded-md border border-white/25 bg-white/10 px-4 py-2 text-sm font-semibold text-white backdrop-blur transition hover:border-yellow-300 hover:text-yellow-300 sm:inline-flex">
                                    Dashboard
                                </a>
                            @else
                                <a href="{{ route('login') }}" class="hidden rounded-md border border-white/25 bg-white/10 px-4 py-2 text-sm font-semibold text-white backdrop-blur transition hover:border-yellow-300 hover:text-yellow-300 sm:inline-flex">
                                    {{ $isEnglish ? 'Admin / staff login' : 'Admin / medewerker login' }}
                                </a>
                            @endauth
                        @endif
                        <a href="{{ $languageUrl }}" class="inline-flex rounded-md border border-white/25 bg-white/10 px-3 py-2 text-sm font-bold text-white backdrop-blur transition hover:border-yellow-300 hover:text-yellow-300">
                            {{ $isEnglish ? 'Nederlands' : 'English' }}
                        </a>
                        <a href="tel:+31686463268" class="rounded-md bg-yellow-400/90 px-4 py-2 text-sm font-bold text-zinc-950 shadow-lg shadow-yellow-400/20 backdrop-blur transition hover:bg-yellow-300">
                            {{ $isEnglish ? 'Call Us' : 'Bel ons' }}
                        </a>
                    </div>
                </header>

                <div class="relative z-10 mx-auto grid max-w-7xl gap-10 px-5 pb-16 pt-8 sm:px-8 lg:grid-cols-[1.05fr_0.95fr] lg:items-center lg:pb-24 lg:pt-16">
                    <div class="max-w-3xl">
                        <div class="mb-5 inline-flex items-center gap-2 rounded-md border border-yellow-300/40 bg-yellow-300/10 px-3 py-2 text-sm font-semibold text-yellow-200 shadow-lg shadow-black/10 backdrop-blur-xl">
                            {{ $isEnglish ? '24/7 pre-booked taxi service' : '24/7 vooraf geboekte taxiservice' }}
                        </div>
                        <h1 class="text-5xl font-black leading-[0.95] text-white drop-shadow-2xl sm:text-6xl lg:text-7xl">
                            {{ $isEnglish ? 'Need a taxi? Affordable, reliable, and reachable.' : 'Taxi nodig? Betaalbaar, betrouwbaar en bereikbaar.' }}
                        </h1>
                        <p class="mt-6 max-w-2xl text-lg leading-8 text-zinc-100">
                            {{ $isEnglish ? 'Book airport transfers, business trips, local rides, and long-distance journeys with direct contact and clear prices.' : 'Boek luchthavenvervoer, zakelijke ritten, lokale taxiritten en lange afstanden met direct contact en heldere prijzen.' }}
                        </p>

                        <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                            <a href="#booking" class="inline-flex items-center justify-center rounded-md bg-yellow-400/90 px-6 py-4 text-base font-bold text-zinc-950 shadow-xl shadow-yellow-400/20 backdrop-blur transition hover:bg-yellow-300">
                                {{ $isEnglish ? 'Start booking' : 'Boeking starten' }}
                            </a>
                            <a href="#services" class="inline-flex items-center justify-center rounded-md border border-white/25 bg-white/10 px-6 py-4 text-base font-bold text-white shadow-lg shadow-black/10 backdrop-blur-xl transition hover:border-yellow-300 hover:text-yellow-200">
                                {{ $isEnglish ? 'View services' : 'Bekijk diensten' }}
                            </a>
                        </div>

                        <div class="mt-10 grid max-w-xl grid-cols-3 gap-3">
                            <div class="rounded-md border border-white/15 bg-white/10 p-4 shadow-lg shadow-black/10 backdrop-blur-xl">
                                <p class="text-2xl font-black text-yellow-300">15m</p>
                                <p class="mt-1 text-xs font-medium text-zinc-200">{{ $isEnglish ? 'average response' : 'gemiddelde reactie' }}</p>
                            </div>
                            <div class="rounded-md border border-white/15 bg-white/10 p-4 shadow-lg shadow-black/10 backdrop-blur-xl">
                                <p class="text-2xl font-black text-yellow-300">24/7</p>
                                <p class="mt-1 text-xs font-medium text-zinc-200">{{ $isEnglish ? 'airport pickup' : 'luchthaven pickup' }}</p>
                            </div>
                            <div class="rounded-md border border-white/15 bg-white/10 p-4 shadow-lg shadow-black/10 backdrop-blur-xl">
                                <p class="text-2xl font-black text-yellow-300">4.9</p>
                                <p class="mt-1 text-xs font-medium text-zinc-200">{{ $isEnglish ? 'customer rating' : 'klantbeoordeling' }}</p>
                            </div>
                        </div>
                    </div>

                    <section
                        id="booking"
                        class="rounded-lg border border-white/40 bg-white/[0.72] p-5 text-zinc-950 shadow-2xl shadow-black/20 backdrop-blur-2xl sm:p-6"
                        x-data="{
                            customerName: '',
                            customerEmail: '',
                            pickup: '',
                            destination: '',
                            date: '',
                            time: '',
                            phone: '',
                            passengers: '1',
                            rideType: 'standard',
                            distanceKm: '',
                            durationMin: '',
                            routeStatus: '',
                            bookingStep: 1,
                            pickupLocation: null,
                            destinationLocation: null,
                            placeSuggestions: { pickup: [], destination: [] },
                            placeLoading: { pickup: false, destination: false },
                            searchTimers: {},
                            searchControllers: {},
                            placeCache: {},
                            placeDetailsCache: {},
                            placeSessionToken: '',
                            cookieConsent: false,
                            baseFare: 0,
                            kmRate: 3.00,
                            minuteRate: 0.50,
                            initBooking() {
                                const now = new Date();
                                now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
                                this.date = now.toISOString().slice(0, 10);
                                this.time = now.toISOString().slice(11, 16);
                                this.cookieConsent = localStorage.getItem('husso_cookie_consent') === 'yes';
                                this.placeSessionToken = window.crypto?.randomUUID ? window.crypto.randomUUID() : `${Date.now()}-${Math.random()}`;
                                try {
                                    this.placeCache = JSON.parse(localStorage.getItem('husso_place_cache') || '{}');
                                    this.placeDetailsCache = JSON.parse(localStorage.getItem('husso_place_details_cache') || '{}');
                                } catch (error) {
                                    this.placeCache = {};
                                    this.placeDetailsCache = {};
                                }
                            },
                            estimateTotal() {
                                const km = Number(this.distanceKm) || 0;
                                const min = Number(this.durationMin) || 0;
                                return (km * this.kmRate) + (min * this.minuteRate);
                            },
                            money(value) {
                                return new Intl.NumberFormat('{{ $isEnglish ? 'en-NL' : 'nl-NL' }}', { style: 'currency', currency: 'EUR' }).format(value);
                            },
                            routeSummary() {
                                const km = Number(this.distanceKm) || 0;
                                const min = Number(this.durationMin) || 0;
                                if (!km && !min) {
                                    return '{{ $isEnglish ? 'Calculate route first.' : 'Bereken eerst de route.' }}';
                                }
                                return `${km.toFixed(1)} km - ${Math.round(min)} min`;
                            },
                            resetRoute() {
                                this.distanceKm = '';
                                this.durationMin = '';
                                this.routeStatus = '';
                                if (this.bookingStep === 3) {
                                    this.bookingStep = 1;
                                }
                            },
                            quoteReady() {
                                return Boolean(this.pickup && this.destination && this.distanceKm && this.durationMin);
                            },
                            detailsReady() {
                                return Boolean(this.customerName.trim() && this.customerEmail.trim() && this.date && this.time && this.phone.trim());
                            },
                            submitReady() {
                                return this.quoteReady() && this.detailsReady();
                            },
                            handleEnterKey(event) {
                                if (event.isComposing) return;
                                event.preventDefault();

                                if (this.bookingStep === 1) {
                                    this.quoteReady() ? this.bookingStep = 2 : this.calculateWithGoogle();
                                } else if (this.bookingStep === 2) {
                                    if (this.detailsReady()) this.bookingStep = 3;
                                } else if (this.bookingStep === 3 && this.submitReady()) {
                                    this.$refs.payLaterButton.click();
                                }
                            },
                            acceptCookies() {
                                localStorage.setItem('husso_cookie_consent', 'yes');
                                document.cookie = 'husso_cookie_consent=yes; Max-Age=31536000; SameSite=Lax; Path=/';
                                this.cookieConsent = true;
                            },
                            searchPlaces(type) {
                                const input = type === 'pickup' ? this.pickup : this.destination;

                                if (type === 'pickup') {
                                    this.pickupLocation = null;
                                } else {
                                    this.destinationLocation = null;
                                }
                                this.resetRoute();
                                clearTimeout(this.searchTimers[type]);

                                if (!input || input.trim().length < 2) {
                                    this.placeSuggestions[type] = [];
                                    return;
                                }

                                this.searchTimers[type] = setTimeout(async () => {
                                    const cacheKey = `${type}:${input.trim().toLowerCase()}`;
                                    if (this.placeCache[cacheKey]) {
                                        this.placeSuggestions[type] = this.placeCache[cacheKey];
                                        return;
                                    }

                                    if (this.searchControllers[type]) {
                                        this.searchControllers[type].abort();
                                    }

                                    this.searchControllers[type] = new AbortController();
                                    this.placeLoading[type] = true;
                                    try {
                                        const response = await fetch('{{ route('places.autocomplete') }}', {
                                            method: 'POST',
                                            signal: this.searchControllers[type].signal,
                                            headers: {
                                                'Accept': 'application/json',
                                                'Content-Type': 'application/json',
                                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                            },
                                            body: JSON.stringify({
                                                input,
                                                language: '{{ $isEnglish ? 'en' : 'nl' }}',
                                                session_token: this.placeSessionToken,
                                            }),
                                        });
                                        const data = await response.json();
                                        const suggestions = data.suggestions || [];
                                        this.placeCache[cacheKey] = suggestions;
                                        this.persistPlaceCache();
                                        this.placeSuggestions[type] = suggestions;
                                    } catch (error) {
                                        if (error.name !== 'AbortError') {
                                            this.placeSuggestions[type] = [];
                                        }
                                    } finally {
                                        this.placeLoading[type] = false;
                                    }
                                }, 80);
                            },
                            persistPlaceCache() {
                                const entries = Object.entries(this.placeCache).slice(-80);
                                localStorage.setItem('husso_place_cache', JSON.stringify(Object.fromEntries(entries)));
                            },
                            persistPlaceDetailsCache() {
                                const entries = Object.entries(this.placeDetailsCache).slice(-80);
                                localStorage.setItem('husso_place_details_cache', JSON.stringify(Object.fromEntries(entries)));
                            },
                            async selectPlace(type, suggestion) {
                                try {
                                    const detailCacheKey = `{{ $isEnglish ? 'en' : 'nl' }}:${suggestion.place_id}`;
                                    let data = this.placeDetailsCache[detailCacheKey];

                                    if (!data) {
                                        const response = await fetch('{{ route('places.details') }}', {
                                            method: 'POST',
                                            headers: {
                                                'Accept': 'application/json',
                                                'Content-Type': 'application/json',
                                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                            },
                                            body: JSON.stringify({
                                                place_id: suggestion.place_id,
                                                language: '{{ $isEnglish ? 'en' : 'nl' }}',
                                            }),
                                        });
                                        data = await response.json();

                                        if (!response.ok) {
                                            throw new Error(data.message || 'Place failed');
                                        }

                                        this.placeDetailsCache[detailCacheKey] = data;
                                        this.persistPlaceDetailsCache();
                                    }

                                    if (!data.location) {
                                        throw new Error(data.message || 'Place failed');
                                    }

                                    if (type === 'pickup') {
                                        this.pickup = data.address || suggestion.text;
                                        this.pickupLocation = data.location;
                                    } else {
                                        this.destination = data.address || suggestion.text;
                                        this.destinationLocation = data.location;
                                    }
                                    this.placeSuggestions[type] = [];
                                    this.resetRoute();

                                    if (this.pickupLocation && this.destinationLocation) {
                                        this.calculateWithGoogle();
                                    }
                                } catch (error) {
                                    this.routeStatus = '{{ $isEnglish ? 'Could not select this place. Try another suggestion.' : 'Deze plaats kon niet geselecteerd worden. Kies een andere suggestie.' }}';
                                }
                            },
                            useCurrentLocation() {
                                if (!navigator.geolocation) {
                                    this.routeStatus = '{{ $isEnglish ? 'Current location is not supported in this browser.' : 'Huidige locatie wordt niet ondersteund in deze browser.' }}';
                                    return;
                                }

                                this.routeStatus = '{{ $isEnglish ? 'Waiting for location permission...' : 'Wachten op locatietoestemming...' }}';
                                navigator.geolocation.getCurrentPosition((position) => {
                                    const latitude = Number(position.coords.latitude).toFixed(6);
                                    const longitude = Number(position.coords.longitude).toFixed(6);
                                    this.pickup = `{{ $isEnglish ? 'Current location' : 'Huidige locatie' }} (GPS: ${latitude}, ${longitude})`;
                                    this.pickupLocation = {
                                        latitude: position.coords.latitude,
                                        longitude: position.coords.longitude,
                                    };
                                    this.placeSuggestions.pickup = [];
                                    this.resetRoute();

                                    if (this.destinationLocation) {
                                        this.calculateWithGoogle();
                                    } else {
                                        this.routeStatus = '{{ $isEnglish ? 'Current location selected. Choose a destination.' : 'Huidige locatie geselecteerd. Kies nu een bestemming.' }}';
                                    }
                                }, () => {
                                    this.routeStatus = '{{ $isEnglish ? 'Location permission was denied.' : 'Locatietoestemming is geweigerd.' }}';
                                }, {
                                    enableHighAccuracy: true,
                                    timeout: 10000,
                                });
                            },
                            async calculateWithGoogle() {
                                if (!this.pickupLocation || !this.destinationLocation) {
                                    this.routeStatus = '{{ $isEnglish ? 'Choose pickup and destination from the Google suggestions first.' : 'Kies eerst ophaallocatie en bestemming uit de Google suggesties.' }}';
                                    return;
                                }

                                this.routeStatus = '{{ $isEnglish ? 'Calculating route...' : 'Route berekenen...' }}';

                                try {
                                    const response = await fetch('{{ route('route-estimate.store') }}', {
                                        method: 'POST',
                                        headers: {
                                            'Accept': 'application/json',
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                        },
                                        body: JSON.stringify({
                                            origin: this.pickupLocation,
                                            destination: this.destinationLocation,
                                        }),
                                    });
                                    const data = await response.json();

                                    if (!response.ok) {
                                        throw new Error(data.message || 'Route failed');
                                    }

                                    this.distanceKm = Number(data.distance_km).toFixed(1);
                                    this.durationMin = Number(data.duration_min);
                                    this.routeStatus = '{{ $isEnglish ? 'Route calculated with Google Routes.' : 'Route berekend met Google Routes.' }}';
                                    this.bookingStep = 2;
                                } catch (error) {
                                    this.distanceKm = '';
                                    this.durationMin = '';
                                    this.routeStatus = '{{ $isEnglish ? 'Could not calculate this route. Check Google Routes API and choose valid places.' : 'Route kon niet berekend worden. Controleer Google Routes API en kies geldige plaatsen.' }}';
                                }
                            }
                        }"
                        x-init="initBooking()"
                    >
                        <div class="mb-5 flex items-start justify-between gap-4">
                            <div>
                                <p class="text-sm font-bold uppercase text-yellow-700">{{ $isEnglish ? 'Instant quote request' : 'Direct rit aanvragen' }}</p>
                                <h2 class="mt-1 text-2xl font-black">{{ $isEnglish ? 'Book your ride' : 'Boek je rit' }}</h2>
                            </div>
                            <span class="rounded-md bg-zinc-950/90 px-3 py-2 text-sm font-bold text-yellow-300">{{ $isEnglish ? 'Secure' : 'Veilig' }}</span>
                        </div>

                        @if (session('payment_error'))
                            <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-700">
                                {{ session('payment_error') }}
                            </div>
                        @endif

                        @if (session('payment_success'))
                            <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm font-bold text-green-700">
                                {{ session('payment_success') }}
                            </div>
                        @endif

                        <form method="POST" action="{{ route('payments.mollie.store') }}" class="grid gap-4" x-ref="bookingForm" @keydown.enter="handleEnterKey($event)">
                            @csrf
                            <input type="hidden" name="language" value="{{ $isEnglish ? 'en' : 'nl' }}">
                            <input type="hidden" name="customer_name" :value="customerName">
                            <input type="hidden" name="customer_email" :value="customerEmail">
                            <input type="hidden" name="amount" :value="estimateTotal().toFixed(2)">
                            <input type="hidden" name="pickup" :value="pickup">
                            <input type="hidden" name="pickup_latitude" :value="pickupLocation?.latitude || ''">
                            <input type="hidden" name="pickup_longitude" :value="pickupLocation?.longitude || ''">
                            <input type="hidden" name="destination" :value="destination">
                            <input type="hidden" name="destination_latitude" :value="destinationLocation?.latitude || ''">
                            <input type="hidden" name="destination_longitude" :value="destinationLocation?.longitude || ''">
                            <input type="hidden" name="distance_km" :value="distanceKm">
                            <input type="hidden" name="duration_min" :value="durationMin">
                            <input type="hidden" name="ride_date" :value="date">
                            <input type="hidden" name="ride_time" :value="time">
                            <input type="hidden" name="phone" :value="phone">
                            <input type="hidden" name="passengers" :value="passengers">
                            <input type="hidden" name="ride_type" :value="rideType">

                            <div class="grid grid-cols-3 gap-2 rounded-lg border border-zinc-950/10 bg-white/55 p-1 text-xs font-black uppercase text-zinc-600">
                                <button type="button" @click="bookingStep = 1" class="rounded-md px-3 py-2 transition" :class="bookingStep === 1 ? 'bg-zinc-950 text-yellow-300' : 'hover:bg-white'">
                                    {{ $isEnglish ? 'Route' : 'Route' }}
                                </button>
                                <button type="button" @click="if (quoteReady()) bookingStep = 2" :disabled="!quoteReady()" class="rounded-md px-3 py-2 transition disabled:cursor-not-allowed disabled:opacity-40" :class="bookingStep === 2 ? 'bg-zinc-950 text-yellow-300' : 'hover:bg-white'">
                                    {{ $isEnglish ? 'When' : 'Wanneer' }}
                                </button>
                                <button type="button" @click="if (submitReady()) bookingStep = 3" :disabled="!submitReady()" class="rounded-md px-3 py-2 transition disabled:cursor-not-allowed disabled:opacity-40" :class="bookingStep === 3 ? 'bg-zinc-950 text-yellow-300' : 'hover:bg-white'">
                                    {{ $isEnglish ? 'Pay' : 'Betalen' }}
                                </button>
                            </div>

                            <label class="block" x-show="bookingStep === 2" x-cloak>
                                <span class="text-sm font-semibold text-zinc-700">{{ $isEnglish ? 'Full name' : 'Volledige naam' }}</span>
                                <input
                                    x-model="customerName"
                                    name="visible_customer_name"
                                    type="text"
                                    required
                                    autocomplete="name"
                                    placeholder="{{ $isEnglish ? 'Your full name' : 'Je volledige naam' }}"
                                    class="mt-1 w-full rounded-md border-white/60 bg-white/70 text-zinc-950 shadow-sm focus:border-yellow-500 focus:ring-yellow-500"
                                >
                            </label>

                            <label class="block" x-show="bookingStep === 2" x-cloak>
                                <span class="text-sm font-semibold text-zinc-700">{{ $isEnglish ? 'Email for confirmation' : 'E-mail voor bevestiging' }}</span>
                                <input
                                    x-model="customerEmail"
                                    name="visible_customer_email"
                                    type="email"
                                    required
                                    autocomplete="email"
                                    placeholder="info@example.nl"
                                    class="mt-1 w-full rounded-md border-white/60 bg-white/70 text-zinc-950 shadow-sm focus:border-yellow-500 focus:ring-yellow-500"
                                >
                            </label>

                            <div class="block" x-show="bookingStep === 1" x-cloak>
                                <span class="text-sm font-semibold text-zinc-700">{{ $isEnglish ? 'Pickup location' : 'Ophaallocatie' }}</span>
                                <div class="relative mt-1">
                                    <input
                                        x-model="pickup"
                                        @input="searchPlaces('pickup')"
                                        type="text"
                                        autocomplete="off"
                                        placeholder="{{ $isEnglish ? 'Street, hotel, airport terminal' : 'Straat, hotel of luchthaven terminal' }}"
                                        class="w-full rounded-md border-white/60 bg-white/70 text-zinc-950 shadow-sm focus:border-yellow-500 focus:ring-yellow-500"
                                    >
                                    <div x-show="placeSuggestions.pickup.length" x-cloak class="absolute z-30 mt-2 max-h-56 w-full overflow-auto rounded-md border border-zinc-950/10 bg-white p-2 shadow-2xl">
                                        <template x-for="suggestion in placeSuggestions.pickup" :key="suggestion.place_id">
                                            <button type="button" @click="selectPlace('pickup', suggestion)" class="block w-full rounded-md px-3 py-2 text-left text-sm font-semibold text-zinc-800 hover:bg-yellow-100" x-text="suggestion.text"></button>
                                        </template>
                                    </div>
                                </div>
                                <p x-show="placeLoading.pickup" x-cloak class="mt-1 text-xs font-semibold text-zinc-600">{{ $isEnglish ? 'Searching...' : 'Zoeken...' }}</p>
                                <button type="button" @click="useCurrentLocation()" class="mt-2 inline-flex w-full justify-center rounded-md border border-zinc-950/10 bg-white/60 px-3 py-2 text-sm font-black text-zinc-800 transition hover:bg-yellow-100">
                                    {{ $isEnglish ? 'Use my current location' : 'Gebruik mijn huidige locatie' }}
                                </button>
                            </div>

                            <div class="block" x-show="bookingStep === 1" x-cloak>
                                <span class="text-sm font-semibold text-zinc-700">{{ $isEnglish ? 'Destination' : 'Bestemming' }}</span>
                                <div class="relative mt-1">
                                    <input
                                        x-model="destination"
                                        @input="searchPlaces('destination')"
                                        type="text"
                                        autocomplete="off"
                                        placeholder="{{ $isEnglish ? 'Where should we take you?' : 'Waar moeten we je naartoe brengen?' }}"
                                        class="w-full rounded-md border-white/60 bg-white/70 text-zinc-950 shadow-sm focus:border-yellow-500 focus:ring-yellow-500"
                                    >
                                    <div x-show="placeSuggestions.destination.length" x-cloak class="absolute z-30 mt-2 max-h-56 w-full overflow-auto rounded-md border border-zinc-950/10 bg-white p-2 shadow-2xl">
                                        <template x-for="suggestion in placeSuggestions.destination" :key="suggestion.place_id">
                                            <button type="button" @click="selectPlace('destination', suggestion)" class="block w-full rounded-md px-3 py-2 text-left text-sm font-semibold text-zinc-800 hover:bg-yellow-100" x-text="suggestion.text"></button>
                                        </template>
                                    </div>
                                </div>
                                <p x-show="placeLoading.destination" x-cloak class="mt-1 text-xs font-semibold text-zinc-600">{{ $isEnglish ? 'Searching...' : 'Zoeken...' }}</p>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2" x-show="bookingStep === 2" x-cloak>
                                <label class="block">
                                    <span class="text-sm font-semibold text-zinc-700">{{ $isEnglish ? 'Date' : 'Datum' }}</span>
                                    <input x-model="date" type="date" class="mt-1 w-full rounded-md border-white/60 bg-white/70 text-zinc-950 shadow-sm focus:border-yellow-500 focus:ring-yellow-500">
                                </label>
                                <label class="block">
                                    <span class="text-sm font-semibold text-zinc-700">{{ $isEnglish ? 'Time' : 'Tijd' }}</span>
                                    <input x-model="time" type="time" class="mt-1 w-full rounded-md border-white/60 bg-white/70 text-zinc-950 shadow-sm focus:border-yellow-500 focus:ring-yellow-500">
                                </label>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2" x-show="bookingStep === 2" x-cloak>
                                <label class="block">
                                    <span class="text-sm font-semibold text-zinc-700">{{ $isEnglish ? 'Passengers' : 'Passagiers' }}</span>
                                    <select x-model="passengers" class="mt-1 w-full rounded-md border-white/60 bg-white/70 text-zinc-950 shadow-sm focus:border-yellow-500 focus:ring-yellow-500">
                                        <option value="1">{{ $isEnglish ? '1 passenger' : '1 passagier' }}</option>
                                        <option value="2">{{ $isEnglish ? '2 passengers' : '2 passagiers' }}</option>
                                        <option value="3">{{ $isEnglish ? '3 passengers' : '3 passagiers' }}</option>
                                        <option value="4">{{ $isEnglish ? '4 passengers' : '4 passagiers' }}</option>
                                        <option value="5+">{{ $isEnglish ? '5+ passengers' : '5+ passagiers' }}</option>
                                    </select>
                                </label>
                                <label class="block">
                                    <span class="text-sm font-semibold text-zinc-700">{{ $isEnglish ? 'Ride type' : 'Type rit' }}</span>
                                    <select x-model="rideType" class="mt-1 w-full rounded-md border-white/60 bg-white/70 text-zinc-950 shadow-sm focus:border-yellow-500 focus:ring-yellow-500">
                                        <option value="standard">{{ $isEnglish ? 'Standard taxi' : 'Standaard taxi' }}</option>
                                        <option value="airport">{{ $isEnglish ? 'Airport transfer' : 'Luchthavenvervoer' }}</option>
                                        <option value="business">{{ $isEnglish ? 'Business ride' : 'Zakelijke rit' }}</option>
                                        <option value="long_distance">{{ $isEnglish ? 'Long distance' : 'Lange afstand' }}</option>
                                    </select>
                                </label>
                            </div>

                            <label class="block" x-show="bookingStep === 2" x-cloak>
                                <span class="text-sm font-semibold text-zinc-700">{{ $isEnglish ? 'Phone number' : 'Telefoonnummer' }}</span>
                                <input x-model="phone" type="tel" required placeholder="+31 6 86463268" autocomplete="tel" class="mt-1 w-full rounded-md border-white/60 bg-white/70 text-zinc-950 shadow-sm focus:border-yellow-500 focus:ring-yellow-500">
                            </label>

                            <div class="rounded-lg border border-zinc-950/10 bg-zinc-950/[0.92] p-4 text-white shadow-xl shadow-black/10" x-show="bookingStep === 1 || bookingStep === 3" x-cloak>
                                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <p class="text-sm font-bold uppercase text-yellow-300">{{ $isEnglish ? 'Approximate price' : 'Geschatte prijs' }}</p>
                                        <p class="mt-1 text-4xl font-black" x-text="distanceKm && durationMin ? money(estimateTotal()) : '--'"></p>
                                        <p class="mt-2 text-sm text-zinc-300">{{ $isEnglish ? 'Formula: distance x EUR 3.00 + minutes x EUR 0.50.' : 'Formule: afstand x EUR 3,00 + minuten x EUR 0,50.' }}</p>
                                    </div>
                                    <div class="rounded-md bg-yellow-400 px-3 py-2 text-sm font-black text-zinc-950" x-text="routeSummary()"></div>
                                </div>

                                <button type="button" @click="calculateWithGoogle()" class="mt-4 w-full rounded-md border border-yellow-300/40 bg-yellow-300/10 px-4 py-3 text-sm font-black text-yellow-200 transition hover:bg-yellow-300 hover:text-zinc-950">
                                    {{ $isEnglish ? 'Calculate route and price' : 'Bereken route en prijs' }}
                                </button>
                                <p class="mt-3 text-xs text-zinc-400" x-text="routeStatus || '{{ $isEnglish ? 'Choose pickup and destination, then calculate the route.' : 'Kies ophaallocatie en bestemming en bereken daarna de route.' }}'"></p>
                            </div>

                            <div x-show="bookingStep === 2" x-cloak class="grid gap-3">
                                <button
                                    type="button"
                                    @click="if (detailsReady()) bookingStep = 3"
                                    :disabled="!detailsReady()"
                                    class="rounded-md bg-zinc-950 px-5 py-4 text-base font-black text-white shadow-lg shadow-black/20 transition hover:bg-zinc-800 disabled:cursor-not-allowed disabled:bg-zinc-500"
                                >
                                    {{ $isEnglish ? 'Review booking' : 'Boeking controleren' }}
                                </button>
                                <p class="text-xs font-medium text-zinc-600">
                                    {{ $isEnglish ? 'Add your name, email, phone number, date, and time before payment.' : 'Vul naam, e-mail, telefoonnummer, datum en tijd in voordat je betaalt.' }}
                                </p>
                            </div>

                            <div x-show="bookingStep === 3" x-cloak class="rounded-lg border border-zinc-950/10 bg-white/65 p-4 text-sm text-zinc-700">
                                <p class="font-black text-zinc-950">{{ $isEnglish ? 'Booking summary' : 'Boeking overzicht' }}</p>
                                <div class="mt-3 grid gap-2">
                                    <p><span class="font-bold">{{ $isEnglish ? 'Name:' : 'Naam:' }}</span> <span x-text="customerName || '-'"></span></p>
                                    <p><span class="font-bold">{{ $isEnglish ? 'Email:' : 'E-mail:' }}</span> <span x-text="customerEmail || '-'"></span></p>
                                    <p><span class="font-bold">{{ $isEnglish ? 'Pickup:' : 'Ophaal:' }}</span> <span x-text="pickup || '-'"></span></p>
                                    <p><span class="font-bold">{{ $isEnglish ? 'Destination:' : 'Bestemming:' }}</span> <span x-text="destination || '-'"></span></p>
                                    <p><span class="font-bold">{{ $isEnglish ? 'When:' : 'Wanneer:' }}</span> <span x-text="`${date} ${time}`"></span></p>
                                    <p><span class="font-bold">{{ $isEnglish ? 'Phone:' : 'Telefoon:' }}</span> <span x-text="phone || '-'"></span></p>
                                </div>
                            </div>

                            <div x-show="bookingStep === 3" x-cloak class="grid gap-3 sm:grid-cols-2">
                                <button
                                    x-ref="payLaterButton"
                                    type="submit"
                                    formaction="{{ route('bookings.pay-later.store') }}"
                                    :disabled="!submitReady()"
                                    class="rounded-md border border-zinc-950/10 bg-white/70 px-5 py-4 text-base font-black text-zinc-950 shadow-lg shadow-black/10 transition hover:bg-white disabled:cursor-not-allowed disabled:bg-zinc-200 disabled:text-zinc-500"
                                >
                                    {{ $isEnglish ? 'Book, pay later' : 'Boek, betaal later' }}
                                </button>
                                <button
                                    type="submit"
                                    :disabled="!submitReady()"
                                    class="rounded-md bg-zinc-950/90 px-5 py-4 text-base font-black text-white shadow-lg shadow-black/20 transition hover:bg-zinc-800 disabled:cursor-not-allowed disabled:bg-zinc-500"
                                >
                                    {{ $isEnglish ? 'Pay now with iDEAL' : 'Betaal nu met iDEAL' }}
                                </button>
                            </div>
                        </form>
                    </section>
                </div>
            </section>

            @php
                $serviceCards = [
                    [
                        'icon' => 'AIR',
                        'label' => $isEnglish ? 'Airport transfer' : 'Luchthavenvervoer',
                        'body' => $isEnglish ? 'Timed pickups for Schiphol and other airports, with luggage space and direct driver contact.' : 'Strakke pickups voor Schiphol en andere luchthavens, met bagageruimte en direct chauffeurscontact.',
                    ],
                    [
                        'icon' => 'CITY',
                        'label' => $isEnglish ? 'City rides' : 'Stadsritten',
                        'body' => $isEnglish ? 'Fast door-to-door rides for stations, hotels, appointments, dinners, and late returns.' : 'Snelle ritten van deur tot deur voor stations, hotels, afspraken, diners en late terugritten.',
                    ],
                    [
                        'icon' => 'BIZ',
                        'label' => $isEnglish ? 'Business trips' : 'Zakelijke ritten',
                        'body' => $isEnglish ? 'Clean, quiet transfers for meetings, office pickups, guests, and planned company travel.' : 'Nette, rustige ritten voor meetings, kantoor pickups, gasten en geplande bedrijfsritten.',
                    ],
                    [
                        'icon' => 'KM',
                        'label' => $isEnglish ? 'Long distance' : 'Lange afstand',
                        'body' => $isEnglish ? 'Comfortable travel outside the city without train switches, luggage stress, or waiting around.' : 'Comfortabel reizen buiten de stad zonder overstappen, bagagestress of onnodig wachten.',
                    ],
                ];
            @endphp

            <section id="services" class="relative bg-[#f6f3ea] px-5 py-20 text-zinc-950 sm:px-8">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_16%_18%,rgba(250,204,21,0.28),transparent_28%),linear-gradient(180deg,rgba(255,255,255,0.92),rgba(246,243,234,0.72))]"></div>
                <img src="{{ asset('images/husso-taxi-logo-transparent.png') }}" alt="" class="absolute left-[-9rem] top-4 hidden w-[32rem] -rotate-6 opacity-[0.06] lg:block">

                <div class="relative mx-auto max-w-7xl">
                    <div class="flex flex-col justify-between gap-6 md:flex-row md:items-end">
                        <div>
                            <p class="text-sm font-bold uppercase text-yellow-700">{{ $isEnglish ? 'Husso Taxi services' : 'Husso Taxi diensten' }}</p>
                            <h2 class="mt-2 max-w-2xl text-4xl font-black text-zinc-950">{{ $isEnglish ? 'Choose the ride that fits your day.' : 'Kies de rit die bij je dag past.' }}</h2>
                        </div>
                        <p class="max-w-xl text-base leading-7 text-zinc-700">
                            {{ $isEnglish ? 'Clean service cards, quick selection, and a direct route into the booking form.' : 'Heldere servicekaarten, snelle keuze en direct door naar het boekingsformulier.' }}
                        </p>
                    </div>

                    <div class="mt-12 grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                        @foreach ($serviceCards as $card)
                            <article class="group relative overflow-hidden rounded-lg border border-zinc-950/10 bg-white/70 p-6 shadow-xl shadow-yellow-950/5 backdrop-blur-xl transition duration-300 hover:-translate-y-1 hover:border-yellow-400/70 hover:shadow-2xl hover:shadow-yellow-950/15">
                                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-yellow-400 via-zinc-950 to-yellow-300"></div>
                                <div class="flex h-14 w-14 items-center justify-center rounded-md bg-zinc-950 text-sm font-black text-yellow-300 shadow-lg shadow-black/15 transition group-hover:bg-yellow-400 group-hover:text-zinc-950">
                                    {{ $card['icon'] }}
                                </div>
                                <h3 class="mt-6 text-2xl font-black">{{ $card['label'] }}</h3>
                                <p class="mt-4 min-h-28 text-sm leading-7 text-zinc-700">{{ $card['body'] }}</p>
                                <a href="#booking" class="mt-6 inline-flex w-full justify-center rounded-md border border-zinc-950/10 bg-zinc-950 px-4 py-3 text-sm font-black text-white transition hover:bg-yellow-400 hover:text-zinc-950">
                                    {{ $isEnglish ? 'Select' : 'Selecteer' }}
                                </a>
                            </article>
                        @endforeach
                    </div>

                    <div class="mt-10 flex flex-col gap-3 rounded-lg border border-zinc-950/10 bg-zinc-950/[0.88] p-5 text-white shadow-2xl shadow-yellow-950/10 backdrop-blur-xl sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm font-bold uppercase text-yellow-300">{{ $isEnglish ? 'Need a ride now?' : 'Nu een taxi nodig?' }}</p>
                            <p class="mt-1 text-lg font-bold">{{ $isEnglish ? 'Call Husso Taxi directly or send us your pickup details.' : 'Bel Husso Taxi direct of stuur je ophaalgegevens.' }}</p>
                        </div>
                        <div class="flex flex-col gap-3 sm:flex-row">
                            <a href="tel:+31686463268" class="inline-flex justify-center rounded-md bg-yellow-400 px-5 py-3 font-black text-zinc-950 transition hover:bg-yellow-300">{{ $isEnglish ? 'Call Us' : 'Bel ons' }}</a>
                            <a href="{{ $contactUrl }}" class="inline-flex justify-center rounded-md border border-white/20 px-5 py-3 font-black text-white transition hover:border-yellow-300 hover:text-yellow-200">{{ $isEnglish ? 'Contact page' : 'Contactpagina' }}</a>
                        </div>
                    </div>
                </div>
            </section>

        </main>

        <div class="fixed inset-x-0 bottom-0 z-50 border-t border-white/10 bg-zinc-950/90 px-4 py-3 shadow-2xl shadow-black/30 backdrop-blur-xl md:hidden">
            <div class="mx-auto grid max-w-md grid-cols-2 gap-3">
                <a href="tel:+31686463268" class="inline-flex justify-center rounded-md border border-yellow-300/30 px-4 py-3 text-sm font-black text-yellow-200">
                    {{ $isEnglish ? 'Call Direct' : 'Direct bellen' }}
                </a>
                <a href="#booking" class="inline-flex justify-center rounded-md bg-yellow-400 px-4 py-3 text-sm font-black text-zinc-950">
                    {{ $isEnglish ? 'Book Ride' : 'Rit boeken' }}
                </a>
            </div>
        </div>

        <div x-data="{ show: localStorage.getItem('husso_cookie_consent') !== 'yes' }" x-show="show" x-cloak class="fixed bottom-24 left-4 right-4 z-50 rounded-lg border border-white/10 bg-zinc-950/95 p-4 text-sm text-white shadow-2xl backdrop-blur-xl md:bottom-5 md:left-auto md:right-5 md:max-w-sm">
            <p class="font-bold">{{ $isEnglish ? 'Cookies and location' : 'Cookies en locatie' }}</p>
            <p class="mt-1 text-zinc-300">{{ $isEnglish ? 'We use a small consent cookie and only ask for your current location when you press the location button.' : 'We gebruiken een kleine toestemmingscookie en vragen je locatie alleen wanneer je op de locatieknop drukt.' }}</p>
            <button type="button" @click="localStorage.setItem('husso_cookie_consent', 'yes'); document.cookie = 'husso_cookie_consent=yes; Max-Age=31536000; SameSite=Lax; Path=/'; show = false" class="mt-3 w-full rounded-md bg-yellow-400 px-4 py-2 font-black text-zinc-950">
                {{ $isEnglish ? 'Allow' : 'Toestaan' }}
            </button>
        </div>
    </body>
</html>
