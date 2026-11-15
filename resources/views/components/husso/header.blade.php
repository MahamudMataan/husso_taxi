@props(['isEnglish', 'homeUrl', 'languageUrl', 'onContact' => false])

<header class="site-header">
    <div class="husso-wrap site-header-inner">
        <x-husso.brand :href="$homeUrl" />
        <nav class="site-nav" aria-label="{{ $isEnglish ? 'Main navigation' : 'Hoofdnavigatie' }}">
            <a href="{{ $homeUrl }}#services">{{ $isEnglish ? 'Services' : 'Diensten' }}</a>
            <a href="{{ $homeUrl }}#about">{{ $isEnglish ? 'About' : 'Over ons' }}</a>
            <a href="{{ $homeUrl }}#faq">FAQ</a>
            <a href="{{ route('contact', $isEnglish ? ['lang' => 'en'] : []) }}" @if ($onContact) aria-current="page" @endif>Contact</a>
        </nav>
        <div class="site-header-actions">
            <a class="site-language" href="{{ $languageUrl }}" lang="{{ $isEnglish ? 'nl' : 'en' }}" hreflang="{{ $isEnglish ? 'nl' : 'en' }}" aria-label="{{ $isEnglish ? 'Nederlands' : 'English' }}">{{ $isEnglish ? 'NL' : 'EN' }}</a>
            <a class="husso-button husso-button--primary" href="{{ $homeUrl }}#booking">{{ $isEnglish ? 'Book a ride' : 'Boek een rit' }} <span class="husso-arrow" aria-hidden="true"></span></a>
            <details class="taxi-mobile-menu" x-data @keydown.escape.prevent="$el.open = false; $refs.menuButton.focus()">
                <summary x-ref="menuButton" aria-label="{{ $isEnglish ? 'Open navigation' : 'Open navigatie' }}">
                    <svg viewBox="0 0 24 24" width="23" height="23" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                </summary>
                <nav aria-label="{{ $isEnglish ? 'Mobile navigation' : 'Mobiele navigatie' }}" @click="if ($event.target.closest('a')) $el.closest('details').open = false">
                    <a href="{{ $homeUrl }}#services">{{ $isEnglish ? 'Services' : 'Diensten' }}</a>
                    <a href="{{ $homeUrl }}#about">{{ $isEnglish ? 'About' : 'Over ons' }}</a>
                    <a href="{{ $homeUrl }}#faq">FAQ</a>
                    <a href="{{ route('contact', $isEnglish ? ['lang' => 'en'] : []) }}" @if ($onContact) aria-current="page" @endif>Contact</a>
                </nav>
            </details>
        </div>
    </div>
</header>
