<x-app-layout>
    <x-slot name="adminShell">true</x-slot>
    <x-husso.dispatch-header />

    @php
        $metrics = [
            ['Ritten vandaag', $stats['today_bookings']],
            ['Open boekingen', $stats['open_bookings']],
            ['Actieve ritten', $stats['active_rides']],
            ['Omzet vandaag (betaald en later)', '€ '.number_format((float) $stats['today_revenue'], 2, ',', '.')],
            ['Gem. ritwaarde', '€ '.number_format((float) $stats['average_ride_value'], 2, ',', '.')],
            ['Chauffeur', $stats['driver_online'] ? 'Online' : 'Offline'],
        ];
    @endphp

    <main class="husso-wrap dispatch-main">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-x-3">
            <h1 class="dispatch-title !mb-0">Ritbeheer</h1>
            <a class="dispatch-link" href="{{ url('/') }}">Naar de website</a>
        </div>
        @if (session('status'))
            <p class="husso-notice" role="status">{{ session('status') }}</p>
        @endif
        <div class="dispatch-grid">
            <div class="dispatch-stack">
                <dl class="dispatch-panel dispatch-metrics">
                    @foreach ($metrics as [$label, $value])
                        <div><dt>{{ $label }}</dt><dd>{{ $value }}</dd></div>
                    @endforeach
                </dl>
                <section class="dispatch-panel" aria-labelledby="recent-title">
                    <div class="dispatch-panel-head">
                        <h2 id="recent-title" class="dispatch-heading">Recente boekingen</h2>
                        <a class="dispatch-link" href="{{ route('admin.bookings.index') }}">Alle boekingen <span class="husso-arrow" aria-hidden="true"></span></a>
                    </div>
                    <x-husso.booking-list :bookings="$bookings" empty="Nog geen boekingen." />
                </section>
            </div>

            <aside class="dispatch-stack">
                <section class="dispatch-panel dispatch-panel-body">
                    <h2 class="dispatch-heading">Live overzicht</h2>
                    @if ($activeBooking)
                        <p class="mt-2 font-semibold">{{ $activeBooking->reference }}</p>
                        <p class="dispatch-route mt-1 text-sm"><span>{{ $activeBooking->pickup }}</span><span>naar {{ $activeBooking->destination }}</span></p>
                        <div class="mt-2"><x-husso.status :code="$activeBooking->booking_status" /></div>
                        <a class="dispatch-link" href="{{ $activeBooking->trackingUrl() }}" target="_blank" rel="noopener">Volg rit</a>
                    @else
                        <p class="dispatch-muted mt-2">Geen rit onderweg.</p>
                    @endif
                    <div><a class="dispatch-link" href="{{ route('admin.live') }}">Naar live overzicht</a></div>
                </section>
                <section class="dispatch-panel dispatch-panel-body">
                    <h2 class="dispatch-heading">Prijsmodel</h2>
                    <dl class="dispatch-detail-list">
                        <div class="dispatch-fare"><dt>Per kilometer</dt><dd>€ {{ number_format(\App\Services\BookingPriceCalculator::KM_RATE, 2, ',', '.') }}</dd></div>
                        <div class="dispatch-fare"><dt>Per minuut</dt><dd>€ {{ number_format(\App\Services\BookingPriceCalculator::MINUTE_RATE, 2, ',', '.') }}</dd></div>
                    </dl>
                </section>
            </aside>
        </div>
    </main>
</x-app-layout>
