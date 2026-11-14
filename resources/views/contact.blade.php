@php
    $isEnglish = request('lang') === 'en';
    $homeUrl = url($isEnglish ? '/?lang=en' : '/');
    $contactUrl = route('contact', $isEnglish ? ['lang' => 'en'] : []);
    $languageUrl = route('contact', $isEnglish ? [] : ['lang' => 'en']);
@endphp

<!DOCTYPE html>
<html lang="{{ $isEnglish ? 'en' : 'nl' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $isEnglish ? 'Contact Husso Taxi | Call or Email' : 'Contact Husso Taxi | Bel of Mail' }}</title>
        <meta name="description" content="{{ $isEnglish ? 'Call or email Husso Taxi with a question about your ride or booking.' : 'Bel of mail Husso Taxi met een vraag over je rit of boeking.' }}">
        <link rel="canonical" href="{{ $contactUrl }}">
        <link rel="alternate" hreflang="nl" href="{{ route('contact') }}">
        <link rel="alternate" hreflang="en" href="{{ route('contact', ['lang' => 'en']) }}">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="husso site-contact">
        <x-husso.header :is-english="$isEnglish" :home-url="$homeUrl" :language-url="$languageUrl" :on-contact="true" />
        <main class="husso-wrap contact-layout">
            <div class="contact-intro">
                <h1>{{ $isEnglish ? 'Questions about your ride?' : 'Een vraag over je rit?' }}</h1>
                <p>{{ $isEnglish
                    ? 'Call us if you need a taxi soon. For a question about a booking, include your reference when you call or email.'
                    : 'Bel ons als je snel een taxi nodig hebt. Gaat je vraag over een boeking? Houd dan je ritnummer bij de hand of zet het in je mail.' }}</p>
            </div>
            <section class="contact-options" aria-labelledby="contact-title">
                <h2 id="contact-title">Contact</h2>
                <dl>
                    <div>
                        <dt>{{ $isEnglish ? 'Phone' : 'Telefoon' }}</dt>
                        <dd><a class="husso-button husso-button--primary" href="tel:+31686463268">+31 6 8646 3268</a></dd>
                    </div>
                    <div>
                        <dt>E-mail</dt>
                        <dd><a href="mailto:info@husso.nl">info@husso.nl</a></dd>
                    </div>
                </dl>
                <p>{{ $isEnglish ? 'Want to check the price of a new ride?' : 'Wil je de prijs van een nieuwe rit bekijken?' }}</p>
                <a class="husso-button husso-button--plain" href="{{ $homeUrl }}#booking">{{ $isEnglish ? 'Book a ride' : 'Rit boeken' }} <span class="husso-arrow" aria-hidden="true"></span></a>
            </section>
        </main>
        <x-husso.footer :is-english="$isEnglish" :home-url="$homeUrl" />
    </body>
</html>
