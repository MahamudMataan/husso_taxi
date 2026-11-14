@props(['isEnglish', 'homeUrl', 'languageUrl', 'onContact' => false])

<header class="site-header">
    <div class="husso-wrap site-header-inner">
        <x-husso.brand :href="$homeUrl" />
        <nav class="site-nav" aria-label="{{ $isEnglish ? 'Main navigation' : 'Hoofdnavigatie' }}">
            <a href="{{ $onContact ? $homeUrl : '' }}#services">{{ $isEnglish ? 'Services' : 'Diensten' }}</a>
            <a href="{{ $onContact ? $homeUrl : '' }}#booking">{{ $isEnglish ? 'Book a ride' : 'Rit boeken' }}</a>
            <a href="{{ route('contact', $isEnglish ? ['lang' => 'en'] : []) }}" @if ($onContact) aria-current="page" @endif>Contact</a>
        </nav>
        <div class="site-header-actions">
            <a class="site-language" href="{{ $languageUrl }}" lang="{{ $isEnglish ? 'nl' : 'en' }}" hreflang="{{ $isEnglish ? 'nl' : 'en' }}" aria-label="{{ $isEnglish ? 'Nederlands' : 'English' }}">{{ $isEnglish ? 'NL' : 'EN' }}</a>
            <a class="husso-button husso-button--inverse site-header-call" href="tel:+31686463268">{{ $isEnglish ? 'Call us' : 'Direct bellen' }}</a>
        </div>
    </div>
</header>
