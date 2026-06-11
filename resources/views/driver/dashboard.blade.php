<x-app-layout>
    <x-slot name="adminShell">true</x-slot>

    <div class="min-h-screen bg-slate-100 text-slate-950">
        <header class="border-b border-slate-200 bg-white px-4 py-4 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-wide text-yellow-600">Driver</p>
                    <h1 class="text-2xl font-black">{{ $driverName }}</h1>
                </div>
                <span class="rounded-full px-3 py-1 text-sm font-black {{ $activeShift ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700' }}">{{ $activeShift ? 'Online' : 'Offline' }}</span>
            </div>
        </header>

        <main class="space-y-6 px-4 py-6 sm:px-6 lg:px-8">
            <section class="grid gap-4 md:grid-cols-3">
                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-bold text-slate-500">Ritten vandaag</p>
                    <p class="mt-3 text-3xl font-black">{{ $ridesToday }}</p>
                </div>
                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-bold text-slate-500">Omzet vandaag</p>
                    <p class="mt-3 text-3xl font-black">EUR {{ number_format((float) $revenueToday, 2, ',', '.') }}</p>
                </div>
                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-bold text-slate-500">Dienst</p>
                    <p class="mt-3 text-3xl font-black">{{ $activeShift ? 'Gestart' : 'Niet actief' }}</p>
                </div>
            </section>

            <section class="grid gap-6 lg:grid-cols-[360px_minmax(0,1fr)]">
                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-black">Dienst beheren</h2>
                    <div class="mt-5 grid gap-3">
                        <form method="POST" action="{{ route('dashboard.driver.clock') }}">
                            @csrf
                            <input type="hidden" name="action" value="in">
                            <button class="w-full rounded-lg bg-emerald-600 px-4 py-3 font-black text-white disabled:bg-slate-300" @disabled($activeShift)>Dienst starten</button>
                        </form>
                        <form method="POST" action="{{ route('dashboard.driver.clock') }}">
                            @csrf
                            <input type="hidden" name="action" value="out">
                            <button class="w-full rounded-lg bg-slate-950 px-4 py-3 font-black text-white disabled:bg-slate-300" @disabled(! $activeShift)>Dienst beeindigen</button>
                        </form>
                    </div>
                </div>

                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-black">Actieve rit</h2>
                    @if ($activeBooking)
                        <div class="mt-4 grid gap-4 md:grid-cols-2">
                            <div>
                                <p class="text-sm font-bold text-slate-500">Pickup</p>
                                <p class="mt-1 font-black">{{ $activeBooking->pickup }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-slate-500">Bestemming</p>
                                <p class="mt-1 font-black">{{ $activeBooking->destination }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-slate-500">Prijs</p>
                                <p class="mt-1 font-black">EUR {{ number_format($activeBooking->calculatedFare(), 2, ',', '.') }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-slate-500">Status</p>
                                <p class="mt-1 font-black">{{ str_replace('_', ' ', $activeBooking->booking_status) }}</p>
                            </div>
                        </div>
                    @else
                        <p class="mt-4 text-sm text-slate-500">Geen actieve rit.</p>
                    @endif
                </div>
            </section>
        </main>
    </div>
</x-app-layout>
