<x-app-layout>
    <x-slot name="adminShell">true</x-slot>
    <x-husso.dispatch-header />

    <main class="husso-wrap dispatch-main">
        <h1 class="dispatch-title">Boekingen</h1>
        <section class="dispatch-panel" aria-label="Boekingsoverzicht">
            <div class="dispatch-panel-body border-b border-[var(--line)]">
                <form class="dispatch-filter" method="GET" action="{{ route('admin.bookings.index') }}">
                    <label for="search">
                        Zoek een boeking
                        <input class="husso-input" id="search" name="search" value="{{ $search }}" placeholder="Klant, ritnummer of adres">
                    </label>
                    @if ($filter !== '')
                        <input type="hidden" name="filter" value="{{ $filter }}">
                    @endif
                    <button class="husso-button husso-button--primary" type="submit">Zoeken</button>
                </form>
                <nav class="dispatch-filter-links" aria-label="Boekingen filteren">
                    @foreach (['' => 'Alle', 'today' => 'Vandaag', 'open' => 'Open', 'payment' => 'Betaling open'] as $value => $label)
                        <a class="husso-button husso-button--plain" href="{{ route('admin.bookings.index', array_filter(['search' => $search, 'filter' => $value], fn ($parameter) => $parameter !== '')) }}" @if ($filter === $value) aria-current="true" @endif>{{ $label }}</a>
                    @endforeach
                </nav>
            </div>
            <x-husso.booking-list :bookings="$bookings" :filters="$returnFilters" />
            @if ($bookings->hasPages())
                <div class="dispatch-panel-body border-t border-[var(--line)]">{{ $bookings->links() }}</div>
            @endif
        </section>
    </main>
</x-app-layout>
