<x-app-layout>
    <x-slot name="adminShell">true</x-slot>
    <x-husso.dispatch-header />

        <main class="husso-wrap dispatch-main space-y-4">
            <h1 class="dispatch-title">{{ $driverName }}</h1>
            @if (session('status'))
                <p class="husso-notice" role="status">{{ session('status') }}</p>
            @endif
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
                            <button class="husso-button husso-button--primary w-full" @disabled($activeShift)>Dienst starten</button>
                        </form>
                        <form method="POST" action="{{ route('dashboard.driver.clock') }}">
                            @csrf
                            <input type="hidden" name="action" value="out">
                            <button class="husso-button husso-button--plain w-full" @disabled(! $activeShift)>Dienst beëindigen</button>
                        </form>
                    </div>
                </div>

                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-black">Actieve rit</h2>
                    @if ($activeBooking)
                        <div class="mt-4 grid gap-4 md:grid-cols-2">
                            <div>
                                <p class="text-sm font-bold text-slate-500">Ophaallocatie</p>
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
                                <div class="mt-1"><x-husso.status :code="$activeBooking->booking_status" /></div>
                            </div>
                        </div>
                    @else
                        <p class="mt-4 text-sm text-slate-500">Geen actieve rit.</p>
                    @endif
                </div>
            </section>
        </main>
</x-app-layout>
