<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Ritbon {{ $booking->reference }} · Husso Taxi</title>
    <x-husso.favicon />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="husso min-h-screen">
    <header class="site-header">
        <div class="husso-wrap site-header-inner">
            <x-husso.brand />
        </div>
    </header>
    <main class="husso-wrap dispatch-main !max-w-3xl">
        <section class="dispatch-panel dispatch-panel-body">
            <div class="flex flex-col gap-3 border-b border-[var(--line)] pb-5 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 class="dispatch-title">Ritbon</h1>
                    <p class="dispatch-muted">{{ $booking->reference }}</p>
                </div>
                <p class="dispatch-muted">{{ $booking->ride_date->format('d-m-Y') }} · {{ substr((string) $booking->ride_time, 0, 5) }}</p>
            </div>

            <div class="mt-6 grid gap-6 sm:grid-cols-2">
                <div>
                    <h2 class="font-black">Klant</h2>
                    <p class="mt-2">{{ $booking->customer_name }}</p>
                    <p class="dispatch-muted break-words">{{ $booking->customer_email }}</p>
                </div>
                <div>
                    <h2 class="font-black">Rit</h2>
                    <p class="mt-2">{{ $booking->pickup }}</p>
                    <p class="dispatch-muted">naar {{ $booking->destination }}</p>
                </div>
            </div>

            <div class="mt-6 border-t border-[var(--line)] pt-4">
                <h2 class="dispatch-heading">Prijsopbouw</h2>
                <x-husso.fare :booking="$booking" />
            </div>
        </section>
    </main>
</body>
</html>
