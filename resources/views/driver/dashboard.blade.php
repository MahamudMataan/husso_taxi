<x-app-layout>
    <x-slot name="adminShell">true</x-slot>
    <x-husso.dispatch-header />

    <main class="husso-wrap dispatch-main workday">
        <div class="workday-heading">
            <div>
                <h1 class="dispatch-title">Vandaag</h1>
                <p class="dispatch-muted">{{ ucfirst(now('Europe/Amsterdam')->locale('nl')->translatedFormat('l j F')) }} · {{ $driverName }}</p>
            </div>
            <a class="husso-button husso-button--plain" href="{{ route('admin.bookings.index', ['filter' => 'open']) }}">Open boekingen</a>
        </div>

        @if (session('status') || $errors->any())
            <p class="husso-notice" role="status">{{ session('status') ?? $errors->first() }}</p>
        @endif

        <div class="workday-grid">
            <section class="dispatch-panel dispatch-panel-body" aria-labelledby="shift-title">
                <div class="workday-panel-heading">
                    <h2 id="shift-title" class="dispatch-heading">Mijn dienst</h2>
                    <span class="workday-state">{{ $activeShift ? 'In dienst' : 'Buiten dienst' }}</span>
                </div>
                @if ($activeShift)
                    <dl class="workday-shift-times">
                        <div><dt>Begonnen</dt><dd>{{ $activeShift->clocked_in_at->copy()->timezone('Europe/Amsterdam')->format('H:i') }}</dd></div>
                    </dl>
                @endif
                <form class="workday-actions" method="POST" action="{{ route('dashboard.driver.clock') }}">
                    @csrf
                    @if ($activeShift)
                        <button class="husso-button husso-button--plain" name="action" value="out">Dienst beëindigen</button>
                    @else
                        <button class="husso-button husso-button--primary" name="action" value="in">Dienst starten</button>
                    @endif
                </form>
            </section>

            <section class="dispatch-panel dispatch-panel-body" aria-labelledby="active-ride-title">
                <div class="workday-panel-heading">
                    <h2 id="active-ride-title" class="dispatch-heading">Actieve rit</h2>
                    @if ($activeBooking)<x-husso.status :code="$activeBooking->booking_status" />@endif
                </div>
                @if ($activeBooking)
                    <p class="workday-pickup-time">{{ substr($activeBooking->ride_time, 0, 5) }} <span>{{ $activeBooking->ride_date->format('d-m-Y') }}</span></p>
                    <div class="workday-route">
                        <p><span>Ophalen</span>{{ $activeBooking->pickup }}</p>
                        <p><span>Bestemming</span>{{ $activeBooking->destination }}</p>
                    </div>
                    <p class="workday-ride-meta">Ritprijs € {{ number_format($activeBooking->calculatedFare(), 2, ',', '.') }}</p>
                    <div class="workday-actions">
                        <a class="husso-button husso-button--plain" href="{{ route('admin.bookings.show', $activeBooking) }}">Ritdetails</a>
                    </div>
                @else
                    <p class="workday-empty">Geen actieve rit.</p>
                @endif
            </section>
        </div>

        <section class="dispatch-panel dispatch-panel-body" aria-labelledby="today-totals">
            <h2 id="today-totals" class="dispatch-heading">Overzicht vandaag</h2>
            <dl class="workday-totals">
                <div><dt>Ritten vandaag</dt><dd>{{ $ridesToday }}</dd></div>
                <div><dt>Ritbedrag vandaag</dt><dd>€ {{ number_format((float) $revenueToday, 2, ',', '.') }}</dd></div>
            </dl>
        </section>
    </main>
</x-app-layout>
