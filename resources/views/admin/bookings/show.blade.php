<x-app-layout>
    <x-slot name="adminShell">true</x-slot>
    <x-husso.dispatch-header />

    @php
        $centerLat = $booking->driver_latitude ?? $booking->pickup_latitude;
        $centerLng = $booking->driver_longitude ?? $booking->pickup_longitude;
        $next = $booking->nextDispatchStatus();
        $actionLabel = [
            'driver_assigned' => 'Chauffeur toewijzen',
            'driver_en_route' => 'Markeer als onderweg',
            'arrived' => 'Markeer als aangekomen',
            'picked_up' => 'Rit starten',
            'completed' => 'Rit afronden',
        ][$next] ?? null;
    @endphp

    <main class="husso-wrap dispatch-main">
        <a class="dispatch-link" href="{{ route('admin.bookings.index', $returnFilters) }}">Terug naar boekingen</a>
        <h1 class="dispatch-title">{{ $booking->reference }}</h1>
        @if (session('status'))
            <p class="husso-notice" role="status">{{ session('status') }}</p>
        @endif
        @if ($errors->any())
            <div class="husso-notice husso-notice--error" role="alert">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <div class="dispatch-grid dispatch-grid--detail">
            <aside class="dispatch-stack dispatch-actions">
                <section class="dispatch-panel dispatch-panel-body">
                    <h2 class="dispatch-heading">Ritstatus</h2>
                    <div class="dispatch-statuses my-3">
                        <x-husso.status :code="$booking->booking_status" />
                        <x-husso.status :code="$booking->payment_status" kind="payment" />
                    </div>
                    @if ($next)
                        <form method="POST" action="{{ route('dashboard.bookings.dispatch', $booking) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="booking_status" value="{{ $next }}">
                            <button class="husso-button husso-button--primary w-full" type="submit">{{ $actionLabel }}</button>
                        </form>
                    @endif

                    @if ($booking->canCancelDispatch())
                        <details class="dispatch-exceptions mt-3" @if ($errors->has('reason') || old('booking_status') === 'canceled') open @endif>
                            <summary>Rit annuleren</summary>
                            <form class="grid gap-2" method="POST" action="{{ route('dashboard.bookings.dispatch', $booking) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="booking_status" value="canceled">
                                <label class="booking-field" for="reason">
                                    Reden voor annulering
                                    <textarea class="husso-input" id="reason" name="reason" rows="3" maxlength="500" required>{{ old('reason') }}</textarea>
                                </label>
                                <button class="husso-button husso-button--plain" type="submit">Annulering bevestigen</button>
                            </form>
                        </details>
                    @endif

                    @if ($booking->booking_status === 'new')
                        <details class="dispatch-exceptions mt-2">
                            <summary>Aanvraag weigeren</summary>
                            <form method="POST" action="{{ route('dashboard.bookings.review', $booking) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="admin_status" value="rejected">
                                <button class="husso-button husso-button--plain" type="submit">Weigering bevestigen</button>
                            </form>
                        </details>
                    @endif
                </section>

                <section class="dispatch-panel dispatch-panel-body">
                    <h2 class="dispatch-heading">Betaling</h2>
                    <form class="mt-3 grid gap-3" method="POST" action="{{ route('dashboard.bookings.payment', $booking) }}">
                        @csrf
                        @method('PATCH')
                        <div class="booking-field">
                            <label for="payment_status">Betaalstatus</label>
                            <select id="payment_status" class="husso-input" name="payment_status" required>
                                @if (! in_array($booking->payment_status, ['paid', 'pending', 'pay_later', 'failed', 'refunded'], true))
                                    <option value="" selected disabled>Kies een betaalstatus</option>
                                @endif
                                @foreach (['paid' => 'Betaald', 'pending' => 'Betaling open', 'pay_later' => 'Later betalen', 'failed' => 'Betaling mislukt', 'refunded' => 'Terugbetaald'] as $status => $label)
                                    <option value="{{ $status }}" @selected($booking->payment_status === $status)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button class="husso-button husso-button--plain" type="submit">Betaalstatus opslaan</button>
                    </form>
                </section>

                <section class="dispatch-panel dispatch-panel-body">
                    <h2 class="dispatch-heading">Prijsopbouw</h2>
                    <x-husso.fare :booking="$booking" />
                </section>
            </aside>

            <div class="dispatch-stack dispatch-detail">
                <section class="dispatch-panel dispatch-panel-body">
                    <h2 class="dispatch-heading">Ritgegevens</h2>
                    <dl class="dispatch-detail-list">
                        <div><dt class="dispatch-muted">Ophaallocatie</dt><dd>{{ $booking->pickup }}</dd></div>
                        <div><dt class="dispatch-muted">Bestemming</dt><dd>{{ $booking->destination }}</dd></div>
                        <div><dt class="dispatch-muted">Vertrek</dt><dd>{{ $booking->ride_date->format('d-m-Y') }} om {{ substr((string) $booking->ride_time, 0, 5) }}</dd></div>
                        <div><dt class="dispatch-muted">Passagiers</dt><dd>{{ $booking->passengers }}</dd></div>
                    </dl>
                    <div class="mt-2 flex flex-wrap gap-x-4">
                        <a class="dispatch-link" target="_blank" rel="noopener" href="{{ $booking->trackingUrl() }}">Volg rit</a>
                        <a class="dispatch-link" target="_blank" rel="noopener" href="{{ route('bookings.receipt', $booking->tracking_token) }}">Ritbon</a>
                    </div>
                </section>

                <div class="grid gap-4 sm:grid-cols-2">
                    <section class="dispatch-panel dispatch-panel-body">
                        <h2 class="dispatch-heading">Klant</h2>
                        <dl class="dispatch-detail-list">
                            <div><dt class="dispatch-muted">Naam</dt><dd>{{ $booking->customer_name }}</dd></div>
                            <div><dt class="dispatch-muted">E-mail</dt><dd>{{ $booking->customer_email }}</dd></div>
                            <div><dt class="dispatch-muted">Telefoon</dt><dd>{{ $booking->phone }}</dd></div>
                        </dl>
                    </section>
                    <section class="dispatch-panel dispatch-panel-body">
                        <h2 class="dispatch-heading">Chauffeur</h2>
                        @if ($booking->driver_name)
                            <p class="mt-3 font-semibold">{{ $booking->driver_name }}</p>
                            <p class="dispatch-muted">{{ $booking->driver_phone }}</p>
                            <p class="dispatch-muted">{{ $booking->driver_vehicle }}</p>
                        @else
                            <p class="dispatch-muted mt-3">Nog geen chauffeur toegewezen.</p>
                        @endif
                    </section>
                </div>

                @if ($centerLat !== null && $centerLng !== null)
                    <section class="dispatch-panel dispatch-panel-body">
                        <h2 class="dispatch-heading">Kaart</h2>
                        <iframe class="dispatch-map mt-3" title="Kaart van de rit" loading="lazy" referrerpolicy="no-referrer"
                            src="https://www.openstreetmap.org/export/embed.html?bbox={{ (float) $centerLng - 0.06 }}%2C{{ (float) $centerLat - 0.04 }}%2C{{ (float) $centerLng + 0.06 }}%2C{{ (float) $centerLat + 0.04 }}&layer=mapnik&marker={{ $centerLat }}%2C{{ $centerLng }}"></iframe>
                    </section>
                @endif

                <section class="dispatch-panel dispatch-panel-body">
                    <h2 class="dispatch-heading">Activiteitenlog</h2>
                    @if ($booking->dispatch_notes)
                        <p class="mt-3 whitespace-pre-wrap text-sm">{{ $booking->dispatch_notes }}</p>
                    @endif
                    <ul class="divide-y divide-[var(--line)]">
                        @forelse ($booking->notifications->sortByDesc('created_at') as $notification)
                            <li class="py-2 text-sm">
                                <p class="font-semibold">{{ [
                                    'booking_created' => 'Aanvraag ontvangen',
                                    'booking_accepted' => 'Bevestiging',
                                    'driver_assigned' => 'Chauffeur toegewezen',
                                    'driver_arriving' => 'Chauffeur onderweg of aangekomen',
                                    'trip_started' => 'Rit gestart',
                                    'trip_completed' => 'Rit afgerond',
                                    'payment_failed' => 'Betaling mislukt',
                                ][$notification->event] ?? 'Ritmelding' }}</p>
                                <p class="dispatch-muted">{{ ['queued' => 'In wachtrij', 'sent' => 'Verzonden', 'failed' => 'Verzending mislukt'][$notification->status] ?? $notification->status }} · {{ $notification->created_at->format('d-m-Y H:i') }}</p>
                            </li>
                        @empty
                            <li class="dispatch-muted pt-3">Nog geen meldingen.</li>
                        @endforelse
                    </ul>
                </section>
            </div>
        </div>
    </main>
</x-app-layout>
