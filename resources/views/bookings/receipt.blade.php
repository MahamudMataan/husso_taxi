<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ritbon {{ $booking->reference }} · Husso Taxi</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 font-sans text-slate-950 antialiased">
    <main class="mx-auto max-w-3xl px-4 py-8 sm:px-6">
        <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-3 border-b border-slate-200 pb-5 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-wide text-yellow-600">Husso Taxi</p>
                    <h1 class="mt-1 text-2xl font-black">Ritbon</h1>
                    <p class="text-sm text-slate-500">{{ $booking->reference }}</p>
                </div>
                <p class="text-right text-sm font-bold text-slate-500">{{ $booking->ride_date->format('d-m-Y') }} {{ substr((string) $booking->ride_time, 0, 5) }}</p>
            </div>

            <div class="mt-6 grid gap-6 sm:grid-cols-2">
                <div>
                    <h2 class="font-black">Klant</h2>
                    <p class="mt-2">{{ $booking->customer_name }}</p>
                    <p class="text-slate-500">{{ $booking->customer_email }}</p>
                </div>
                <div>
                    <h2 class="font-black">Rit</h2>
                    <p class="mt-2">{{ $booking->pickup }}</p>
                    <p class="text-slate-500">naar {{ $booking->destination }}</p>
                </div>
            </div>

            <div class="mt-8 rounded-lg bg-slate-50 p-5">
                <h2 class="font-black">Prijsopbouw</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between gap-4"><dt>{{ $booking->distance_km }} km x EUR 3,00</dt><dd class="font-black">EUR {{ number_format($booking->distanceFare(), 2, ',', '.') }}</dd></div>
                    <div class="flex justify-between gap-4"><dt>{{ $booking->duration_min }} min x EUR 0,50</dt><dd class="font-black">EUR {{ number_format($booking->timeFare(), 2, ',', '.') }}</dd></div>
                    <div class="flex justify-between gap-4 border-t border-slate-200 pt-3 text-lg"><dt class="font-black">Totaal</dt><dd class="font-black">EUR {{ number_format($booking->calculatedFare(), 2, ',', '.') }}</dd></div>
                </dl>
            </div>
        </section>
    </main>
</body>
</html>
