@php
    $isEnglish = $booking->language === 'en';
    $isAccepted = $booking->admin_status === 'accepted';
    $isRejected = $booking->admin_status === 'rejected';
    $pickupIsGps = str_contains(strtolower($booking->pickup), 'current location') || str_contains(strtolower($booking->pickup), 'huidige locatie');
    $trackingUrl = $booking->trackingUrl();
    $receiptUrl = route('bookings.receipt', $booking->tracking_token);

    $heading = $forStaff
        ? ($isAccepted ? 'Rit geaccepteerd' : ($isRejected ? 'Rit geweigerd' : 'Nieuwe ritaanvraag'))
        : ($isEnglish
            ? ($isAccepted ? 'Your ride is confirmed' : ($isRejected ? 'Your ride request was declined' : 'Your ride request was received'))
            : ($isAccepted ? 'Je rit is bevestigd' : ($isRejected ? 'Je ritaanvraag is geweigerd' : 'Je ritaanvraag is ontvangen')));

    $intro = $forStaff
        ? 'Onderstaande ritgegevens zijn bijgewerkt.'
        : ($isEnglish
            ? ($isAccepted
                ? 'Husso Taxi has accepted your ride. Your driver is Omar Hussein.'
                : ($isRejected
                    ? 'Unfortunately Husso Taxi cannot accept this ride request. Please contact us if you want to choose another time or route.'
                    : 'Husso Taxi received your request. We will review the ride and send confirmation as soon as possible.'))
            : ($isAccepted
                ? 'Husso Taxi heeft je rit geaccepteerd. Je chauffeur is Omar Hussein.'
                : ($isRejected
                    ? 'Husso Taxi kan deze ritaanvraag helaas niet aannemen. Neem contact op als je een andere tijd of route wilt kiezen.'
                    : 'Husso Taxi heeft je aanvraag ontvangen. We controleren de rit en sturen zo snel mogelijk een bevestiging.')));
@endphp

<!DOCTYPE html>
<html lang="{{ $isEnglish ? 'en' : 'nl' }}">
<body style="font-family: Arial, sans-serif; color: #18181b; line-height: 1.5;">
    <h1 style="margin-bottom: 8px;">{{ $heading }}</h1>
    <p style="margin-top: 0;">{{ $intro }}</p>
    <p style="margin-top: 0;">Referentie: <strong>{{ $booking->reference }}</strong></p>

    @unless ($forStaff || $isRejected)
        <p style="margin: 24px 0;">
            <a href="{{ $trackingUrl }}" style="display: inline-block; background: #020617; color: #ffffff; padding: 12px 18px; border-radius: 8px; text-decoration: none; font-weight: bold;">
                {{ $isEnglish ? 'Track my ride' : 'Volg mijn rit' }}
            </a>
            <a href="{{ $receiptUrl }}" style="display: inline-block; margin-left: 8px; background: #facc15; color: #020617; padding: 12px 18px; border-radius: 8px; text-decoration: none; font-weight: bold;">
                {{ $isEnglish ? 'Open receipt' : 'Open ritbon' }}
            </a>
        </p>

        <p style="margin-top: 0; color: #52525b; word-break: break-all;">
            {{ $isEnglish ? 'Tracking link:' : 'Trackinglink:' }} <a href="{{ $trackingUrl }}">{{ $trackingUrl }}</a><br>
            {{ $isEnglish ? 'Receipt:' : 'Ritbon:' }} <a href="{{ $receiptUrl }}">{{ $receiptUrl }}</a>
        </p>
    @endunless

    <table style="border-collapse: collapse; width: 100%; max-width: 680px;">
        <tr><td style="padding: 6px 0; font-weight: bold;">Naam</td><td>{{ $booking->customer_name }}</td></tr>
        <tr><td style="padding: 6px 0; font-weight: bold;">E-mail</td><td>{{ $booking->customer_email }}</td></tr>
        <tr><td style="padding: 6px 0; font-weight: bold;">Telefoon</td><td>{{ $booking->phone }}</td></tr>
        <tr>
            <td style="padding: 6px 0; font-weight: bold;">Ophaal</td>
            <td>
                {{ $booking->pickup }}
                @if ($pickupIsGps)
                    <br><span style="color: #52525b;">GPS: {{ $booking->pickup_latitude }}, {{ $booking->pickup_longitude }}</span>
                @endif
            </td>
        </tr>
        <tr><td style="padding: 6px 0; font-weight: bold;">Bestemming</td><td>{{ $booking->destination }}</td></tr>
        <tr><td style="padding: 6px 0; font-weight: bold;">Datum/tijd</td><td>{{ $booking->ride_date->format('d-m-Y') }} {{ substr((string) $booking->ride_time, 0, 5) }}</td></tr>
        <tr><td style="padding: 6px 0; font-weight: bold;">Passagiers</td><td>{{ $booking->passengers }}</td></tr>
        <tr><td style="padding: 6px 0; font-weight: bold;">Type rit</td><td>{{ str_replace('_', ' ', $booking->ride_type) }}</td></tr>
        <tr><td style="padding: 6px 0; font-weight: bold;">Afstand/tijd</td><td>{{ $booking->distance_km }} km / {{ $booking->duration_min }} min</td></tr>
        <tr><td style="padding: 6px 0; font-weight: bold;">Prijs</td><td>EUR {{ number_format((float) $booking->quoted_amount, 2, ',', '.') }}</td></tr>
        <tr><td style="padding: 6px 0; font-weight: bold;">Betaling</td><td>{{ $booking->payment_method }} / {{ $booking->payment_status }}</td></tr>
        <tr><td style="padding: 6px 0; font-weight: bold;">Status</td><td>{{ $booking->admin_status }}</td></tr>
        <tr><td style="padding: 6px 0; font-weight: bold;">Chauffeur</td><td>{{ $booking->driver_name ?? 'Omar Hussein' }}</td></tr>
    </table>

    @unless ($forStaff)
        <p style="margin-top: 24px;">
            {{ $isEnglish
                ? 'Questions? Call +31 6 86463268 or email info@husso.nl.'
                : 'Vragen? Bel +31 6 86463268 of mail naar info@husso.nl.' }}
        </p>
    @endunless
</body>
</html>
