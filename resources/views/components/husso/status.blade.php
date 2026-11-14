@props(['code', 'kind' => 'ride', 'isEnglish' => false, 'livePayment' => false])

@php
    $statuses = $kind === 'payment' ? [
        'paid' => ['Betaald', 'Paid', 'good'],
        'pending' => ['Betaling open', 'Payment pending', 'attention'],
        'pay_later' => ['Later betalen', 'Pay later', 'attention'],
        'failed' => ['Betaling mislukt', 'Payment failed', 'error'],
        'checkout_failed' => ['Betaling mislukt', 'Payment failed', 'error'],
        'checkout_missing' => ['Betaling mislukt', 'Payment failed', 'error'],
        'canceled' => ['Betaling afgebroken', 'Payment canceled', 'neutral'],
        'expired' => ['Betaling verlopen', 'Payment expired', 'neutral'],
        'refunded' => ['Terugbetaald', 'Refunded', 'neutral'],
    ] : [
        'new' => ['Nieuw', 'New', 'attention'],
        'driver_assigned' => ['Toegewezen', 'Driver assigned', 'neutral'],
        'driver_en_route' => ['Onderweg naar klant', 'Driver on the way', 'moving'],
        'arrived' => ['Aangekomen', 'Driver arrived', 'moving'],
        'picked_up' => ['Rit bezig', 'In progress', 'moving'],
        'completed' => ['Afgerond', 'Completed', 'good'],
        'failed' => ['Mislukt', 'Failed', 'error'],
        'canceled' => ['Geannuleerd', 'Canceled', 'neutral'],
        'rejected' => ['Geweigerd', 'Declined', 'neutral'],
    ];
    [$nl, $en, $tone] = $statuses[$code] ?? ['Status onbekend', 'Unknown status', 'neutral'];
@endphp

<span
    {{ $attributes->class(['dispatch-status']) }}
    data-kind="{{ $kind }}"
    data-tone="{{ $tone }}"
    @if ($livePayment)
        x-data="{ statuses: @js($statuses), initial: @js([$nl, $en, $tone]) }"
        x-text="(statuses[state?.payment_status] ?? initial)[{{ $isEnglish ? 1 : 0 }}]"
        :data-tone="(statuses[state?.payment_status] ?? initial)[2]"
        aria-live="polite"
    @endif
>{{ $isEnglish ? $en : $nl }}</span>
