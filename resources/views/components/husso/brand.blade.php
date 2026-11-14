@props(['href' => url('/')])

<a href="{{ $href }}" {{ $attributes->class(['husso-brand']) }}>
    <img src="{{ asset('images/husso-taxi-mark.webp') }}" width="44" height="44" alt="">
    <span>Husso Taxi</span>
</a>
