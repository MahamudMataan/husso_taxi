@php
    $isEnglish = $booking->language === 'en';
    $labels = [
        'new' => $isEnglish ? 'Booking request received' : 'Boekingsaanvraag ontvangen',
        'driver_assigned' => $isEnglish ? 'Driver assigned' : 'Chauffeur toegewezen',
        'driver_en_route' => $isEnglish ? 'Driver on the way' : 'Chauffeur onderweg',
        'arrived' => $isEnglish ? 'Driver arrived' : 'Chauffeur aangekomen',
        'picked_up' => $isEnglish ? 'Trip in progress' : 'Rit bezig',
        'completed' => $isEnglish ? 'Trip completed' : 'Rit afgerond',
        'canceled' => $isEnglish ? 'Trip canceled' : 'Rit geannuleerd',
        'rejected' => $isEnglish ? 'Booking declined' : 'Aanvraag geweigerd',
    ];
    $statusOrder = ['new', 'driver_assigned', 'driver_en_route', 'arrived', 'picked_up', 'completed'];
    $currentStatus = $booking->booking_status ?: 'new';
    $assignedStatuses = array_slice($statusOrder, 1);
    $hasDriver = in_array($currentStatus, $assignedStatuses, true);
    $eta = in_array($currentStatus, ['driver_en_route', 'arrived'], true) ? $booking->driver_eta_min : null;
    $centerLat = $booking->driver_latitude ?? $booking->pickup_latitude;
    $centerLng = $booking->driver_longitude ?? $booking->pickup_longitude;
@endphp

<!DOCTYPE html>
<html lang="{{ $isEnglish ? 'en' : 'nl' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex">
        <title>{{ $isEnglish ? 'Track your ride' : 'Volg je rit' }} | Husso Taxi</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="husso min-h-screen" x-data="rideUpdates(@js(route('bookings.track.state', $booking->tracking_token)), null, @js($isEnglish ? 'en-NL' : 'nl-NL'))">
        <header class="site-header">
            <div class="husso-wrap site-header-inner">
                <x-husso.brand :href="url($isEnglish ? '/?lang=en' : '/')" />
                <a class="husso-button husso-button--inverse" href="{{ route('contact', $isEnglish ? ['lang' => 'en'] : []) }}">Contact</a>
            </div>
        </header>
        <main class="husso-wrap dispatch-main">
            <h1 class="dispatch-title">{{ $isEnglish ? 'Track your ride' : 'Volg je rit' }}</h1>
            <p class="dispatch-muted mb-4">{{ $booking->reference }}</p>
            <div class="dispatch-grid">
                <div class="dispatch-stack">
                    <section class="dispatch-panel dispatch-panel-body">
                        <h2 class="dispatch-heading" x-text="@js($labels)[state?.booking_status ?? @js($currentStatus)] ?? $el.dataset.initial" data-initial="{{ $labels[$currentStatus] ?? $labels['new'] }}" aria-live="polite">{{ $labels[$currentStatus] ?? $labels['new'] }}</h2>
                        <div class="dispatch-statuses mt-3">
                            <x-husso.status :code="$booking->payment_status" kind="payment" :is-english="$isEnglish" :live-payment="true" />
                        </div>
                        <p class="mt-3 font-semibold" x-show="state ? state.eta_min !== null : @js($eta !== null)" @if ($eta === null) x-cloak @endif>
                            {{ $isEnglish ? 'Driver arrives in about' : 'Chauffeur arriveert over ongeveer' }} <span x-text="state ? state.eta_min + ' min' : $el.dataset.initial" data-initial="{{ $eta }} min">{{ $eta }} min</span>
                        </p>
                        <ol class="tracking-progress" x-show="@js($statusOrder).includes(state?.booking_status ?? @js($currentStatus))" @if (! in_array($currentStatus, $statusOrder, true)) x-cloak @endif>
                            @foreach ($statusOrder as $status)
                                <li @if ($status === $currentStatus) aria-current="step" @endif :aria-current="(state?.booking_status ?? @js($currentStatus)) === @js($status) ? 'step' : null">{{ $labels[$status] }}</li>
                            @endforeach
                        </ol>
                        <p class="dispatch-muted mt-2" x-show="updatedAt" x-cloak>{{ $isEnglish ? 'Updated at' : 'Bijgewerkt om' }} <span x-text="updatedAt"></span></p>
                        <p class="husso-notice husso-notice--error" role="status" x-show="offline" x-cloak>{{ $isEnglish ? 'Could not get the latest status. We will try again.' : 'De laatste status is niet opgehaald. We proberen het opnieuw.' }}</p>
                    </section>

                    @if ($centerLat !== null && $centerLng !== null)
                        <section class="dispatch-panel dispatch-panel-body">
                            <h2 class="dispatch-heading">{{ $isEnglish ? 'Ride map' : 'Ritkaart' }}</h2>
                            <iframe class="dispatch-map mt-3" title="{{ $isEnglish ? 'Map for this ride' : 'Kaart van deze rit' }}" loading="lazy" referrerpolicy="no-referrer"
                                src="https://www.openstreetmap.org/export/embed.html?bbox={{ (float) $centerLng - 0.05 }}%2C{{ (float) $centerLat - 0.03 }}%2C{{ (float) $centerLng + 0.05 }}%2C{{ (float) $centerLat + 0.03 }}&layer=mapnik&marker={{ $centerLat }}%2C{{ $centerLng }}"></iframe>
                        </section>
                    @endif
                </div>

                <aside class="dispatch-stack">
                    <section class="dispatch-panel dispatch-panel-body">
                        <h2 class="dispatch-heading">{{ $isEnglish ? 'Ride details' : 'Ritgegevens' }}</h2>
                        <dl class="dispatch-detail-list">
                            <div><dt class="dispatch-muted">{{ $isEnglish ? 'Pickup' : 'Ophaallocatie' }}</dt><dd>{{ $booking->pickup }}</dd></div>
                            <div><dt class="dispatch-muted">{{ $isEnglish ? 'Destination' : 'Bestemming' }}</dt><dd>{{ $booking->destination }}</dd></div>
                            <div><dt class="dispatch-muted">{{ $isEnglish ? 'Departure' : 'Vertrek' }}</dt><dd>{{ $booking->ride_date->format('d-m-Y') }} · {{ substr((string) $booking->ride_time, 0, 5) }}</dd></div>
                            <div><dt class="dispatch-muted">{{ $isEnglish ? 'Passengers' : 'Passagiers' }}</dt><dd>{{ $booking->passengers }}</dd></div>
                        </dl>
                    </section>
                    <section class="dispatch-panel dispatch-panel-body">
                        <h2 class="dispatch-heading">{{ $isEnglish ? 'Driver' : 'Chauffeur' }}</h2>
                        <p class="dispatch-muted mt-2" x-show="!@js($assignedStatuses).includes(state?.booking_status ?? @js($currentStatus))" @if ($hasDriver) x-cloak @endif>{{ $isEnglish ? 'No driver is assigned.' : 'Er is geen chauffeur toegewezen.' }}</p>
                        <div x-show="@js($assignedStatuses).includes(state?.booking_status ?? @js($currentStatus))" @unless ($hasDriver) x-cloak @endunless>
                            <p class="mt-2 font-semibold" x-text="state?.driver?.name ?? $el.dataset.initial" data-initial="{{ $booking->driver_name ?: 'Omar Hussein' }}">{{ $booking->driver_name ?: 'Omar Hussein' }}</p>
                            <p class="dispatch-muted" x-text="state?.driver?.vehicle ?? $el.dataset.initial" data-initial="{{ $booking->driver_vehicle ?: 'Husso Taxi' }}">{{ $booking->driver_vehicle ?: 'Husso Taxi' }}</p>
                            <p class="dispatch-muted">{{ $isEnglish ? 'Plate' : 'Kenteken' }} <span x-text="state?.driver?.plate ?? $el.dataset.initial" data-initial="TX-001-H">TX-001-H</span></p>
                            <a class="dispatch-link" href="tel:{{ $booking->driver_phone ?: '+31686463268' }}" data-phone="{{ $booking->driver_phone ?: '+31686463268' }}" :href="'tel:' + (state?.driver?.phone ?? $el.dataset.phone)">{{ $isEnglish ? 'Call driver' : 'Bel chauffeur' }}</a>
                        </div>
                    </section>
                    <section class="dispatch-panel dispatch-panel-body">
                        <h2 class="dispatch-heading">{{ $isEnglish ? 'Fare breakdown' : 'Prijsopbouw' }}</h2>
                        <x-husso.fare :booking="$booking" :is-english="$isEnglish" />
                    </section>
                    <button class="husso-button husso-button--plain" type="button" @click="copyLink()" x-cloak>
                        <span x-show="!copied">{{ $isEnglish ? 'Copy tracking link' : 'Kopieer trackinglink' }}</span>
                        <span x-show="copied" role="status">{{ $isEnglish ? 'Link copied' : 'Link gekopieerd' }}</span>
                    </button>
                    <p class="husso-notice husso-notice--error" role="status" x-show="copyFailed" x-cloak>{{ $isEnglish ? 'Copy the link from your browser’s address bar.' : 'Kopieer de link uit de adresbalk van je browser.' }}</p>
                    <a class="husso-button husso-button--primary" target="_blank" rel="noopener noreferrer" href="https://www.google.com/maps/dir/?api=1&origin={{ urlencode($booking->pickup) }}&destination={{ urlencode($booking->destination) }}">{{ $isEnglish ? 'Open route' : 'Open route' }}</a>
                </aside>
            </div>
        </main>
    </body>
</html>
