@props(['booking', 'isEnglish' => false])

@php
    $money = fn (float $amount): string => '€ '.number_format($amount, 2, $isEnglish ? '.' : ',', $isEnglish ? ',' : '.');
@endphp

<dl class="dispatch-detail-list">
    <div class="dispatch-fare"><dt>{{ $isEnglish ? 'Starting fare' : 'Instap' }}</dt><dd>{{ $money((float) $booking->base_fare) }}</dd></div>
    <div class="dispatch-fare"><dt>{{ number_format((float) $booking->distance_km, 1, $isEnglish ? '.' : ',', $isEnglish ? ',' : '.') }} km × {{ $money((float) $booking->km_rate) }}</dt><dd>{{ $money($booking->distanceFare()) }}</dd></div>
    <div class="dispatch-fare"><dt>{{ $booking->duration_min }} min × {{ $money((float) $booking->minute_rate) }}</dt><dd>{{ $money($booking->timeFare()) }}</dd></div>
    <div class="dispatch-fare border-t border-[var(--line)] pt-2"><dt>{{ $isEnglish ? 'Total' : 'Totaal' }}</dt><dd>{{ $money($booking->calculatedFare()) }}</dd></div>
</dl>
