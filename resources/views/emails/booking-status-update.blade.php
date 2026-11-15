@php
    $isEnglish = $booking->language === 'en';
    $labels = [
        'booking_created' => [
            'nl' => ['Je aanvraag is ontvangen', 'We hebben je rit ontvangen en controleren de planning. Je kunt de status live volgen via je trackinglink.'],
            'en' => ['Your request was received', 'We received your ride request and are checking availability. You can follow the live status with your tracking link.'],
        ],
        'booking_accepted' => [
            'nl' => ['Je rit is geaccepteerd', 'Je boeking is bevestigd. We houden je op de hoogte zodra een chauffeur onderweg is.'],
            'en' => ['Your ride is accepted', 'Your booking is confirmed. We will keep you updated when a driver is on the way.'],
        ],
        'driver_assigned' => [
            'nl' => ['Je chauffeur is toegewezen', 'Je chauffeur is gekoppeld aan deze rit. Bekijk je trackingpagina voor ETA en route.'],
            'en' => ['Your driver is assigned', 'Your driver has been assigned to this ride. Open the tracking page for ETA and route progress.'],
        ],
        'driver_arriving' => [
            'nl' => ['Je chauffeur is onderweg', 'Je chauffeur rijdt naar de ophaallocatie. Houd je telefoon bij de hand.'],
            'en' => ['Your driver is on the way', 'Your driver is heading to the pickup location. Please keep your phone nearby.'],
        ],
        'driver_arrived' => [
            'nl' => ['Je chauffeur is aangekomen', 'Je chauffeur staat bij de ophaallocatie.'],
            'en' => ['Your driver has arrived', 'Your driver is at the pickup location.'],
        ],
        'trip_started' => [
            'nl' => ['Je rit is gestart', 'De passagier is opgehaald en de rit is onderweg naar de bestemming.'],
            'en' => ['Your trip has started', 'The passenger has been picked up and the trip is heading to the destination.'],
        ],
        'trip_completed' => [
            'nl' => ['Je rit is afgerond', 'Bedankt voor je rit met Husso Taxi. We hopen je snel weer te rijden.'],
            'en' => ['Your trip is complete', 'Thank you for riding with Husso Taxi. We hope to drive you again soon.'],
        ],
        'payment_failed' => [
            'nl' => ['Je betaling is niet gelukt', 'De betaling voor je rit is mislukt of verlopen. Neem contact op zodat we dit snel kunnen oplossen.'],
            'en' => ['Your payment did not go through', 'The payment for your ride failed or expired. Please contact us so we can resolve it quickly.'],
        ],
        'payment_received' => [
            'nl' => ['Je betaling is ontvangen', 'Bekijk via je ritlink of je boeking al is bevestigd.'],
            'en' => ['Your payment was received', 'Use your ride link to check whether your booking has been confirmed.'],
        ],
        'trip_canceled' => [
            'nl' => ['Je rit is geannuleerd', 'Heb je vragen over de annulering of je betaling? Neem contact op met Husso Taxi.'],
            'en' => ['Your ride was cancelled', 'Contact Husso Taxi with questions about the cancellation or your payment.'],
        ],
    ];
    [$heading, $intro] = $labels[$event][$isEnglish ? 'en' : 'nl'] ?? ($isEnglish
        ? ['Husso Taxi update', 'There is an update about your booking.']
        : ['Husso Taxi update', 'Er is een update over je boeking.']);
@endphp

<!DOCTYPE html>
<html lang="{{ $isEnglish ? 'en' : 'nl' }}">
<body style="font-family: Arial, sans-serif; color: #18181b; line-height: 1.5;">
    <h1 style="margin-bottom: 8px;">{{ $heading }}</h1>
    <p style="margin-top: 0;">{{ $intro }}</p>
    <p>Referentie: <strong>{{ $booking->reference }}</strong></p>

    @include('emails.ride-links')

    <table style="border-collapse: collapse; width: 100%; max-width: 680px;">
        <tr><td style="padding: 6px 0; font-weight: bold;">{{ $isEnglish ? 'Pickup' : 'Ophaaladres' }}</td><td>{{ $booking->pickup }}</td></tr>
        <tr><td style="padding: 6px 0; font-weight: bold;">{{ $isEnglish ? 'Destination' : 'Bestemming' }}</td><td>{{ $booking->destination }}</td></tr>
        <tr><td style="padding: 6px 0; font-weight: bold;">{{ $isEnglish ? 'Date/time' : 'Datum/tijd' }}</td><td>{{ $booking->ride_date->format('d-m-Y') }} {{ substr((string) $booking->ride_time, 0, 5) }}</td></tr>
        <tr><td style="padding: 6px 0; font-weight: bold;">{{ $isEnglish ? 'Driver' : 'Chauffeur' }}</td><td>{{ $booking->driver_name ?: 'Husso Taxi' }}</td></tr>
        @if ($booking->booking_status === 'driver_en_route' && $booking->driver_eta_min !== null)
            <tr><td style="padding: 6px 0; font-weight: bold;">{{ $isEnglish ? 'Estimated pickup' : 'Geschatte ophaaltijd' }}</td><td>{{ $booking->driver_eta_min }} min</td></tr>
        @endif
        <tr><td style="padding: 6px 0; font-weight: bold;">{{ $isEnglish ? 'Payment' : 'Betaling' }}</td><td>{{ str_replace('_', ' ', $booking->payment_status) }}</td></tr>
    </table>

    <p style="margin-top: 24px; color: #52525b;">
        {{ $isEnglish ? 'Questions? Contact us on' : 'Vragen? Stuur ons een bericht via' }}
        <a href="https://wa.me/31686463268">WhatsApp</a>
        {{ $isEnglish ? 'or email' : 'of mail naar' }} <a href="mailto:info@husso.nl">info@husso.nl</a>.
    </p>
</body>
</html>
