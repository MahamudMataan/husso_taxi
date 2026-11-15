<x-app-layout>
    <x-slot name="adminShell">true</x-slot>
    <x-husso.dispatch-header />

    @php
        $running = $shift && ! $shift->clocked_out_at;
        $pause = $running ? $shift->breaks->firstWhere('ended_at', null) : null;
        $formatTime = fn ($time) => $time->copy()->timezone('Europe/Amsterdam')->format('H:i');
        $featured = $activeBooking ?? $overdueBooking ?? $nextBooking;
        $featuredTitle = $activeBooking ? 'Actieve rit' : ($overdueBooking ? 'Rit wacht op actie' : 'Eerstvolgende rit');
        $nextAction = $featured ? [
            'new' => 'Rit bevestigen',
            'driver_assigned' => 'Ik vertrek',
            'driver_en_route' => 'Ik ben aangekomen',
            'arrived' => 'Passagier opgehaald',
            'picked_up' => 'Rit afronden',
        ][$featured->booking_status] ?? null : null;
    @endphp

    <main class="husso-wrap dispatch-main workday">
        <div class="workday-heading">
            <div>
                <h1 class="dispatch-title">Vandaag</h1>
                <p class="dispatch-muted">{{ ucfirst($today->locale('nl')->translatedFormat('l j F')) }} · {{ $driverName }}</p>
            </div>
            <a class="husso-button husso-button--plain" href="{{ route('admin.bookings.index', ['filter' => 'open']) }}">Open boekingen</a>
        </div>
        @if (session('status') || $errors->any())
            <p class="husso-notice" role="status">{{ session('status') ?? $errors->first() }}</p>
        @endif

        <div class="workday-grid">
            <section class="dispatch-panel dispatch-panel-body workday-shift" aria-labelledby="shift-title">
                <div class="workday-panel-heading">
                    <h2 id="shift-title" class="dispatch-heading">Mijn dienst</h2>
                    <span class="workday-state">{{ $pause ? 'Pauze' : ($running ? 'In dienst' : 'Buiten dienst') }}</span>
                </div>
                @if ($shift)
                    <dl class="workday-shift-times">
                        <div><dt>Begonnen</dt><dd>{{ $formatTime($shift->clocked_in_at) }}</dd></div>
                        <div><dt>Geëindigd</dt><dd>{{ $shift->clocked_out_at ? $formatTime($shift->clocked_out_at) : '—' }}</dd></div>
                    </dl>
                @endif
                <form class="workday-actions" method="POST" action="{{ route('dashboard.driver.clock') }}">
                    @csrf
                    @if ($running)
                        <button class="husso-button husso-button--primary" name="action" value="{{ $pause ? 'resume' : 'break' }}">{{ $pause ? 'Dienst hervatten' : 'Pauze starten' }}</button>
                        <button class="husso-button husso-button--plain" name="action" value="out">Dienst beëindigen</button>
                    @else
                        <button class="husso-button husso-button--primary" name="action" value="in">Dienst starten</button>
                    @endif
                </form>
                @if ($shift?->breaks->isNotEmpty())
                    <div class="workday-breaks">
                        <h3>Pauzes tijdens deze dienst</h3>
                        <ul>
                            @foreach ($shift->breaks->sortBy('started_at') as $loggedBreak)
                                <li>{{ $formatTime($loggedBreak->started_at) }} – {{ $loggedBreak->ended_at ? $formatTime($loggedBreak->ended_at) : 'nu' }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </section>

            <section class="dispatch-panel dispatch-panel-body workday-ride" aria-labelledby="workday-ride-title">
                <div class="workday-panel-heading">
                    <h2 id="workday-ride-title" class="dispatch-heading">{{ $featuredTitle }}</h2>
                    @if ($featured)<x-husso.status :code="$featured->booking_status" />@endif
                </div>
                @if ($featured)
                    <p class="workday-pickup-time">{{ substr($featured->ride_time, 0, 5) }} <span>{{ $featured->ride_date->format('d-m-Y') }}</span></p>
                    <div class="workday-route">
                        <p><span>Ophalen</span>{{ $featured->pickup }}</p>
                        <p><span>Bestemming</span>{{ $featured->destination }}</p>
                    </div>
                    <div class="workday-ride-meta">{{ $featured->passengers }} {{ (int) $featured->passengers === 1 ? 'passagier' : 'passagiers' }} · {{ $featured->paymentMethodLabel() }} · € {{ number_format($featured->calculatedFare(), 2, ',', '.') }}</div>
                    <div class="workday-actions">
                        @if ($nextAction)
                            <form method="POST" action="{{ route('dashboard.bookings.dispatch', $featured) }}">
                                @csrf
                                @method('PATCH')
                                <button class="husso-button husso-button--primary" name="booking_status" value="{{ $featured->nextDispatchStatus() }}">{{ $nextAction }}</button>
                            </form>
                        @endif
                        <a class="husso-button husso-button--plain" href="{{ route('admin.bookings.show', $featured) }}">Ritdetails</a>
                    </div>
                    @if ($activeBooking)
                        <div class="workday-breaks" x-data="driverLocation(@js(route('dashboard.bookings.location.update', $activeBooking)), {{ $activeBooking->id }})" x-cloak>
                            <button type="button" class="husso-button husso-button--plain" :disabled="stopping" @click="sharing ? stop() : start()"
                                x-text="sharing ? 'Stop met delen' : 'Deel mijn locatie'">Deel mijn locatie</button>
                            <p class="mt-2" role="status" x-text="message || 'Laat dit scherm open om je locatie te blijven delen.'"></p>
                        </div>
                    @endif
                @else
                    <p class="workday-empty">Er staan geen open ritten op je planning.</p>
                @endif
            </section>
        </div>

        <dl class="workday-totals">
            <div><dt>Afgeronde ritten vandaag</dt><dd>{{ $ridesCompleted }}</dd></div>
            <div><dt>Ontvangen vandaag</dt><dd>€ {{ number_format((float) $receivedToday, 2, ',', '.') }}</dd></div>
            <div><dt>Nog te ontvangen voor ritten vandaag</dt><dd>€ {{ number_format((float) $openAmount, 2, ',', '.') }}</dd></div>
        </dl>

        <section class="dispatch-panel" aria-labelledby="today-rides">
            <div class="dispatch-panel-head"><h2 id="today-rides" class="dispatch-heading">Ritten op de planning</h2></div>
            <x-husso.booking-list :bookings="$rides" empty="Vandaag staan er geen open ritten gepland." />
        </section>
    </main>
</x-app-layout>
