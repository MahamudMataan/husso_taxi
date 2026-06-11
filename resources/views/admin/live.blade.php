<x-app-layout>
    <x-slot name="adminShell">true</x-slot>

    @php
        $statusColors = [
            'new' => 'bg-yellow-400 text-slate-950',
            'driver_assigned' => 'bg-blue-500 text-white',
            'driver_en_route' => 'bg-blue-500 text-white',
            'arrived' => 'bg-green-500 text-white',
            'picked_up' => 'bg-green-500 text-white',
            'completed' => 'bg-green-600 text-white',
            'failed' => 'bg-red-500 text-white',
            'canceled' => 'bg-slate-400 text-white',
            'rejected' => 'bg-slate-400 text-white',
        ];
        $centerLat = $booking?->driver_latitude ?: ($booking?->pickup_latitude ?: '52.3676');
        $centerLng = $booking?->driver_longitude ?: ($booking?->pickup_longitude ?: '4.9041');
    @endphp

    <div class="min-h-screen bg-slate-950 text-white" x-data="{
        state: null,
        init() {
            this.fetchState();
            setInterval(() => this.fetchState(), 5000);
        },
        async fetchState() {
            const response = await fetch('{{ route('admin.live.state') }}', { headers: { Accept: 'application/json' } });
            this.state = await response.json();
        }
    }">
        <header class="flex flex-col gap-3 border-b border-white/10 px-4 py-4 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8">
            <div>
                <p class="text-xs font-black uppercase tracking-wide text-yellow-300">Live dispatch</p>
                <h1 class="text-2xl font-black">Control room</h1>
            </div>
            <nav class="flex flex-wrap gap-2 text-sm font-black">
                <a class="rounded-lg bg-yellow-400 px-4 py-2 text-slate-950" href="{{ route('admin.dashboard') }}">Dashboard</a>
                <a class="rounded-lg border border-white/20 px-4 py-2 text-white" href="{{ route('admin.bookings.index') }}">Boekingen</a>
            </nav>
        </header>

        <main class="grid min-h-[calc(100vh-73px)] gap-0 lg:grid-cols-[minmax(0,1fr)_420px]">
            <section class="relative min-h-[520px] bg-slate-800">
                @if ($booking)
                    <iframe class="h-full min-h-[520px] w-full opacity-90" loading="lazy" referrerpolicy="no-referrer-when-downgrade" src="https://www.openstreetmap.org/export/embed.html?bbox={{ (float) $centerLng - 0.08 }}%2C{{ (float) $centerLat - 0.05 }}%2C{{ (float) $centerLng + 0.08 }}%2C{{ (float) $centerLat + 0.05 }}&layer=mapnik&marker={{ $centerLat }}%2C{{ $centerLng }}"></iframe>
                @else
                    <div class="flex h-full min-h-[520px] items-center justify-center text-slate-400">Geen actieve rit.</div>
                @endif
                <div class="absolute left-4 top-4 rounded-lg bg-slate-950/90 p-4 shadow-2xl">
                    <p class="text-xs font-black uppercase text-yellow-300">Route flow</p>
                    <p class="mt-2 text-lg font-black">Driver -> Pickup -> Destination</p>
                    <p class="mt-1 text-sm text-slate-300">Polling elke 5 seconden</p>
                </div>
            </section>

            <aside class="space-y-5 overflow-y-auto border-l border-white/10 bg-slate-950 p-5">
                <div class="rounded-lg border border-white/10 bg-white/5 p-4">
                    <p class="text-sm font-bold text-slate-300">Chauffeur</p>
                    <p class="mt-2 text-2xl font-black" x-text="state?.driver_online ? 'Online' : '{{ $stats['driver_online'] ? 'Online' : 'Offline' }}'"></p>
                </div>

                @if ($booking)
                    <div class="rounded-lg border border-white/10 bg-white/5 p-4">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-sm font-bold text-slate-300">Actieve boeking</p>
                                <h2 class="mt-1 text-xl font-black">{{ $booking->reference }}</h2>
                            </div>
                            <span class="rounded-full px-3 py-1 text-xs font-black {{ $statusColors[$booking->booking_status] ?? 'bg-slate-500 text-white' }}">{{ str_replace('_', ' ', $booking->booking_status) }}</span>
                        </div>
                        <dl class="mt-5 space-y-4 text-sm">
                            <div><dt class="text-slate-400">Klant</dt><dd class="font-bold">{{ $booking->customer_name }}</dd></div>
                            <div><dt class="text-slate-400">Pickup</dt><dd class="font-bold">{{ $booking->pickup }}</dd></div>
                            <div><dt class="text-slate-400">Destination</dt><dd class="font-bold">{{ $booking->destination }}</dd></div>
                            <div><dt class="text-slate-400">Prijs</dt><dd class="font-bold">EUR {{ number_format($booking->calculatedFare(), 2, ',', '.') }}</dd></div>
                            <div><dt class="text-slate-400">ETA</dt><dd class="font-bold">{{ $booking->driver_eta_min ?: $booking->duration_min }} min</dd></div>
                        </dl>
                    </div>
                @else
                    <div class="rounded-lg border border-white/10 bg-white/5 p-4 text-slate-300">Geen actieve boeking.</div>
                @endif

                <div class="grid grid-cols-2 gap-3 text-sm font-black">
                    <span class="rounded-lg bg-yellow-400 p-3 text-slate-950">Geel = Nieuw</span>
                    <span class="rounded-lg bg-blue-500 p-3 text-white">Blauw = Toegewezen</span>
                    <span class="rounded-lg bg-green-500 p-3 text-white">Groen = Actief</span>
                    <span class="rounded-lg bg-red-500 p-3 text-white">Rood = Mislukt</span>
                    <span class="rounded-lg bg-slate-500 p-3 text-white">Grijs = Geannuleerd</span>
                </div>
            </aside>
        </main>
    </div>
</x-app-layout>
