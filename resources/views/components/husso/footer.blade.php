@props(['isEnglish', 'homeUrl'])

<footer class="site-footer">
    <div class="husso-wrap site-footer-inner">
        <x-husso.brand :href="$homeUrl" />
        <nav aria-label="{{ $isEnglish ? 'Contact and staff links' : 'Contact en medewerkers' }}">
            <a href="{{ route('contact', $isEnglish ? ['lang' => 'en'] : []) }}">Contact</a>
            <a href="tel:+31686463268">+31 6 8646 3268</a>
            @auth
                <a href="{{ route('dashboard') }}">{{ $isEnglish ? 'Dispatch' : 'Ritbeheer' }}</a>
            @else
                <a href="{{ route('login') }}">{{ $isEnglish ? 'Staff login' : 'Medewerkerslogin' }}</a>
            @endauth
        </nav>
    </div>
</footer>
