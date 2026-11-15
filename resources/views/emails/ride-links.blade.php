@php
    $trackingUrl = $booking->trackingUrl();
    $receiptUrl = route('bookings.receipt', $booking->tracking_token);
@endphp

<p style="margin: 22px 0 0;">
    <a href="{{ $trackingUrl }}" style="display:inline-block;margin:0 8px 8px 0;padding:12px 18px;background:#ffcc00;color:#111111;text-decoration:none;font-weight:bold;border-radius:6px;">
        {{ $isEnglish ? 'Track my ride' : 'Volg mijn rit' }}
    </a>
    <a href="{{ $receiptUrl }}" style="display:inline-block;margin:0 0 8px;padding:12px 18px;background:#111111;color:#ffffff;text-decoration:none;font-weight:bold;border-radius:6px;">
        {{ $isEnglish ? 'View trip receipt' : 'Open ritbon' }}
    </a>
</p>
<p style="margin:0 0 20px;color:#52525b;">
    {{ $isEnglish ? 'The tracking page updates automatically. Keep these personal links private.' : 'De volgpagina wordt automatisch bijgewerkt. Houd deze persoonlijke links voor jezelf.' }}
</p>
