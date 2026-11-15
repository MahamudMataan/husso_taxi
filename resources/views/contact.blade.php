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
        <title>{{ $isEnglish ? 'Contact Husso Taxi | WhatsApp, Phone and Email' : 'Contact Husso Taxi | WhatsApp, Telefoon en E-mail' }}</title>
        <x-husso.favicon />
        <meta name="description" content="{{ $isEnglish ? 'Contact Husso Taxi by WhatsApp, phone or email with a question about your ride or booking.' : 'Neem via WhatsApp, telefoon of e-mail contact op met Husso Taxi over je rit of boeking.' }}">
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
                    ? 'Call us if you need a taxi soon. If you have a question about a booking, please include your booking reference.'
                    : 'Bel ons als je snel een taxi nodig hebt. Gaat je vraag over een boeking? Vermeld dan je ritnummer.' }}</p>
            </div>
            <section class="contact-options" aria-labelledby="contact-title">
                <h2 id="contact-title">Contact</h2>
                <dl>
                    <div>
                        <dt>WhatsApp</dt>
                        <dd><x-husso.whatsapp class="husso-button husso-button--primary" :show-number="true" /></dd>
                    </div>
                    <div>
                        <dt>{{ $isEnglish ? 'Phone' : 'Telefoon' }}</dt>
                        <dd><a href="tel:+31686463268" aria-label="{{ $isEnglish ? 'Call +31 6 8646 3268' : 'Bel +31 6 8646 3268' }}">+31 6 8646 3268</a></dd>
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
