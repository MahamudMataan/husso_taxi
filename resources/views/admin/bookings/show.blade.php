<x-app-layout>
    <x-slot name="adminShell">true</x-slot>

    @php
        $centerLat = $booking->driver_latitude ?: $booking->pickup_latitude;
        $centerLng = $booking->driver_longitude ?: $booking->pickup_longitude;
        $actions = [
            ['driver_assigned', 'Chauffeur toewijzen'],
            ['driver_en_route', 'Onderweg'],
            ['arrived', 'Aangekomen'],
            ['picked_up', 'Rit starten'],
            ['completed', 'Rit afronden'],
            ['canceled', 'Annuleren'],
        ];
    @endphp

    <div class="min-h-screen bg-slate-100 text-slate-950">
        <header class="border-b border-slate-200 bg-white px-4 py-4 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-wide text-yellow-600">Boeking #{{ $booking->id }}</p>
                    <h1 class="text-2xl font-black">{{ $booking->reference }}</h1>
                </div>
                <a class="rounded-lg bg-slate-950 px-4 py-2 text-sm font-black text-white" href="{{ route('admin.bookings.index') }}">Terug naar boekingen</a>
            </div>
        </header>

        <main class="grid gap-6 px-4 py-6 sm:px-6 lg:grid-cols-[minmax(0,1fr)_380px] lg:px-8">
            <section class="space-y-6">
                <div class="grid gap-6 xl:grid-cols-2">
                    <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <h2 class="text-lg font-black">Klantgegevens</h2>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div><dt class="text-slate-500">Naam</dt><dd class="font-bold">{{ $booking->customer_name }}</dd></div>
                            <div><dt class="text-slate-500">Email</dt><dd class="font-bold">{{ $booking->customer_email }}</dd></div>
                            <div><dt class="text-slate-500">Telefoon</dt><dd class="font-bold">{{ $booking->phone }}</dd></div>
                        </dl>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <h2 class="text-lg font-black">Ritgegevens</h2>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div><dt class="text-slate-500">Ophaallocatie</dt><dd class="font-bold">{{ $booking->pickup }}</dd></div>
                            <div><dt class="text-slate-500">Bestemming</dt><dd class="font-bold">{{ $booking->destination }}</dd></div>
                            <div><dt class="text-slate-500">Datum/tijd</dt><dd class="font-bold">{{ $booking->ride_date->format('d-m-Y') }} {{ substr((string) $booking->ride_time, 0, 5) }}</dd></div>
                        </dl>
                    </div>
                </div>

                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-black">Live kaart</h2>
                    <iframe class="mt-4 h-96 w-full rounded-lg" loading="lazy" referrerpolicy="no-referrer-when-downgrade" src="https://www.openstreetmap.org/export/embed.html?bbox={{ (float) $centerLng - 0.06 }}%2C{{ (float) $centerLat - 0.04 }}%2C{{ (float) $centerLng + 0.06 }}%2C{{ (float) $centerLat + 0.04 }}&layer=mapnik&marker={{ $centerLat }}%2C{{ $centerLng }}"></iframe>
                </div>

                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-black">Activiteitenlog</h2>
                    <div class="mt-4 space-y-3">
                        @forelse ($booking->notifications->sortByDesc('created_at') as $notification)
                            <div class="rounded-lg bg-slate-50 p-3 text-sm">
                                <p class="font-black">{{ str_replace('_', ' ', $notification->event) }} - {{ $notification->channel }}</p>
                                <p class="text-slate-500">{{ $notification->status }} - {{ $notification->created_at->format('d-m-Y H:i') }}</p>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">Nog geen activiteiten.</p>
                        @endforelse
                    </div>
                </div>
            </section>

            <aside class="space-y-6">
                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-black">Acties</h2>
                    <div class="mt-4 grid grid-cols-2 gap-2">
                        <a class="rounded-lg bg-yellow-400 px-4 py-3 text-center text-sm font-black text-slate-950" target="_blank" href="{{ $booking->trackingUrl() }}">Volg rit</a>
                        <a class="rounded-lg bg-slate-950 px-4 py-3 text-center text-sm font-black text-white" target="_blank" href="{{ route('bookings.receipt', $booking->tracking_token) }}">Ritbon</a>
                    </div>
                    <div class="mt-4 rounded-lg bg-slate-50 p-3 text-xs font-bold text-slate-600">
                        Tracking page:<br>
                        <span class="break-all text-slate-950">{{ $booking->trackingUrl() }}</span>
                    </div>
                    <div class="mt-4 grid gap-2">
                        @foreach ($actions as [$status, $label])
                            <form method="POST" action="{{ route('dashboard.bookings.dispatch', $booking) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="booking_status" value="{{ $status }}">
                                <button class="w-full rounded-lg border border-slate-300 px-4 py-3 text-sm font-black hover:bg-yellow-50" type="submit">{{ $label }}</button>
                            </form>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-black">Chauffeur</h2>
                    <p class="mt-3 font-bold">{{ $booking->driver_name ?: 'Omar Hussein' }}</p>
                    <p class="text-sm text-slate-500">{{ $booking->driver_phone ?: '+31 6 86463268' }}</p>
                    <p class="text-sm text-slate-500">{{ $booking->driver_vehicle ?: 'Husso Taxi' }}</p>
                </div>

                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-black">Prijsopbouw</h2>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex justify-between gap-4"><dt>{{ $booking->distance_km }} km x EUR 3,00</dt><dd class="font-black">EUR {{ number_format($booking->distanceFare(), 2, ',', '.') }}</dd></div>
                        <div class="flex justify-between gap-4"><dt>{{ $booking->duration_min }} min x EUR 0,50</dt><dd class="font-black">EUR {{ number_format($booking->timeFare(), 2, ',', '.') }}</dd></div>
                        <div class="flex justify-between gap-4 border-t border-slate-200 pt-3 text-base"><dt>Totaal</dt><dd class="font-black">EUR {{ number_format($booking->calculatedFare(), 2, ',', '.') }}</dd></div>
                    </dl>
                </div>
            </aside>
        </main>
    </div>
</x-app-layout>
