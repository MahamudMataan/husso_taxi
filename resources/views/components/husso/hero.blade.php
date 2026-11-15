@props(['isEnglish', 'rideUrl'])

@php
    $slides = [
        [
            'airport',
            $isEnglish ? 'To Schiphol in comfort.' : 'Naar Schiphol in alle rust.',
            $isEnglish ? 'Reserve your airport transfer in advance and see your estimated fare before you book.' : 'Reserveer je luchthaventaxi vooraf en bekijk je prijsindicatie voordat je boekt.',
            $isEnglish ? 'Book an airport transfer' : 'Boek luchthavenvervoer',
            'airport',
        ],
        [
            'electric',
            $isEnglish ? 'Business travel, arranged.' : 'Zakelijk onderweg.',
            $isEnglish ? 'Arrange a ride for your next meeting or a pickup for your guests.' : 'Reserveer een rit naar je volgende afspraak of laat je gasten ophalen.',
            $isEnglish ? 'Book a business ride' : 'Boek een zakelijke rit',
            'business',
        ],
        [
            'city',
            $isEnglish ? 'Wherever you need to go.' : 'Waar je ook heen moet.',
            $isEnglish ? 'An appointment, a night out or a short trip. Husso Taxi takes you to your destination in comfort.' : 'Een afspraak, een avondje uit of een korte rit. Husso Taxi brengt je comfortabel naar je bestemming.',
            $isEnglish ? 'Plan your ride' : 'Plan jouw rit',
            'standard',
        ],
    ];
@endphp

<section class="taxi-hero" aria-label="{{ $isEnglish ? 'Discover Husso Taxi' : 'Ontdek Husso Taxi' }}" x-data="hero({{ count($slides) }})"
    @focusin="if (!$event.target.closest('.taxi-hero-controls')) pause()"
    @keydown.left.prevent="show(current - 1)" @keydown.right.prevent="show(current + 1)"
    @touchstart.passive="touchStart = $event.touches[0]; pause()" @touchend.passive="finishTouch($event)">
    <h1 class="sr-only">Husso Taxi</h1>
    <div class="taxi-hero-stage" :aria-live="playing ? 'off' : 'polite'">
        @foreach ($slides as $index => [$image, $headline, $description, $cta, $type])
            <div class="taxi-hero-slide taxi-hero-slide--{{ $image }}" :class="{ 'is-current': current === {{ $index }} }"
                :inert="current !== {{ $index }}" :aria-hidden="current !== {{ $index }}" @if($index !== 0) x-cloak @endif>
                @if ($image === 'airport')
                    <img src="{{ asset('images/hero-schiphol.jpeg') }}" alt="{{ $isEnglish ? 'Taxis beneath the control tower at Schiphol' : 'Taxi’s bij de verkeerstoren op Schiphol' }}"
                        width="1280" height="853" fetchpriority="high" decoding="async">
                @else
                    <img src="{{ asset('images/hero-'.$image.'-1536.webp') }}"
                        srcset="{{ asset('images/hero-'.$image.'-768.webp') }} 768w, {{ asset('images/hero-'.$image.'-1536.webp') }} 1536w"
                        sizes="100vw" alt="" width="1536" height="1024" decoding="async"
                        @if($index === 0) fetchpriority="high" @else loading="lazy" @endif>
                @endif
                <div class="husso-wrap taxi-hero-copy">
                    <h2>{{ $headline }}</h2>
                    <p>{{ $description }}</p>
                    <a class="husso-button husso-button--primary" href="{{ $rideUrl($type) }}" @click="chooseRideType($event, @js($type))">
                        {{ $cta }} <span class="husso-arrow" aria-hidden="true"></span>
                    </a>
                </div>
            </div>
        @endforeach
    </div>
    <div class="taxi-hero-controls" x-cloak>
        <button class="taxi-hero-arrow taxi-hero-arrow--prev" type="button" @click="show(current - 1)" aria-label="{{ $isEnglish ? 'Previous slide' : 'Vorige foto' }}"><svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path d="m15 5-7 7 7 7"/></svg></button>
        @foreach ($slides as $index => $slide)
            <button type="button" class="taxi-hero-dot" @click="show({{ $index }})" :aria-pressed="current === {{ $index }}" aria-label="{{ $slide[1] }}"><span></span></button>
        @endforeach
        <button class="taxi-hero-arrow taxi-hero-arrow--next" type="button" @click="show(current + 1)" aria-label="{{ $isEnglish ? 'Next slide' : 'Volgende foto' }}"><svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path d="m9 5 7 7-7 7"/></svg></button>
        <button type="button" class="taxi-hero-play" @click="playing ? pause() : play()" x-show="!motion?.matches"
            :aria-label="playing ? @js($isEnglish ? 'Pause' : 'Pauze') : @js($isEnglish ? 'Play' : 'Afspelen')">
            <svg x-show="playing" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                <path d="M9 5v14m6-14v14" />
            </svg>
            <svg x-show="!playing" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" fill="currentColor">
                <path d="M8 5v14l12-7z" />
            </svg>
        </button>
    </div>
</section>
