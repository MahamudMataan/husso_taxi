<x-app-layout>
    <x-slot name="adminShell">true</x-slot>

    @php
        $statusMeta = [
            'new' => ['label' => 'Nieuwe boeking', 'class' => 'bg-blue-50 text-blue-700 ring-blue-200', 'dot' => 'bg-blue-500'],
            'driver_assigned' => ['label' => 'Chauffeur toegewezen', 'class' => 'bg-indigo-50 text-indigo-700 ring-indigo-200', 'dot' => 'bg-indigo-500'],
            'driver_en_route' => ['label' => 'Chauffeur onderweg', 'class' => 'bg-amber-50 text-amber-700 ring-amber-200', 'dot' => 'bg-amber-500'],
            'picked_up' => ['label' => 'Passagier opgehaald', 'class' => 'bg-cyan-50 text-cyan-700 ring-cyan-200', 'dot' => 'bg-cyan-500'],
            'completed' => ['label' => 'Afgerond', 'class' => 'bg-emerald-50 text-emerald-700 ring-emerald-200', 'dot' => 'bg-emerald-500'],
            'rejected' => ['label' => 'Geweigerd', 'class' => 'bg-rose-50 text-rose-700 ring-rose-200', 'dot' => 'bg-rose-500'],
        ];
        $paymentMeta = [
            'paid' => ['label' => 'Betaald', 'class' => 'bg-emerald-50 text-emerald-700 ring-emerald-200'],
            'pending' => ['label' => 'In afwachting', 'class' => 'bg-amber-50 text-amber-700 ring-amber-200'],
            'pay_later' => ['label' => 'Later betalen', 'class' => 'bg-sky-50 text-sky-700 ring-sky-200'],
            'failed' => ['label' => 'Mislukt', 'class' => 'bg-rose-50 text-rose-700 ring-rose-200'],
            'refunded' => ['label' => 'Terugbetaald', 'class' => 'bg-slate-100 text-slate-700 ring-slate-200'],
            'checkout_failed' => ['label' => 'Mislukt', 'class' => 'bg-rose-50 text-rose-700 ring-rose-200'],
            'checkout_missing' => ['label' => 'Mislukt', 'class' => 'bg-rose-50 text-rose-700 ring-rose-200'],
            'expired' => ['label' => 'Verlopen', 'class' => 'bg-rose-50 text-rose-700 ring-rose-200'],
            'canceled' => ['label' => 'Geannuleerd', 'class' => 'bg-slate-100 text-slate-700 ring-slate-200'],
        ];
        $selectedBooking = $bookings->first() ?? $recentBookings->first();
        $totalPayments = max(1, $recentBookings->count());
        $paidCount = $recentBookings->where('payment_status', 'paid')->count();
        $pendingPaymentCount = $recentBookings->where('payment_status', 'pending')->count();
        $payLaterCount = $recentBookings->where('payment_status', 'pay_later')->count();
        $failedPaymentCount = $recentBookings->whereIn('payment_status', ['failed', 'checkout_failed', 'checkout_missing', 'expired', 'canceled'])->count();
        $mapBooking = $selectedBooking;
        $centerLat = $mapBooking?->pickup_latitude ?: '52.3676';
        $centerLng = $mapBooking?->pickup_longitude ?: '4.9041';
    @endphp

    <div class="min-h-screen bg-slate-100 text-slate-950">
        <div class="flex">
            <aside class="hidden min-h-screen w-72 shrink-0 bg-slate-950 px-5 py-6 text-white lg:block">
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-yellow-400 text-slate-950">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M5 17h14l-1.4-6.3A3 3 0 0 0 14.7 8H9.3a3 3 0 0 0-2.9 2.7L5 17Z" />
                            <path d="M7 17v2M17 17v2M7 13h10M9 8l1-3h4l1 3" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-lg font-black">Husso Control</p>
                        <p class="text-xs font-semibold text-slate-400">Dispatch operations</p>
                    </div>
                </div>

                <nav class="mt-9 space-y-1 text-sm font-bold">
                    @foreach ([['Dashboard', '#top'], ['Boekingen', '#bookings'], ['Tracking', '#tracking'], ['Chauffeurs', '#drivers'], ['Klanten', '#customers'], ['Betalingen', '#payments']] as [$label, $href])
                        <a class="flex items-center gap-3 rounded-lg px-4 py-3 text-slate-300 hover:bg-slate-900 hover:text-white {{ $loop->first ? 'bg-yellow-400 text-slate-950 hover:bg-yellow-400 hover:text-slate-950' : '' }}" href="{{ $href }}">
                            <span class="h-2 w-2 rounded-full {{ $loop->first ? 'bg-slate-950' : 'bg-slate-600' }}"></span>
                            {{ $label }}
                        </a>
                    @endforeach
                </nav>

                <div class="mt-10 rounded-lg border border-slate-800 bg-slate-900 p-4 text-sm">
                    <p class="font-black text-white">Dispatch focus</p>
                    <p class="mt-2 text-slate-400">Nieuwe aanvragen, open betalingen en actieve ritten staan bovenaan zodat het team sneller kan handelen.</p>
                </div>
            </aside>

            <section id="top" class="min-w-0 flex-1">
                <header class="sticky top-0 z-20 border-b border-slate-200 bg-white/95 px-4 py-4 backdrop-blur sm:px-6 lg:px-8">
                    <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                        <div>
                            <p class="text-xs font-black uppercase tracking-wide text-yellow-600">Operations panel</p>
                            <h1 class="mt-1 text-2xl font-black text-slate-950">Boekingen, dispatch en betalingen</h1>
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-bold text-slate-600">
                                Vandaag <span class="text-slate-950">{{ now()->format('d-m-Y') }}</span>
                            </span>
                            <a href="{{ url('/') }}" class="rounded-lg bg-slate-950 px-4 py-2 text-sm font-black text-white hover:bg-slate-800">Website bekijken</a>
                        </div>
                    </div>
                </header>

                <main class="space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                    @if (session('status'))
                        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-bold text-emerald-800">
                            {{ session('status') }}
                        </div>
                    @endif

                    <section class="grid gap-4 md:grid-cols-2 2xl:grid-cols-5">
                        @foreach ([
                            ['Ritten vandaag', $stats['today_bookings'], 'Actuele planning', 'bg-blue-50 text-blue-700', 'M12 8v4l3 3'],
                            ['Actieve chauffeurs', $stats['active_drivers'], $activeShift ? 'Online sinds '.$activeShift->clocked_in_at->format('H:i') : 'Geen actieve dienst', 'bg-emerald-50 text-emerald-700', 'M5 13l4 4L19 7'],
                            ['Omzet vandaag', 'EUR '.number_format((float) $stats['today_revenue'], 2, ',', '.'), 'Betaalde ritten', 'bg-violet-50 text-violet-700', 'M12 6v12M17 9.5A4 4 0 0 0 12 8a4 4 0 0 0 0 8 4 4 0 0 0 5-1.5'],
                            ['Open boekingen', $stats['pending_bookings'], 'Nog opvolgen', 'bg-amber-50 text-amber-700', 'M12 8v5l3 2'],
                            ['Mislukte betalingen', $stats['failed_payments'], 'Actie nodig', 'bg-rose-50 text-rose-700', 'M12 9v4m0 4h.01'],
                        ] as [$title, $value, $hint, $color, $path])
                            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-sm font-bold text-slate-500">{{ $title }}</p>
                                        <p class="mt-3 text-2xl font-black tracking-tight">{{ $value }}</p>
                                    </div>
                                    <div class="flex h-11 w-11 items-center justify-center rounded-lg {{ $color }}">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path d="{{ $path }}" />
                                        </svg>
                                    </div>
                                </div>
                                <p class="mt-4 text-xs font-bold text-slate-500">{{ $hint }}</p>
                            </div>
                        @endforeach
                    </section>

                    <section class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_380px]">
                        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <h2 class="text-lg font-black">Boekingen trend</h2>
                                    <p class="text-sm text-slate-500">Laatste 7 dagen op basis van ritdatum.</p>
                                </div>
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">{{ $stats['total_bookings'] }} totaal</span>
                            </div>
                            <div class="mt-6 grid h-64 grid-cols-7 items-end gap-3">
                                @foreach ($trendDays as $day)
                                    <div class="flex h-full flex-col justify-end gap-2">
                                        <div class="flex flex-1 items-end gap-1">
                                            <div class="w-full rounded-t bg-yellow-400" style="height: {{ max(8, ($day['bookings'] / $maxTrendBookings) * 100) }}%"></div>
                                            <div class="w-full rounded-t bg-slate-900" style="height: {{ max(8, ($day['revenue'] / $maxTrendRevenue) * 100) }}%"></div>
                                        </div>
                                        <div class="text-center">
                                            <p class="text-xs font-black text-slate-700">{{ $day['bookings'] }}</p>
                                            <p class="text-[11px] font-bold text-slate-400">{{ $day['label'] }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="mt-4 flex flex-wrap gap-4 text-xs font-bold text-slate-500">
                                <span class="flex items-center gap-2"><span class="h-3 w-3 rounded bg-yellow-400"></span>Boekingen</span>
                                <span class="flex items-center gap-2"><span class="h-3 w-3 rounded bg-slate-900"></span>Betaalde omzet</span>
                            </div>
                        </div>

                        <div id="payments" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                            <h2 class="text-lg font-black">Betalingen</h2>
                            <p class="mt-1 text-sm text-slate-500">Status los van de ritstatus.</p>
                            <div class="mt-5 space-y-4">
                                @foreach ([['Betaald', $paidCount, 'bg-emerald-500'], ['In afwachting', $pendingPaymentCount, 'bg-amber-500'], ['Later betalen', $payLaterCount, 'bg-sky-500'], ['Mislukt', $failedPaymentCount, 'bg-rose-500']] as [$label, $count, $bar])
                                    <div>
                                        <div class="flex justify-between text-sm font-bold">
                                            <span>{{ $label }}</span>
                                            <span>{{ $count }}</span>
                                        </div>
                                        <div class="mt-2 h-2 rounded-full bg-slate-100">
                                            <div class="h-2 rounded-full {{ $bar }}" style="width: {{ max(4, ($count / $totalPayments) * 100) }}%"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="mt-6 rounded-lg bg-slate-950 p-4 text-white">
                                <p class="text-sm font-bold text-slate-300">Totale betaalde omzet</p>
                                <p class="mt-2 text-2xl font-black">EUR {{ number_format((float) $stats['paid_revenue'], 2, ',', '.') }}</p>
                            </div>
                        </div>
                    </section>

                    <section id="bookings" class="rounded-lg border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-200 p-5">
                            <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                                <div>
                                    <h2 class="text-lg font-black">Dispatch queue</h2>
                                    <p class="text-sm text-slate-500">Nieuwe boeking -> chauffeur toegewezen -> onderweg -> opgehaald -> afgerond.</p>
                                </div>
                                <form method="GET" action="{{ route('dashboard') }}" class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                                    <input class="rounded-lg border-slate-300 text-sm" type="search" name="search" value="{{ $filters['search'] }}" placeholder="Zoek klant, telefoon, route">
                                    <select class="rounded-lg border-slate-300 text-sm" name="status">
                                        <option value="">Alle ritstatussen</option>
                                        @foreach ($statusMeta as $value => $meta)
                                            <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $meta['label'] }}</option>
                                        @endforeach
                                    </select>
                                    <select class="rounded-lg border-slate-300 text-sm" name="payment_status">
                                        <option value="">Alle betalingen</option>
                                        @foreach (['paid', 'pending', 'pay_later', 'failed', 'refunded'] as $value)
                                            <option value="{{ $value }}" @selected($filters['payment_status'] === $value)>{{ $paymentMeta[$value]['label'] }}</option>
                                        @endforeach
                                    </select>
                                    <div class="flex gap-2">
                                        <select class="min-w-0 flex-1 rounded-lg border-slate-300 text-sm" name="sort">
                                            <option value="newest" @selected($filters['sort'] === 'newest')>Nieuwste</option>
                                            <option value="ride_time" @selected($filters['sort'] === 'ride_time')>Rittijd</option>
                                            <option value="highest_value" @selected($filters['sort'] === 'highest_value')>Hoogste waarde</option>
                                        </select>
                                        <button class="rounded-lg bg-slate-950 px-4 text-sm font-black text-white hover:bg-slate-800" type="submit">Filter</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="bg-slate-50 text-left text-xs font-black uppercase text-slate-500">
                                    <tr>
                                        <th class="px-5 py-3">Boeking</th>
                                        <th class="px-5 py-3">Klant</th>
                                        <th class="px-5 py-3">Route</th>
                                        <th class="px-5 py-3">Planning</th>
                                        <th class="px-5 py-3">Ritstatus</th>
                                        <th class="px-5 py-3">Betaling</th>
                                        <th class="px-5 py-3">Chauffeur</th>
                                        <th class="px-5 py-3">Snelle acties</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @forelse ($bookings as $booking)
                                        @php
                                            $bookingStatus = $booking->booking_status ?: ($booking->admin_status === 'accepted' ? 'driver_assigned' : ($booking->admin_status === 'rejected' ? 'rejected' : 'new'));
                                            $status = $statusMeta[$bookingStatus] ?? $statusMeta['new'];
                                            $payment = $paymentMeta[$booking->payment_status] ?? ['label' => ucfirst(str_replace('_', ' ', $booking->payment_status)), 'class' => 'bg-slate-100 text-slate-700 ring-slate-200'];
                                        @endphp
                                        <tr class="bg-white align-top hover:bg-slate-50">
                                            <td class="px-5 py-4">
                                                <p class="font-black">{{ $booking->reference }}</p>
                                                <p class="mt-1 text-xs font-bold text-slate-500">EUR {{ number_format((float) $booking->quoted_amount, 2, ',', '.') }}</p>
                                            </td>
                                            <td class="px-5 py-4">
                                                <p class="font-bold">{{ $booking->customer_name }}</p>
                                                <p class="text-slate-500">{{ $booking->phone }}</p>
                                                <p class="text-slate-500">{{ $booking->customer_email }}</p>
                                            </td>
                                            <td class="max-w-sm px-5 py-4">
                                                <p class="truncate font-semibold">{{ $booking->pickup }}</p>
                                                <p class="truncate text-slate-500">naar {{ $booking->destination }}</p>
                                                <p class="mt-1 text-xs font-bold text-slate-500">{{ $booking->distance_km }} km · {{ $booking->duration_min }} min ETA</p>
                                            </td>
                                            <td class="whitespace-nowrap px-5 py-4 font-bold">
                                                {{ $booking->ride_date->format('d-m-Y') }}
                                                <p class="text-slate-500">{{ substr((string) $booking->ride_time, 0, 5) }}</p>
                                            </td>
                                            <td class="px-5 py-4">
                                                <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-black ring-1 {{ $status['class'] }}">
                                                    <span class="h-2 w-2 rounded-full {{ $status['dot'] }}"></span>{{ $status['label'] }}
                                                </span>
                                            </td>
                                            <td class="px-5 py-4">
                                                <form method="POST" action="{{ route('dashboard.bookings.payment', $booking) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <select class="rounded-full border-0 px-3 py-1 text-xs font-black ring-1 {{ $payment['class'] }}" name="payment_status" onchange="this.form.submit()">
                                                        @foreach (['paid', 'pending', 'pay_later', 'failed', 'refunded'] as $value)
                                                            <option value="{{ $value }}" @selected($booking->payment_status === $value)>{{ $paymentMeta[$value]['label'] }}</option>
                                                        @endforeach
                                                    </select>
                                                </form>
                                            </td>
                                            <td class="px-5 py-4">
                                                <p class="font-bold">{{ $booking->driver_name ?: 'Niet toegewezen' }}</p>
                                                <p class="text-xs text-slate-500">{{ $booking->driver_eta_min ? $booking->driver_eta_min.' min tot pickup' : 'Auto-assign op nabijheid' }}</p>
                                            </td>
                                            <td class="px-5 py-4">
                                                <div class="flex min-w-72 flex-wrap gap-2">
                                                    @if ($bookingStatus === 'new')
                                                        <form method="POST" action="{{ route('dashboard.bookings.review', $booking) }}">
                                                            @csrf
                                                            @method('PATCH')
                                                            <input type="hidden" name="admin_status" value="accepted">
                                                            <button class="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-black text-white hover:bg-emerald-700" type="submit">Accept</button>
                                                        </form>
                                                        <form method="POST" action="{{ route('dashboard.bookings.review', $booking) }}">
                                                            @csrf
                                                            @method('PATCH')
                                                            <input type="hidden" name="admin_status" value="rejected">
                                                            <button class="rounded-lg bg-rose-600 px-3 py-2 text-xs font-black text-white hover:bg-rose-700" type="submit">Reject</button>
                                                        </form>
                                                    @endif
                                                    @foreach ([['driver_assigned', 'Assign Driver'], ['driver_en_route', 'En Route'], ['picked_up', 'Picked Up'], ['completed', 'Complete']] as [$nextStatus, $label])
                                                        @if ($bookingStatus !== 'rejected' && $bookingStatus !== 'completed')
                                                            <form method="POST" action="{{ route('dashboard.bookings.dispatch', $booking) }}">
                                                                @csrf
                                                                @method('PATCH')
                                                                <input type="hidden" name="booking_status" value="{{ $nextStatus }}">
                                                                <button class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-black text-slate-700 hover:bg-slate-100" type="submit">{{ $label }}</button>
                                                            </form>
                                                        @endif
                                                    @endforeach
                                                    <a class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-black text-slate-700 hover:bg-slate-100" href="tel:{{ $booking->phone }}">Call Customer</a>
                                                    <a class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-black text-slate-700 hover:bg-slate-100" target="_blank" href="https://www.google.com/maps/dir/?api=1&origin={{ urlencode($booking->pickup) }}&destination={{ urlencode($booking->destination) }}">View Route</a>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="px-5 py-12 text-center text-slate-500">Geen boekingen gevonden.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="border-t border-slate-200 p-5">
                            {{ $bookings->links() }}
                        </div>
                    </section>

                    <section id="tracking" class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_380px]">
                        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                            <div class="flex flex-col gap-3 border-b border-slate-200 p-5 md:flex-row md:items-center md:justify-between">
                                <div>
                                    <h2 class="text-lg font-black">Maps & live tracking</h2>
                                    <p class="text-sm text-slate-500">Pickup, bestemming, routeafstand en ETA voor de geselecteerde boeking.</p>
                                </div>
                                @if ($selectedBooking)
                                    <span class="rounded-full bg-yellow-100 px-3 py-1 text-sm font-black text-yellow-800">{{ $selectedBooking->reference }}</span>
                                @endif
                            </div>
                            <div class="grid gap-0 lg:grid-cols-[1fr_300px]">
                                <div class="min-h-[360px] bg-slate-200">
                                    @if ($mapBooking)
                                        <iframe class="h-full min-h-[360px] w-full" loading="lazy" referrerpolicy="no-referrer-when-downgrade" src="https://www.openstreetmap.org/export/embed.html?bbox={{ (float) $centerLng - 0.05 }}%2C{{ (float) $centerLat - 0.03 }}%2C{{ (float) $centerLng + 0.05 }}%2C{{ (float) $centerLat + 0.03 }}&layer=mapnik&marker={{ $centerLat }}%2C{{ $centerLng }}"></iframe>
                                    @else
                                        <div class="flex h-full min-h-[360px] items-center justify-center text-sm font-bold text-slate-500">Nog geen route om te tonen.</div>
                                    @endif
                                </div>
                                <div class="space-y-4 p-5">
                                    @if ($selectedBooking)
                                        <div>
                                            <p class="text-xs font-black uppercase text-slate-400">Pickup</p>
                                            <p class="mt-1 font-bold">{{ $selectedBooking->pickup }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs font-black uppercase text-slate-400">Bestemming</p>
                                            <p class="mt-1 font-bold">{{ $selectedBooking->destination }}</p>
                                        </div>
                                        <div class="grid grid-cols-2 gap-3">
                                            <div class="rounded-lg bg-slate-50 p-3">
                                                <p class="text-xs font-bold text-slate-500">Afstand</p>
                                                <p class="mt-1 text-lg font-black">{{ $selectedBooking->distance_km }} km</p>
                                            </div>
                                            <div class="rounded-lg bg-slate-50 p-3">
                                                <p class="text-xs font-bold text-slate-500">ETA</p>
                                                <p class="mt-1 text-lg font-black">{{ $selectedBooking->driver_eta_min ?: $selectedBooking->duration_min }} min</p>
                                            </div>
                                        </div>
                                        <a class="block rounded-lg bg-slate-950 px-4 py-3 text-center text-sm font-black text-white hover:bg-slate-800" target="_blank" href="https://www.google.com/maps/dir/?api=1&origin={{ urlencode($selectedBooking->pickup) }}&destination={{ urlencode($selectedBooking->destination) }}">Route openen</a>
                                    @else
                                        <p class="text-sm text-slate-500">Nieuwe boekingen verschijnen hier zodra ze binnenkomen.</p>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <aside id="drivers" class="space-y-6">
                            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <h2 class="text-lg font-black">Chauffeurs</h2>
                                        <p class="text-sm text-slate-500">Online status en auto-assignment.</p>
                                    </div>
                                    <span class="rounded-full px-3 py-1 text-xs font-black {{ $activeShift ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : 'bg-slate-100 text-slate-700 ring-1 ring-slate-200' }}">
                                        {{ $activeShift ? 'Online' : 'Offline' }}
                                    </span>
                                </div>
                                <div class="mt-5 rounded-lg border border-slate-200 bg-slate-50 p-4">
                                    <p class="font-black">{{ $driverName }}</p>
                                    <p class="mt-1 text-sm text-slate-500">{{ $activeShift ? 'Actief sinds '.$activeShift->clocked_in_at->format('H:i') : 'Niet actief vandaag' }}</p>
                                    <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                                        <div class="rounded-lg bg-white p-3">
                                            <p class="font-bold text-slate-500">Ritten vandaag</p>
                                            <p class="mt-1 text-xl font-black">{{ $driverCompletedToday }}</p>
                                        </div>
                                        <div class="rounded-lg bg-white p-3">
                                            <p class="font-bold text-slate-500">Locatie</p>
                                            <p class="mt-1 text-xl font-black">{{ $activeShift ? 'Live' : '-' }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-4 grid grid-cols-2 gap-2">
                                    <form method="POST" action="{{ route('dashboard.driver.clock') }}">
                                        @csrf
                                        <input type="hidden" name="action" value="in">
                                        <button class="w-full rounded-lg bg-emerald-600 px-3 py-2 text-sm font-black text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-slate-300" type="submit" @disabled($activeShift)>Online</button>
                                    </form>
                                    <form method="POST" action="{{ route('dashboard.driver.clock') }}">
                                        @csrf
                                        <input type="hidden" name="action" value="out">
                                        <button class="w-full rounded-lg bg-slate-950 px-3 py-2 text-sm font-black text-white hover:bg-slate-800 disabled:cursor-not-allowed disabled:bg-slate-300" type="submit" @disabled(! $activeShift)>Offline</button>
                                    </form>
                                </div>
                                <div class="mt-5 rounded-lg bg-yellow-50 p-4 text-sm text-yellow-900">
                                    <p class="font-black">Auto-assignment</p>
                                    <p class="mt-1">Klaar voor toewijzing op basis van dichtstbijzijnde chauffeur, live locatie en beschikbaarheid.</p>
                                </div>
                            </div>
                        </aside>
                    </section>

                    <section id="customers" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 class="text-lg font-black">Klantprofielen</h2>
                                <p class="text-sm text-slate-500">Ritten, spending, laatste boeking, betaalvoorkeur en notities.</p>
                            </div>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">{{ $stats['unique_customers'] }} unieke klanten</span>
                        </div>
                        <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            @forelse ($customers as $customer)
                                <div class="rounded-lg border border-slate-200 p-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="font-black">{{ $customer['name'] }}</p>
                                            <p class="text-sm text-slate-500">{{ $customer['email'] }}</p>
                                        </div>
                                        <span class="rounded-full bg-emerald-50 px-2 py-1 text-xs font-black text-emerald-700 ring-1 ring-emerald-200">{{ $customer['total_rides'] }} ritten</span>
                                    </div>
                                    <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                                        <div><dt class="text-slate-500">Spending</dt><dd class="font-black">EUR {{ number_format($customer['total_spending'], 2, ',', '.') }}</dd></div>
                                        <div><dt class="text-slate-500">Laatste rit</dt><dd class="font-black">{{ $customer['last_booking']?->format('d-m-Y') }}</dd></div>
                                        <div><dt class="text-slate-500">Betaalmethode</dt><dd class="font-black">{{ str_replace('_', ' ', $customer['preferred_payment_method']) }}</dd></div>
                                        <div><dt class="text-slate-500">Telefoon</dt><dd class="font-black">{{ $customer['phone'] }}</dd></div>
                                    </dl>
                                    <p class="mt-4 rounded-lg bg-slate-50 p-3 text-sm font-semibold text-slate-600">{{ $customer['notes'] }}</p>
                                </div>
                            @empty
                                <p class="text-sm text-slate-500">Nog geen klantprofielen.</p>
                            @endforelse
                        </div>
                    </section>

                    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <h2 class="text-lg font-black">Automatische notificaties</h2>
                        <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-6">
                            @foreach (['Booking accepted', 'Driver assigned', 'Driver arriving', 'Trip starts', 'Trip completes', 'Payment fails'] as $event)
                                <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm font-black text-slate-700">{{ $event }}</div>
                            @endforeach
                        </div>
                    </section>
                </main>
            </section>
        </div>
    </div>
</x-app-layout>
