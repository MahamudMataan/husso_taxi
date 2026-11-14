<x-app-layout>
    <x-slot name="adminShell">true</x-slot>
    <x-husso.dispatch-header />

    @php
        $centerLat = $booking?->driver_latitude ?? $booking?->pickup_latitude;
        $centerLng = $booking?->driver_longitude ?? $booking?->pickup_longitude;
        $liveLabels = [
            'new' => 'Nieuw',
            'driver_assigned' => 'Toegewezen',
            'driver_en_route' => 'Onderweg naar klant',
            'arrived' => 'Aangekomen',
            'picked_up' => 'Rit bezig',
        ];
    @endphp

    <main class="husso-wrap dispatch-main" x-data="rideUpdates(@js(route('admin.live.state')), @js($booking?->id))">
        <h1 class="dispatch-title">Live ritoverzicht</h1>
        <p class="dispatch-muted mb-3" x-show="updatedAt" x-cloak>Bijgewerkt om <span x-text="updatedAt"></span></p>
        <p class="husso-notice husso-notice--error" role="status" x-show="offline" x-cloak>De laatste update is niet opgehaald. We proberen het opnieuw.</p>
        <p class="husso-notice" role="status" x-show="changedRide" x-cloak>De rit in dit overzicht is gewijzigd. <a href="{{ route('admin.live') }}">Vernieuw het overzicht</a></p>

        <div class="dispatch-grid">
            <section class="dispatch-panel dispatch-panel-body">
                <h2 class="dispatch-heading">Kaart</h2>
                @if ($centerLat !== null && $centerLng !== null)
                    <iframe class="dispatch-map mt-3" title="Kaart van de gevolgde rit" loading="lazy" referrerpolicy="no-referrer"
                        src="https://www.openstreetmap.org/export/embed.html?bbox={{ (float) $centerLng - 0.08 }}%2C{{ (float) $centerLat - 0.05 }}%2C{{ (float) $centerLng + 0.08 }}%2C{{ (float) $centerLat + 0.05 }}&layer=mapnik&marker={{ $centerLat }}%2C{{ $centerLng }}"></iframe>
                @else
                    <p class="dispatch-muted mt-3">Geen locatie beschikbaar voor dit overzicht.</p>
                @endif
            </section>

            <aside class="dispatch-stack">
                <section class="dispatch-panel dispatch-panel-body">
                    <h2 class="dispatch-heading">Chauffeur</h2>
                    <p class="mt-2 text-sm font-semibold" x-text="state ? (state.driver_online ? 'Online' : 'Offline') : $el.dataset.initial" data-initial="{{ $stats['driver_online'] ? 'Online' : 'Offline' }}">{{ $stats['driver_online'] ? 'Online' : 'Offline' }}</p>
                </section>
                <section class="dispatch-panel dispatch-panel-body">
                    <h2 class="dispatch-heading">Rit</h2>
                    @if ($booking)
                        <p class="mt-2 font-semibold">{{ $booking->reference }}</p>
                        <p class="dispatch-muted" data-initial="{{ $liveLabels[$booking->booking_status] ?? '' }}" x-text="sameRide ? @js($liveLabels)[state.active_booking.status] : $el.dataset.initial">{{ $liveLabels[$booking->booking_status] ?? '' }}</p>
                        <p class="dispatch-route mt-2 text-sm"><span>{{ $booking->pickup }}</span><span>naar {{ $booking->destination }}</span></p>
                        <div class="mt-2 flex flex-wrap gap-x-3">
                            <a class="dispatch-link" href="{{ route('admin.bookings.show', $booking) }}">Beheer</a>
                            <a class="dispatch-link" href="{{ $booking->trackingUrl() }}" target="_blank" rel="noopener">Volg rit</a>
                        </div>
                    @else
                        <p class="dispatch-muted mt-2">Geen open rit.</p>
                    @endif
                </section>
            </aside>
        </div>
    </main>
</x-app-layout>
