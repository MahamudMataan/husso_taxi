@php
    $isEnglish = ! $forStaff && $booking->language === 'en';
    $isAccepted = $event === 'booking_accepted';
    $isRejected = $event === 'booking_declined';
    $pickupIsGps = str_contains(strtolower($booking->pickup), 'current location') || str_contains(strtolower($booking->pickup), 'huidige locatie');

    $heading = $forStaff
        ? ($isAccepted ? 'Rit geaccepteerd' : ($isRejected ? 'Rit geweigerd' : 'Nieuwe ritaanvraag'))
        : ($isEnglish
            ? ($isAccepted ? 'Your ride is confirmed' : ($isRejected ? 'Your ride request was declined' : 'Your ride request was received'))
            : ($isAccepted ? 'Je rit is bevestigd' : ($isRejected ? 'Je ritaanvraag is geweigerd' : 'Je ritaanvraag is ontvangen')));

    $intro = $forStaff
        ? 'Bekijk de ritaanvraag en bevestig de rit in Ritbeheer.'
        : ($isEnglish
            ? ($isAccepted
                ? 'Husso Taxi has accepted your ride.'
                : ($isRejected
                    ? 'Unfortunately Husso Taxi cannot accept this ride request. Please contact us if you want to choose another time or route.'
                    : 'Husso Taxi received your request. We will review the ride and send confirmation as soon as possible.'))
            : ($isAccepted
                ? 'Husso Taxi heeft je rit geaccepteerd.'
                : ($isRejected
                    ? 'Husso Taxi kan deze ritaanvraag helaas niet aannemen. Neem contact op als je een andere tijd of route wilt kiezen.'
                    : 'Husso Taxi heeft je aanvraag ontvangen. We controleren de rit en sturen zo snel mogelijk een bevestiging.')));
@endphp

<!DOCTYPE html>
<html lang="{{ $isEnglish ? 'en' : 'nl' }}">
<body style="font-family: Arial, sans-serif; color: #18181b; line-height: 1.5;">
    <img src="{{ asset('images/husso-taxi-mark.webp') }}" width="56" height="56" alt="Husso Taxi">
    <h1 style="margin-bottom: 8px;">{{ $heading }}</h1>
    <p style="margin-top: 0;">{{ $intro }}</p>
    <p style="margin-top: 0;">Referentie: <strong>{{ $booking->reference }}</strong></p>
    @if ($forStaff)
        <p><a href="{{ route('admin.bookings.show', $booking) }}" style="display:inline-block;background:#FFCC00;color:#111111;padding:12px 18px;text-decoration:none;font-weight:bold;">Open boeking in Ritbeheer</a></p>
    @endif

    @include('emails.ride-links')

    <h2 style="font-size:20px;">{{ $isEnglish ? 'Trip receipt' : 'Ritbon' }}</h2>
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
        @if ($booking->departure_supplement_cents > 0)
            <tr><td style="padding:6px 0;font-weight:bold;">{{ $isEnglish ? 'Minimum fare adjustment' : 'Uitrijkosten' }}</td><td>€ {{ number_format($booking->departure_supplement_cents / 100, 2, ',', '.') }}</td></tr>
        @endif
        <tr><td style="padding: 6px 0; font-weight: bold;">{{ $isEnglish ? 'Total' : 'Totaal' }}</td><td>€ {{ number_format((float) $booking->quoted_amount, 2, ',', '.') }}</td></tr>
        <tr><td style="padding: 6px 0; font-weight: bold;">{{ $isEnglish ? 'Payment method' : 'Betaalwijze' }}</td><td>{{ $booking->paymentMethodLabel($isEnglish) }}</td></tr>
        <tr><td style="padding: 6px 0; font-weight: bold;">{{ $isEnglish ? 'Payment status' : 'Betaalstatus' }}</td><td><x-husso.status :code="$booking->payment_status" kind="payment" :is-english="$isEnglish" /></td></tr>
        <tr><td style="padding: 6px 0; font-weight: bold;">Status</td><td>{{ $isAccepted ? ($isEnglish ? 'Confirmed' : 'Bevestigd') : ($isRejected ? ($isEnglish ? 'Declined' : 'Geweigerd') : ($isEnglish ? 'Request received' : 'Aanvraag ontvangen')) }}</td></tr>
        <tr><td style="padding: 6px 0; font-weight: bold;">{{ $isEnglish ? 'Invoice requested' : 'Factuur gewenst' }}</td><td>{{ $booking->invoice_details ? ($isEnglish ? 'Yes' : 'Ja') : ($isEnglish ? 'No' : 'Nee') }}</td></tr>
        @if ($booking->customer_notes)
            <tr><td style="padding: 6px 0; font-weight: bold;">{{ $isEnglish ? 'Notes' : 'Opmerkingen' }}</td><td>{{ $booking->customer_notes }}</td></tr>
        @endif
    </table>

    @unless ($forStaff)
        <p style="margin-top: 24px;">
            {{ $isEnglish ? 'Questions? Contact us on' : 'Vragen? Stuur ons een bericht via' }}
            <a href="https://wa.me/31686463268">WhatsApp</a>
            {{ $isEnglish ? 'or email' : 'of mail naar' }} <a href="mailto:info@husso.nl">info@husso.nl</a>.
        </p>
    @endunless
</body>
</html>
