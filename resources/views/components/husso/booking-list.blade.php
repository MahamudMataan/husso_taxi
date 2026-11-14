@props(['bookings', 'empty' => 'Geen boekingen gevonden.', 'filters' => []])

@if ($bookings->isEmpty())
    <p class="dispatch-empty">{{ $empty }}</p>
@else
    <div class="dispatch-cards">
        @foreach ($bookings as $booking)
            <article class="dispatch-card">
                <div class="dispatch-card-title">
                    <h3 class="break-all">{{ $booking->reference }}</h3>
                    <time datetime="{{ $booking->ride_date->format('Y-m-d') }}T{{ substr((string) $booking->ride_time, 0, 5) }}">{{ $booking->ride_date->format('d-m') }} · {{ substr((string) $booking->ride_time, 0, 5) }}</time>
                </div>
                <p class="dispatch-route"><span>{{ $booking->pickup }}</span><span>naar {{ $booking->destination }}</span></p>
                <div class="dispatch-statuses">
                    <x-husso.status :code="$booking->booking_status" />
                    <x-husso.status :code="$booking->payment_status" kind="payment" />
                </div>
                <div class="dispatch-card-actions">
                    <a class="husso-button husso-button--primary" href="{{ route('admin.bookings.show', ['booking' => $booking] + $filters) }}" aria-label="Beheer {{ $booking->reference }}">Beheer</a>
                    <a class="dispatch-link" href="{{ $booking->trackingUrl() }}" target="_blank" rel="noopener">Volg rit</a>
                    <span class="ml-auto font-semibold tabular-nums">€ {{ number_format($booking->calculatedFare(), 2, ',', '.') }}</span>
                </div>
            </article>
        @endforeach
    </div>

    <table class="dispatch-table">
        <caption class="sr-only">Boekingen met ritstatus en betaalstatus</caption>
        <colgroup>
            <col class="dispatch-time-column">
            <col>
            <col class="dispatch-status-column">
            <col class="dispatch-price-column">
            <col class="dispatch-action-column">
        </colgroup>
        <thead>
            <tr>
                <th scope="col">Vertrek</th>
                <th scope="col">Rit</th>
                <th scope="col">Status en betaling</th>
                <th scope="col">Prijs</th>
                <th scope="col">Acties</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($bookings as $booking)
                <tr>
                    <td>
                        <time datetime="{{ $booking->ride_date->format('Y-m-d') }}T{{ substr((string) $booking->ride_time, 0, 5) }}" class="block whitespace-nowrap font-semibold">{{ substr((string) $booking->ride_time, 0, 5) }}</time>
                        <span class="dispatch-muted whitespace-nowrap">{{ $booking->ride_date->format('d-m-Y') }}</span>
                    </td>
                    <td>
                        <span class="block break-words font-semibold">{{ $booking->reference }} · {{ $booking->customer_name }}</span>
                        <span class="dispatch-route"><span>{{ $booking->pickup }}</span><span>naar {{ $booking->destination }}</span></span>
                    </td>
                    <td>
                        <div class="dispatch-statuses">
                            <x-husso.status :code="$booking->booking_status" />
                            <x-husso.status :code="$booking->payment_status" kind="payment" />
                        </div>
                    </td>
                    <td class="whitespace-nowrap font-semibold tabular-nums">€ {{ number_format($booking->calculatedFare(), 2, ',', '.') }}</td>
                    <td>
                        <a class="husso-button husso-button--primary" href="{{ route('admin.bookings.show', ['booking' => $booking] + $filters) }}" aria-label="Beheer {{ $booking->reference }}">Beheer</a>
                        <a class="dispatch-link" href="{{ $booking->trackingUrl() }}" target="_blank" rel="noopener">Volg rit</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
