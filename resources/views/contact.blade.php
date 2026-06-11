@php
    $isEnglish = request('lang') === 'en';
    $homeUrl = $isEnglish ? url('/?lang=en') : url('/');
    $contactUrl = $isEnglish ? route('contact', ['lang' => 'en']) : route('contact');
    $languageUrl = $isEnglish ? route('contact') : route('contact', ['lang' => 'en']);
@endphp

<!DOCTYPE html>
<html lang="{{ $isEnglish ? 'en' : 'nl' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $isEnglish ? 'Contact Husso Taxi' : 'Contact Husso Taxi' }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700,800" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#f8f4e8] font-sans text-zinc-950 antialiased">
        <main class="relative min-h-screen overflow-hidden bg-[radial-gradient(circle_at_15%_10%,rgba(250,204,21,0.42),transparent_30%),linear-gradient(135deg,#141414_0%,#26200f_42%,#f8f4e8_100%)]">
            <div class="absolute inset-0 bg-[linear-gradient(90deg,rgba(0,0,0,0.78),rgba(0,0,0,0.45),rgba(255,255,255,0.12))]"></div>
            <img src="{{ asset('images/husso-taxi-logo-transparent.png') }}" alt="" class="absolute right-[-8rem] top-24 hidden w-[42rem] rotate-3 opacity-[0.20] blur-[1px] md:block">

            <header class="relative z-10 mx-auto flex max-w-7xl items-center justify-between px-5 py-5 sm:px-8">
                <a href="{{ $homeUrl }}" class="flex items-center gap-3">
                    <img src="{{ asset('images/husso-taxi-logo-transparent.png') }}" alt="Husso Taxi logo" class="h-12 w-12 object-contain drop-shadow-lg">
                    <span class="text-white">
                        <span class="block text-lg font-bold leading-none">Husso Taxi</span>
                        <span class="block text-xs font-medium text-zinc-300">{{ $isEnglish ? 'Airport & city transfers' : 'Luchthaven & stadsritten' }}</span>
                    </span>
                </a>

                <nav class="hidden items-center gap-8 text-sm font-medium text-zinc-200 md:flex">
                    <a href="{{ $homeUrl }}" class="transition hover:text-yellow-300">Home</a>
                    <a href="{{ $homeUrl }}#services" class="transition hover:text-yellow-300">{{ $isEnglish ? 'Services' : 'Diensten' }}</a>
                    <a href="{{ $homeUrl }}#booking" class="transition hover:text-yellow-300">{{ $isEnglish ? 'Book now' : 'Boek nu' }}</a>
                </nav>

                <div class="flex items-center gap-3">
                    <a href="{{ $languageUrl }}" class="inline-flex rounded-md border border-white/25 bg-white/10 px-3 py-2 text-sm font-bold text-white backdrop-blur transition hover:border-yellow-300 hover:text-yellow-300">
                        {{ $isEnglish ? 'Nederlands' : 'English' }}
                    </a>
                    <a href="tel:+31686463268" class="rounded-md bg-yellow-400/90 px-4 py-2 text-sm font-bold text-zinc-950 shadow-lg shadow-yellow-400/20 backdrop-blur transition hover:bg-yellow-300">
                        {{ $isEnglish ? 'Call Us' : 'Bel ons' }}
                    </a>
                </div>
            </header>

            <section class="relative z-10 mx-auto grid max-w-7xl gap-10 px-5 pb-20 pt-10 sm:px-8 lg:grid-cols-[0.95fr_1.05fr] lg:items-center lg:pt-20">
                <div>
                    <p class="inline-flex rounded-md border border-yellow-300/40 bg-yellow-300/10 px-3 py-2 text-sm font-bold uppercase text-yellow-200 shadow-lg shadow-black/10 backdrop-blur-xl">
                        {{ $isEnglish ? 'Contact Husso Taxi' : 'Contact Husso Taxi' }}
                    </p>
                    <h1 class="mt-6 max-w-3xl text-5xl font-black leading-[0.95] text-white drop-shadow-2xl sm:text-6xl">
                        {{ $isEnglish ? 'Need a taxi? Call, email, or send your ride details.' : 'Taxi nodig? Bel, mail of stuur je ritgegevens.' }}
                    </h1>
                    <p class="mt-6 max-w-2xl text-lg leading-8 text-zinc-100">
                        {{ $isEnglish ? 'For airport transfers, city rides, business pickups, and planned routes, contact Husso Taxi directly.' : 'Voor luchthavenvervoer, stadsritten, zakelijke pickups en geplande routes neem je direct contact op met Husso Taxi.' }}
                    </p>

                    <div class="mt-8 grid gap-4 sm:grid-cols-2">
                        <a href="tel:+31686463268" class="rounded-lg border border-white/20 bg-white/10 p-5 text-white shadow-xl shadow-black/10 backdrop-blur-xl transition hover:border-yellow-300">
                            <span class="text-sm font-bold uppercase text-yellow-300">{{ $isEnglish ? 'Call Us' : 'Bel ons' }}</span>
                            <span class="mt-2 block text-2xl font-black">+31 6 86463268</span>
                        </a>
                        <a href="mailto:info@husso.nl" class="rounded-lg border border-white/20 bg-white/10 p-5 text-white shadow-xl shadow-black/10 backdrop-blur-xl transition hover:border-yellow-300">
                            <span class="text-sm font-bold uppercase text-yellow-300">{{ $isEnglish ? 'Email' : 'E-mail' }}</span>
                            <span class="mt-2 block text-2xl font-black">info@husso.nl</span>
                        </a>
                    </div>
                </div>

                <section class="rounded-lg border border-white/40 bg-white/[0.72] p-5 shadow-2xl shadow-black/20 backdrop-blur-2xl sm:p-6">
                    <div class="mb-6">
                        <p class="text-sm font-bold uppercase text-yellow-700">{{ $isEnglish ? 'Quick message' : 'Snel bericht' }}</p>
                        <h2 class="mt-1 text-3xl font-black">{{ $isEnglish ? 'Send ride details' : 'Stuur je ritgegevens' }}</h2>
                        <p class="mt-2 leading-7 text-zinc-700">{{ $isEnglish ? 'This form is ready for styling now and can be connected to real messages later.' : 'Dit formulier is alvast mooi vormgegeven en kan later gekoppeld worden aan echte berichten.' }}</p>
                    </div>

                    <form class="grid gap-4">
                        <label class="block">
                            <span class="text-sm font-semibold text-zinc-700">{{ $isEnglish ? 'Name' : 'Naam' }}</span>
                            <input type="text" placeholder="{{ $isEnglish ? 'Your name' : 'Je naam' }}" class="mt-1 w-full rounded-md border-white/60 bg-white/70 text-zinc-950 shadow-sm focus:border-yellow-500 focus:ring-yellow-500">
                        </label>

                        <label class="block">
                            <span class="text-sm font-semibold text-zinc-700">{{ $isEnglish ? 'Phone number' : 'Telefoonnummer' }}</span>
                            <input type="tel" placeholder="+31 ..." class="mt-1 w-full rounded-md border-white/60 bg-white/70 text-zinc-950 shadow-sm focus:border-yellow-500 focus:ring-yellow-500">
                        </label>

                        <label class="block">
                            <span class="text-sm font-semibold text-zinc-700">{{ $isEnglish ? 'Message' : 'Bericht' }}</span>
                            <textarea rows="5" placeholder="{{ $isEnglish ? 'Pickup, destination, date, time, passengers...' : 'Ophaaladres, bestemming, datum, tijd, passagiers...' }}" class="mt-1 w-full rounded-md border-white/60 bg-white/70 text-zinc-950 shadow-sm focus:border-yellow-500 focus:ring-yellow-500"></textarea>
                        </label>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <a href="mailto:info@husso.nl" class="inline-flex justify-center rounded-md bg-zinc-950/90 px-5 py-4 text-base font-black text-white shadow-lg shadow-black/20 transition hover:bg-zinc-800">
                                {{ $isEnglish ? 'Email Husso Taxi' : 'Mail Husso Taxi' }}
                            </a>
                            <a href="tel:+31686463268" class="inline-flex justify-center rounded-md bg-yellow-400 px-5 py-4 text-base font-black text-zinc-950 shadow-lg shadow-yellow-400/20 transition hover:bg-yellow-300">
                                {{ $isEnglish ? 'Call Us' : 'Bel ons' }}
                            </a>
                        </div>
                    </form>
                </section>
            </section>
        </main>
    </body>
</html>
