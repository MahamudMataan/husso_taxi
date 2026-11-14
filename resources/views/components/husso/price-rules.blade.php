@use('App\Services\BookingPriceCalculator')
@props(['isEnglish' => false])

@php
    $money = fn (float $amount): string => '€ '.number_format($amount, 2, $isEnglish ? '.' : ',', $isEnglish ? ',' : '.');
    $threshold = BookingPriceCalculator::LONG_DISTANCE_KM;
@endphp

<dl class="dispatch-detail-list" aria-label="{{ $isEnglish ? 'Taxi fares' : 'Taxitarieven' }}">
    <div class="dispatch-fare"><dt>{{ $isEnglish ? 'Starting fare (once per ride)' : 'Instap (eenmalig per rit)' }}</dt><dd>{{ $money(BookingPriceCalculator::BASE_FARE) }}</dd></div>
    <div class="dispatch-fare"><dt>{{ $isEnglish ? 'Up to '.$threshold.' km' : 'Tot en met '.$threshold.' km' }}</dt><dd>{{ $money(BookingPriceCalculator::KM_RATE) }}/km</dd></div>
    <div class="dispatch-fare"><dt>{{ $isEnglish ? 'Over '.$threshold.' km (entire ride)' : 'Boven '.$threshold.' km (hele rit)' }}</dt><dd>{{ $money(BookingPriceCalculator::LONG_DISTANCE_KM_RATE) }}/km</dd></div>
    <div class="dispatch-fare"><dt>{{ $isEnglish ? 'Ride time' : 'Rijtijd' }}</dt><dd>{{ $money(BookingPriceCalculator::MINUTE_RATE) }}/min</dd></div>
</dl>
