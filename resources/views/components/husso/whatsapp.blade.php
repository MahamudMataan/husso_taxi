@props(['showNumber' => false, 'inline' => false, 'iconOnly' => false])

<a href="https://wa.me/31686463268" {{ $attributes->class(['inline-flex items-center gap-2', 'husso-whatsapp' => $inline]) }}>
    @if ($iconOnly)
        <span class="husso-quick-chat-mark" aria-hidden="true">
            <img src="{{ asset('images/whatsapp-button.webp') }}" alt="" width="740" height="740">
        </span>
        <span class="sr-only">WhatsApp</span>
    @else
        <svg class="shrink-0" aria-hidden="true" focusable="false" viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
            <path d="M12 2.75a9.25 9.25 0 0 0-8.03 13.84l-1.22 4.66 4.77-1.19A9.25 9.25 0 1 0 12 2.75Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
            <path d="M8.7 7.1h.85c.2 0 .38.12.46.31l.98 2.35a.5.5 0 0 1-.1.54l-.84.91a.45.45 0 0 0-.05.53c.73 1.22 1.75 2.19 3.07 2.83a.45.45 0 0 0 .53-.12l.88-1.07a.5.5 0 0 1 .59-.14l2.3 1.1a.5.5 0 0 1 .28.45c0 1.2-1.07 2.16-2.3 2.16-3.14 0-8.25-4.05-8.25-7.52 0-1.03.72-2.33 1.6-2.33Z"/>
        </svg>
        @if ($showNumber)
            <span><span class="sr-only">WhatsApp </span>+31 6 8646 3268</span>
        @else
            <span>WhatsApp</span>
        @endif
    @endif
</a>
