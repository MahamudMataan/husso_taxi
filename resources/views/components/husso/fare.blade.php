@props(['booking', 'isEnglish' => false])

<dl class="dispatch-detail-list">
    <div class="dispatch-fare"><dt>{{ $booking->distance_km }} km × € {{ number_format(\App\Services\BookingPriceCalculator::KM_RATE, 2, ',', '.') }}</dt><dd>€ {{ number_format($booking->distanceFare(), 2, ',', '.') }}</dd></div>
    <div class="dispatch-fare"><dt>{{ $booking->duration_min }} min × € {{ number_format(\App\Services\BookingPriceCalculator::MINUTE_RATE, 2, ',', '.') }}</dt><dd>€ {{ number_format($booking->timeFare(), 2, ',', '.') }}</dd></div>
    <div class="dispatch-fare border-t border-[var(--line)] pt-2"><dt>{{ $isEnglish ? 'Total' : 'Totaal' }}</dt><dd>€ {{ number_format($booking->calculatedFare(), 2, ',', '.') }}</dd></div>
</dl>
