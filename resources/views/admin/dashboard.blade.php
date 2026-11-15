<x-app-layout>
    <x-slot name="adminShell">true</x-slot>
    <x-husso.dispatch-header />

    @php
        $metrics = [
            ['Ritten vandaag', $stats['today_bookings']],
            ['Omzet vandaag (betaald en later)', '€ '.number_format((float) $stats['today_revenue'], 2, ',', '.')],
            ['Gem. ritwaarde', '€ '.number_format((float) $stats['average_ride_value'], 2, ',', '.')],
        ];
    @endphp

    <main class="husso-wrap dispatch-main dispatch-overview">
        <div class="dispatch-overview__heading">
            <h1 class="dispatch-title">Ritbeheer</h1>
            <a class="dispatch-link" href="{{ url('/') }}">Naar de website <span class="husso-arrow" aria-hidden="true"></span></a>
        </div>

        @if (session('status'))
            <p class="husso-notice" role="status">{{ session('status') }}</p>
        @endif

        <div class="dispatch-overview__priorities">
            <section class="dispatch-overview__live" aria-labelledby="live-title">
                <div class="dispatch-overview__live-heading">
                    <h2 id="live-title" class="dispatch-heading">Live overzicht</h2>
                    <dl class="dispatch-overview__live-count">
                        <dt>Actieve ritten</dt>
                        <dd>{{ $stats['active_rides'] }}</dd>
                    </dl>
                </div>

                @if ($activeBooking)
                    <div class="dispatch-overview__ride">
                        <div class="dispatch-overview__ride-heading">
                            <p>{{ $activeBooking->reference }}</p>
                            <x-husso.status :code="$activeBooking->booking_status" />
                        </div>
                        <p class="dispatch-route">
                            <span>{{ $activeBooking->pickup }}</span>
                            <span>naar {{ $activeBooking->destination }}</span>
                        </p>
                    </div>
                @else
                    <p class="dispatch-overview__empty">Geen rit onderweg.</p>
                @endif

                <div class="dispatch-overview__live-actions">
                    <a class="husso-button husso-button--primary" href="{{ route('admin.live') }}">
                        Naar live overzicht <span class="husso-arrow" aria-hidden="true"></span>
                    </a>
                    @if ($activeBooking)
                        <a class="dispatch-link" href="{{ $activeBooking->trackingUrl() }}" target="_blank" rel="noopener">Volg rit</a>
                    @endif
                </div>
            </section>

            <section class="dispatch-overview__open" aria-labelledby="open-title">
                <h2 id="open-title" class="dispatch-heading">Open boekingen</h2>
                <p class="dispatch-overview__open-count">{{ $stats['open_bookings'] }}</p>
                <a class="husso-button dispatch-overview__open-action" href="{{ route('admin.bookings.index', ['filter' => 'open']) }}">
                    Bekijk open boekingen <span class="husso-arrow" aria-hidden="true"></span>
                </a>
            </section>
        </div>

        <dl class="dispatch-overview__metrics">
            @foreach ($metrics as [$label, $value])
                <div>
                    <dt>{{ $label }}</dt>
                    <dd>{{ $value }}</dd>
                </div>
            @endforeach
            <div>
                <dt>Chauffeur</dt>
                <dd class="dispatch-overview__driver" data-online="{{ $stats['driver_online'] ? 'true' : 'false' }}">
                    {{ $stats['driver_online'] ? 'Online' : 'Offline' }}
                </dd>
            </div>
        </dl>

        <div class="dispatch-overview__details">
            <section class="dispatch-panel dispatch-overview__recent" aria-labelledby="recent-title">
                <div class="dispatch-panel-head">
                    <h2 id="recent-title" class="dispatch-heading">Recente boekingen</h2>
                    <a class="dispatch-link" href="{{ route('admin.bookings.index') }}">Alle boekingen <span class="husso-arrow" aria-hidden="true"></span></a>
                </div>
                <x-husso.booking-list :bookings="$bookings" empty="Nog geen boekingen." />
            </section>

            <section class="dispatch-panel dispatch-panel-body dispatch-overview__fares" aria-labelledby="fares-title">
                <h2 id="fares-title" class="dispatch-heading">Prijsmodel</h2>
                <x-husso.price-rules />
            </section>
        </div>
    </main>
</x-app-layout>
