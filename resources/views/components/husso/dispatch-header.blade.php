<header class="dispatch-header">
    <div class="husso-wrap dispatch-header-inner">
        <x-husso.brand :href="route('admin.dashboard')" />
        <nav class="dispatch-nav" aria-label="Ritbeheer">
            @foreach (['admin.dashboard' => 'Overzicht', 'admin.bookings.index' => 'Boekingen', 'admin.live' => 'Live', 'driver.dashboard' => 'Chauffeur'] as $route => $label)
                <a href="{{ route($route) }}" @if (request()->routeIs($route)) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </nav>
        <form class="dispatch-signout" method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">Afmelden</button>
        </form>
    </div>
</header>
