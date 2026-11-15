@props(['booking', 'isEnglish' => false])

@php
    $money = fn (float $amount): string => '€ '.number_format($amount, 2, $isEnglish ? '.' : ',', $isEnglish ? ',' : '.');
@endphp

<dl class="dispatch-detail-list">
    @if ($booking->fare_type === 'schiphol_fixed')
        <div class="dispatch-fare"><dt>{{ $isEnglish ? 'Fixed Schiphol fare' : 'Vaste Schipholprijs' }}</dt><dd>{{ $money((float) $booking->base_fare) }}</dd></div>
    @else
    <div class="dispatch-fare"><dt>{{ $isEnglish ? 'Starting fare' : 'Instap' }}</dt><dd>{{ $money((float) $booking->base_fare) }}</dd></div>
    <div class="dispatch-fare"><dt>{{ number_format((float) $booking->distance_km, 1, $isEnglish ? '.' : ',', $isEnglish ? ',' : '.') }} km × {{ $money((float) $booking->km_rate) }}</dt><dd>{{ $money($booking->distanceFare()) }}</dd></div>
    <div class="dispatch-fare"><dt>{{ $booking->duration_min }} min × {{ $money((float) $booking->minute_rate) }}</dt><dd>{{ $money($booking->timeFare()) }}</dd></div>
    @endif
    @if ($booking->departure_supplement_cents > 0)
        <div class="dispatch-fare"><dt>{{ $isEnglish ? 'Minimum fare adjustment' : 'Uitrijkosten (aanvulling minimumritprijs)' }}</dt><dd>{{ $money($booking->departure_supplement_cents / 100) }}</dd></div>
    @endif
    <div class="dispatch-fare border-t border-[var(--line)] pt-2"><dt>{{ $isEnglish ? 'Total' : 'Totaal' }}</dt><dd>{{ $money($booking->calculatedFare()) }}</dd></div>
</dl>
