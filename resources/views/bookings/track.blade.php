@php
    $isEnglish = $booking->language === 'en';
    $statusOrder = ['new', 'driver_assigned', 'driver_en_route', 'picked_up', 'completed'];
    $statusLabels = [
        'new' => $isEnglish ? 'Booking received' : 'Boeking ontvangen',
        'driver_assigned' => $isEnglish ? 'Driver assigned' : 'Chauffeur toegewezen',
        'driver_en_route' => $isEnglish ? 'Driver arriving' : 'Chauffeur onderweg',
        'picked_up' => $isEnglish ? 'Trip started' : 'Rit gestart',
        'completed' => $isEnglish ? 'Completed' : 'Rit afgerond',
        'canceled' => $isEnglish ? 'Canceled' : 'Geannuleerd',
        'rejected' => $isEnglish ? 'Canceled' : 'Geannuleerd',
    ];
    $currentStatus = $booking->booking_status ?: ($booking->admin_status === 'accepted' ? 'driver_assigned' : 'new');
    $currentIndex = array_search($currentStatus, $statusOrder, true);
    $currentIndex = $currentIndex === false ? 0 : $currentIndex;
    $progress = in_array($currentStatus, ['rejected', 'canceled'], true) ? 0 : (($currentIndex + 1) / count($statusOrder)) * 100;
    $eta = $booking->driver_eta_min ?: $booking->duration_min;
    $centerLat = $booking->driver_latitude ?: $booking->pickup_latitude;
    $centerLng = $booking->driver_longitude ?: $booking->pickup_longitude;
@endphp

<!DOCTYPE html>
<html lang="{{ $isEnglish ? 'en' : 'nl' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $isEnglish ? 'Track your ride' : 'Volg je rit' }} - Husso Taxi</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 font-sans text-slate-950 antialiased" x-data="{
    state: null,
    copied: false,
    init() {
        this.fetchState();
        setInterval(() => this.fetchState(), 5000);
    },
    async fetchState() {
        const response = await fetch('{{ route('bookings.track.state', $booking->tracking_token) }}', { headers: { Accept: 'application/json' } });
        this.state = await response.json();
    },
    async shareLink() {
        await navigator.clipboard.writeText('{{ $booking->trackingUrl() }}');
        this.copied = true;
        setTimeout(() => this.copied = false, 1800);
    }
}">
    <main class="min-h-screen">
        <section class="bg-slate-950 px-4 py-8 text-white sm:px-6 lg:px-8">
            <div class="mx-auto max-w-6xl">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="text-xs font-black uppercase tracking-wide text-yellow-300">Husso Taxi</p>
                        <h1 class="mt-2 text-3xl font-black tracking-tight">{{ $isEnglish ? 'Track your ride' : 'Volg je rit' }}</h1>
                        <p class="mt-2 text-slate-300">{{ $booking->reference }}</p>
                    </div>
                    <div class="rounded-lg bg-white/10 px-4 py-3">
                        <p class="text-xs font-bold text-slate-300">ETA</p>
                        <p class="text-2xl font-black"><span x-text="state?.eta_min ?? '{{ $eta }}'">{{ $eta }}</span> min</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="mx-auto grid max-w-6xl gap-6 px-4 py-6 sm:px-6 lg:grid-cols-[minmax(0,1fr)_360px] lg:px-8">
            <div class="space-y-6">
                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm font-bold text-slate-500">{{ $isEnglish ? 'Status' : 'Status' }}</p>
                            <h2 class="mt-1 text-2xl font-black">{{ $statusLabels[$currentStatus] ?? $statusLabels['new'] }}</h2>
                            <p class="mt-1 text-xs font-bold text-slate-500">{{ $isEnglish ? 'Updates refresh every 5 seconds.' : 'Updates verversen elke 5 seconden.' }}</p>
                        </div>
                        <span class="rounded-full bg-yellow-100 px-3 py-1 text-sm font-black text-yellow-800">{{ str_replace('_', ' ', $booking->payment_status) }}</span>
                    </div>

                    <div class="mt-6 h-3 rounded-full bg-slate-100">
                        <div class="h-3 rounded-full bg-yellow-400" style="width: {{ $progress }}%"></div>
                    </div>

                    <div class="mt-6 grid gap-3 sm:grid-cols-5">
                        @foreach ($statusOrder as $index => $status)
                            <div class="rounded-lg border p-3 {{ $index <= $currentIndex && ! in_array($currentStatus, ['rejected', 'canceled'], true) ? 'border-yellow-300 bg-yellow-50' : 'border-slate-200 bg-slate-50' }}">
                                <div class="flex h-8 w-8 items-center justify-center rounded-full {{ $index <= $currentIndex && ! in_array($currentStatus, ['rejected', 'canceled'], true) ? 'bg-yellow-400 text-slate-950' : 'bg-white text-slate-400' }}">
                                    <span class="text-xs font-black">{{ $index + 1 }}</span>
                                </div>
                                <p class="mt-3 text-sm font-black">{{ $statusLabels[$status] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 p-5">
                        <h2 class="text-lg font-black">{{ $isEnglish ? 'Live map' : 'Live kaart' }}</h2>
                        <p class="text-sm text-slate-500">{{ $booking->booking_status === 'picked_up' ? 'Current Taxi Location -> Destination' : 'Driver -> Pickup -> Destination' }}</p>
                    </div>
                    <iframe class="h-[420px] w-full" loading="lazy" referrerpolicy="no-referrer-when-downgrade" src="https://www.openstreetmap.org/export/embed.html?bbox={{ (float) $centerLng - 0.05 }}%2C{{ (float) $centerLat - 0.03 }}%2C{{ (float) $centerLng + 0.05 }}%2C{{ (float) $centerLat + 0.03 }}&layer=mapnik&marker={{ $centerLat }}%2C{{ $centerLng }}"></iframe>
                </div>
            </div>

            <aside class="space-y-6">
                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-black">{{ $isEnglish ? 'Driver' : 'Chauffeur' }}</h2>
                    <div class="mt-4 rounded-lg bg-slate-50 p-4">
                        <p class="text-xl font-black">{{ $booking->driver_name ?: 'Omar Hussein' }}</p>
                        <p class="mt-1 text-sm font-bold text-slate-500">{{ $booking->driver_phone ?: '+31 6 86463268' }}</p>
                        <p class="mt-1 text-sm font-bold text-slate-500">{{ $booking->driver_vehicle ?: 'Husso Taxi' }}</p>
                        <p class="mt-1 text-sm font-bold text-slate-500">{{ $isEnglish ? 'Plate' : 'Kenteken' }}: TX-001-H</p>
                        <a class="mt-4 inline-flex rounded-lg bg-slate-950 px-4 py-2 text-sm font-black text-white" href="tel:{{ $booking->driver_phone ?: '+31686463268' }}">
                            {{ $isEnglish ? 'Call driver' : 'Bel chauffeur' }}
                        </a>
                    </div>
                </div>

                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-black">{{ $isEnglish ? 'Ride details' : 'Ritgegevens' }}</h2>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div><dt class="font-bold text-slate-500">{{ $isEnglish ? 'Pickup' : 'Ophaaladres' }}</dt><dd class="mt-1 font-semibold">{{ $booking->pickup }}</dd></div>
                        <div><dt class="font-bold text-slate-500">{{ $isEnglish ? 'Destination' : 'Bestemming' }}</dt><dd class="mt-1 font-semibold">{{ $booking->destination }}</dd></div>
                        <div><dt class="font-bold text-slate-500">{{ $isEnglish ? 'Date/time' : 'Datum/tijd' }}</dt><dd class="mt-1 font-semibold">{{ $booking->ride_date->format('d-m-Y') }} {{ substr((string) $booking->ride_time, 0, 5) }}</dd></div>
                        <div><dt class="font-bold text-slate-500">{{ $isEnglish ? 'Passengers' : 'Passagiers' }}</dt><dd class="mt-1 font-semibold">{{ $booking->passengers }}</dd></div>
                    </dl>
                </div>

                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-black">{{ $isEnglish ? 'Fare breakdown' : 'Prijsopbouw' }}</h2>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex justify-between gap-4"><dt>{{ $booking->distance_km }} km x EUR 3,00</dt><dd class="font-black">EUR {{ number_format($booking->distanceFare(), 2, ',', '.') }}</dd></div>
                        <div class="flex justify-between gap-4"><dt>{{ $booking->duration_min }} min x EUR 0,50</dt><dd class="font-black">EUR {{ number_format($booking->timeFare(), 2, ',', '.') }}</dd></div>
                        <div class="flex justify-between gap-4 border-t border-slate-200 pt-3 text-base"><dt>{{ $isEnglish ? 'Total' : 'Totaal' }}</dt><dd class="font-black">EUR {{ number_format($booking->calculatedFare(), 2, ',', '.') }}</dd></div>
                    </dl>
                </div>

                <button type="button" @click="shareLink()" class="block w-full rounded-lg border border-slate-300 bg-white px-4 py-3 text-center text-sm font-black text-slate-950 hover:bg-slate-50">
                    <span x-show="!copied">{{ $isEnglish ? 'Share tracking link' : 'Deel trackinglink' }}</span>
                    <span x-show="copied">{{ $isEnglish ? 'Copied' : 'Gekopieerd' }}</span>
                </button>

                <a class="block rounded-lg bg-yellow-400 px-4 py-3 text-center text-sm font-black text-slate-950 hover:bg-yellow-300" target="_blank" href="https://www.google.com/maps/dir/?api=1&origin={{ urlencode($booking->pickup) }}&destination={{ urlencode($booking->destination) }}">
                    {{ $isEnglish ? 'Open route' : 'Open route' }}
                </a>
            </aside>
        </section>
    </main>
</body>
</html>
