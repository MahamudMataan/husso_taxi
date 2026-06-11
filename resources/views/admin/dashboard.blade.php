<x-app-layout>
    <x-slot name="adminShell">true</x-slot>

    @php
        $statusClasses = [
            'new' => 'bg-yellow-100 text-yellow-800',
            'driver_assigned' => 'bg-blue-100 text-blue-800',
            'driver_en_route' => 'bg-blue-100 text-blue-800',
            'arrived' => 'bg-green-100 text-green-800',
            'picked_up' => 'bg-green-100 text-green-800',
            'completed' => 'bg-emerald-100 text-emerald-800',
            'failed' => 'bg-red-100 text-red-800',
            'canceled' => 'bg-slate-200 text-slate-700',
            'rejected' => 'bg-slate-200 text-slate-700',
        ];
        $activeBooking = $bookings->first(fn ($booking) => in_array($booking->booking_status, ['driver_en_route', 'arrived', 'picked_up', 'driver_assigned', 'new'], true));
        $kpis = [
            ['Ritten vandaag', $stats['today_bookings'], 'Planning', 'bg-yellow-50 text-yellow-700 ring-yellow-200'],
            ['Omzet vandaag', 'EUR '.number_format((float) $stats['today_revenue'], 2, ',', '.'), 'Betaald + later', 'bg-emerald-50 text-emerald-700 ring-emerald-200'],
            ['Open boekingen', $stats['open_bookings'], 'Actie nodig', 'bg-blue-50 text-blue-700 ring-blue-200'],
            ['Actieve ritten', $stats['active_rides'], 'Onderweg', 'bg-green-50 text-green-700 ring-green-200'],
            ['Chauffeur', $stats['driver_online'] ? 'Online' : 'Offline', $stats['driver_online'] ? 'Dienst actief' : 'Niet ingeklokt', $stats['driver_online'] ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-slate-100 text-slate-700 ring-slate-200'],
            ['Gem. ritwaarde', 'EUR '.number_format((float) $stats['average_ride_value'], 2, ',', '.'), 'Vandaag', 'bg-violet-50 text-violet-700 ring-violet-200'],
        ];
    @endphp

    <div class="min-h-screen bg-slate-100 text-slate-950">
        <header class="border-b border-slate-200 bg-slate-950 px-4 py-5 text-white sm:px-6 lg:px-8">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-wide text-yellow-300">Husso Taxi dispatch</p>
                    <h1 class="mt-1 text-3xl font-black">Dispatch control room</h1>
                    <p class="mt-2 text-sm text-slate-300">Single-driver operatie met live opvolging, prijsopbouw en trackinglinks.</p>
                </div>
                <nav class="flex flex-wrap gap-2 text-sm font-black">
                    <a class="rounded-lg bg-yellow-400 px-4 py-2 text-slate-950" href="{{ route('admin.live') }}">Live control</a>
                    <a class="rounded-lg border border-white/20 px-4 py-2 text-white" href="{{ route('admin.bookings.index') }}">Boekingen</a>
                    <a class="rounded-lg border border-white/20 px-4 py-2 text-white" href="{{ route('driver.dashboard') }}">Driver</a>
                    <a class="rounded-lg border border-white/20 px-4 py-2 text-white" href="{{ url('/') }}">Website</a>
                </nav>
            </div>
        </header>

        <main class="grid gap-6 px-4 py-6 sm:px-6 xl:grid-cols-[minmax(0,1fr)_380px] lg:px-8">
            <section class="space-y-6">
                <div class="grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
                    @foreach ($kpis as [$title, $value, $hint, $class])
                        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-sm font-bold text-slate-500">{{ $title }}</p>
                                    <p class="mt-3 text-3xl font-black tracking-tight">{{ $value }}</p>
                                </div>
                                <span class="rounded-full px-3 py-1 text-xs font-black ring-1 {{ $class }}">{{ $hint }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
                    <div class="flex flex-col gap-3 border-b border-slate-200 p-5 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-lg font-black">Recente boekingen</h2>
                            <p class="text-sm text-slate-500">Open een boeking om te dispatchen of deel direct de trackinglink.</p>
                        </div>
                        <a class="rounded-lg bg-slate-950 px-4 py-2 text-sm font-black text-white" href="{{ route('admin.bookings.index') }}">Alle boekingen</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 text-left text-xs font-black uppercase text-slate-500">
                                <tr>
                                    <th class="px-5 py-3">ID</th>
                                    <th class="px-5 py-3">Klant</th>
                                    <th class="px-5 py-3">Route</th>
                                    <th class="px-5 py-3">Prijs</th>
                                    <th class="px-5 py-3">Status</th>
                                    <th class="px-5 py-3">Acties</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($bookings as $booking)
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-5 py-4 font-black"><a href="{{ route('admin.bookings.show', $booking) }}">#{{ $booking->id }}</a></td>
                                        <td class="px-5 py-4">
                                            <p class="font-bold">{{ $booking->customer_name }}</p>
                                            <p class="text-xs text-slate-500">{{ $booking->phone }}</p>
                                        </td>
                                        <td class="max-w-md px-5 py-4">
                                            <p class="truncate font-semibold">{{ $booking->pickup }}</p>
                                            <p class="truncate text-slate-500">naar {{ $booking->destination }}</p>
                                        </td>
                                        <td class="px-5 py-4 font-black">EUR {{ number_format($booking->calculatedFare(), 2, ',', '.') }}</td>
                                        <td class="px-5 py-4"><span class="rounded-full px-2 py-1 text-xs font-black {{ $statusClasses[$booking->booking_status] ?? 'bg-slate-100 text-slate-700' }}">{{ str_replace('_', ' ', $booking->booking_status) }}</span></td>
                                        <td class="px-5 py-4">
                                            <div class="flex flex-wrap gap-2">
                                                <a class="rounded-lg bg-slate-950 px-3 py-2 text-xs font-black text-white" href="{{ route('admin.bookings.show', $booking) }}">Beheer</a>
                                                <a class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-black text-slate-700" target="_blank" href="{{ $booking->trackingUrl() }}">Volg rit</a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="px-5 py-10 text-center text-slate-500">Nog geen boekingen.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </section>

            <aside class="space-y-6">
                <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-black">Live status</h2>
                            <p class="text-sm text-slate-500">Chauffeur en actieve rit.</p>
                        </div>
                        <span class="rounded-full px-3 py-1 text-xs font-black {{ $stats['driver_online'] ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700' }}">{{ $stats['driver_online'] ? 'Online' : 'Offline' }}</span>
                    </div>
                    @if ($activeBooking)
                        <div class="mt-5 rounded-lg bg-slate-50 p-4">
                            <p class="text-xs font-black uppercase text-slate-500">Actieve boeking</p>
                            <p class="mt-1 text-xl font-black">{{ $activeBooking->reference }}</p>
                            <p class="mt-2 text-sm font-semibold text-slate-600">{{ $activeBooking->pickup }}</p>
                            <p class="text-sm text-slate-500">naar {{ $activeBooking->destination }}</p>
                            <div class="mt-4 grid grid-cols-2 gap-2">
                                <a class="rounded-lg bg-yellow-400 px-3 py-2 text-center text-xs font-black text-slate-950" href="{{ route('admin.live') }}">Live openen</a>
                                <a class="rounded-lg border border-slate-300 px-3 py-2 text-center text-xs font-black text-slate-700" target="_blank" href="{{ $activeBooking->trackingUrl() }}">Tracking</a>
                            </div>
                        </div>
                    @else
                        <p class="mt-5 rounded-lg bg-slate-50 p-4 text-sm font-semibold text-slate-500">Geen actieve rit.</p>
                    @endif
                </section>

                <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-black">Prijsmodel</h2>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex justify-between"><dt>Per kilometer</dt><dd class="font-black">EUR 3,00</dd></div>
                        <div class="flex justify-between"><dt>Per minuut</dt><dd class="font-black">EUR 0,50</dd></div>
                        <div class="rounded-lg bg-yellow-50 p-3 font-black text-yellow-900">fare = km x 3,00 + min x 0,50</div>
                    </dl>
                </section>
            </aside>
        </main>
    </div>
</x-app-layout>
