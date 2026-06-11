<x-app-layout>
    <x-slot name="adminShell">true</x-slot>

    <div class="min-h-screen bg-slate-100 text-slate-950">
        <header class="border-b border-slate-200 bg-white px-4 py-4 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-wide text-yellow-600">Admin</p>
                    <h1 class="text-2xl font-black">Boekingen</h1>
                </div>
                <a class="rounded-lg bg-slate-950 px-4 py-2 text-sm font-black text-white" href="{{ route('admin.dashboard') }}">Dashboard</a>
            </div>
        </header>

        <main class="px-4 py-6 sm:px-6 lg:px-8">
            <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 p-5">
                    <form method="GET" class="flex flex-col gap-3 sm:flex-row">
                        <input class="rounded-lg border-slate-300 text-sm sm:w-96" name="search" value="{{ $search }}" placeholder="Zoek klant, ID, route">
                        <button class="rounded-lg bg-yellow-400 px-4 py-2 text-sm font-black text-slate-950">Zoeken</button>
                    </form>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-black uppercase text-slate-500">
                            <tr>
                                <th class="px-5 py-3">ID</th>
                                <th class="px-5 py-3">Klant</th>
                                <th class="px-5 py-3">Ophaallocatie</th>
                                <th class="px-5 py-3">Bestemming</th>
                                <th class="px-5 py-3">Prijs</th>
                                <th class="px-5 py-3">Status</th>
                                <th class="px-5 py-3">Betaling</th>
                                <th class="px-5 py-3">Acties</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($bookings as $booking)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-5 py-4 font-black"><a href="{{ route('admin.bookings.show', $booking) }}">#{{ $booking->id }}</a></td>
                                    <td class="px-5 py-4">{{ $booking->customer_name }}</td>
                                    <td class="max-w-xs truncate px-5 py-4">{{ $booking->pickup }}</td>
                                    <td class="max-w-xs truncate px-5 py-4">{{ $booking->destination }}</td>
                                    <td class="px-5 py-4 font-black">EUR {{ number_format($booking->calculatedFare(), 2, ',', '.') }}</td>
                                    <td class="px-5 py-4">{{ str_replace('_', ' ', $booking->booking_status) }}</td>
                                    <td class="px-5 py-4">{{ str_replace('_', ' ', $booking->payment_status) }}</td>
                                    <td class="px-5 py-4">
                                        <div class="flex flex-wrap gap-2">
                                            <a class="rounded-lg bg-slate-950 px-3 py-2 text-xs font-black text-white" href="{{ route('admin.bookings.show', $booking) }}">Beheer</a>
                                            <a class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-black text-slate-700" target="_blank" href="{{ $booking->trackingUrl() }}">Volg rit</a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="px-5 py-10 text-center text-slate-500">Geen boekingen gevonden.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-200 p-5">{{ $bookings->links() }}</div>
            </section>
        </main>
    </div>
</x-app-layout>
