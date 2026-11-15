<header class="dispatch-header">
    <div class="husso-wrap dispatch-header-inner">
        <x-husso.brand :href="route('driver.dashboard')" />
        <nav class="dispatch-nav" aria-label="Ritbeheer">
            @foreach (['driver.dashboard' => 'Vandaag', 'admin.bookings.index' => 'Boekingen', 'admin.live' => 'Live', 'admin.dashboard' => 'Overzicht'] as $route => $label)
                <a href="{{ route($route) }}" @if (request()->routeIs($route) || ($route === 'admin.bookings.index' && request()->routeIs('admin.bookings.show'))) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </nav>
        <form class="dispatch-signout" method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">Afmelden</button>
        </form>
    </div>
</header>
