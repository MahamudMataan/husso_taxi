<section id="booking" class="booking-panel" aria-labelledby="booking-title" tabindex="-1">
    <div class="booking-panel-heading">
        <h2 id="booking-title" x-ref="bookingHeading" tabindex="-1">{{ $isEnglish ? 'Book a ride' : 'Rit boeken' }}</h2>
        <span class="booking-driver-status" x-data="driverAvailability(@js(route('bookings.availability')))"
            @visibilitychange.document="refresh()" @pageshow.window="refresh()" role="status">
            <span x-show="online" x-cloak>{{ $isEnglish ? 'Driver is online' : 'Chauffeur is online' }}</span>
        </span>
    </div>

    @if (session('payment_error') || $errors->any())
        <div class="husso-notice husso-notice--error" role="alert">
            @if (session('payment_error'))
                <p>{{ session('payment_error') }}</p>
            @endif
            @if ($errors->any())
                <p>{{ $isEnglish ? 'Check your booking details and try again.' : 'Controleer je boekingsgegevens en probeer opnieuw.' }}</p>
            @endif
        </div>
    @endif

    @if (session('payment_success') || session('payment_notice'))
        <div class="husso-notice" role="status">
            <p>{{ session('payment_success') ?? session('payment_notice') }}</p>
            @if (session('booking_tracking_url'))
                <a href="{{ session('booking_tracking_url') }}">{{ $isEnglish ? 'Track your ride' : 'Volg je rit' }}</a>
            @endif
        </div>
    @endif

    <noscript>
        <p class="husso-notice">{{ $isEnglish ? 'Enable JavaScript to book online, or call' : 'Zet JavaScript aan om online te boeken, of bel' }} <a href="tel:+31686463268">+31 6 8646 3268</a>.</p>
    </noscript>

    <form method="POST" action="{{ route('payments.mollie.store') }}" x-ref="form" @keydown.enter="formEnter($event)" @submit="submitBooking($event)" x-cloak>
        @csrf
        <input type="hidden" name="language" value="{{ $isEnglish ? 'en' : 'nl' }}">
        <input type="hidden" name="amount" :value="estimateTotal().toFixed(2)">
        <input type="hidden" name="pickup" :value="addresses.pickup">
        <input type="hidden" name="pickup_latitude" :value="places.pickup?.latitude ?? ''">
        <input type="hidden" name="pickup_longitude" :value="places.pickup?.longitude ?? ''">
        <input type="hidden" name="destination" :value="addresses.destination">
        <input type="hidden" name="destination_latitude" :value="places.destination?.latitude ?? ''">
        <input type="hidden" name="destination_longitude" :value="places.destination?.longitude ?? ''">
        <input type="hidden" name="distance_km" :value="quote?.distance_km ?? ''">
        <input type="hidden" name="duration_min" :value="quote?.duration_min ?? ''">

        <ol class="booking-steps" aria-label="{{ $isEnglish ? 'Booking steps' : 'Boekingsstappen' }}">
            <li><button type="button" @click="goToStep(1)" :aria-current="step === 1 ? 'step' : null"><span aria-hidden="true">1</span> Route</button></li>
            <li><button type="button" @click="goToStep(2)" :disabled="!quote" :aria-current="step === 2 ? 'step' : null"><span aria-hidden="true">2</span> {{ $isEnglish ? 'Details' : 'Ritgegevens' }}</button></li>
            <li><button type="button" @click="reviewBooking()" :disabled="!quote || !detailsReady()" :aria-current="step === 3 ? 'step' : null"><span aria-hidden="true">3</span> {{ $isEnglish ? 'Confirm' : 'Bevestigen' }}</button></li>
        </ol>

        <div class="booking-step" x-show="step === 1">
            <h3 class="sr-only" tabindex="-1" x-ref="routeTitle">{{ $isEnglish ? 'Choose your route' : 'Kies je route' }}</h3>
            <div class="booking-addresses">
                <x-husso.address-field field="pickup" :label="$isEnglish ? 'Pickup location' : 'Ophaallocatie'" :placeholder="$isEnglish ? 'Street, hotel or airport terminal' : 'Straat, hotel of terminal'">
                    <button type="button" class="booking-location husso-text-button" @click="useCurrentLocation()" :disabled="locating || calculating">
                        {{ $isEnglish ? 'Use my current location' : 'Gebruik mijn huidige locatie' }}
                    </button>
                </x-husso.address-field>
                <x-husso.address-field field="destination" :label="$isEnglish ? 'Destination' : 'Bestemming'" :placeholder="$isEnglish ? 'Street, station or airport' : 'Straat, station of luchthaven'" />
            </div>

            <p class="booking-service-note" x-show="serviceSelected">
                {{ $isEnglish ? 'Ride type:' : 'Type rit:' }} <strong x-text="rideTypes[rideType]"></strong>
            </p>

            <div class="booking-action">
                <button type="button" class="husso-button husso-button--primary" @click="calculateRoute()" :disabled="calculating || locating || selecting.pickup || selecting.destination">
                    <span x-show="!calculating">{{ $isEnglish ? 'Continue with this route' : 'Verder met deze route' }}</span>
                    <span x-show="calculating">{{ $isEnglish ? 'Calculating route…' : 'Route berekenen…' }}</span>
                    <span class="husso-arrow" aria-hidden="true"></span>
                </button>
                <p class="booking-field-note" x-show="!routeMessage">{{ $isEnglish ? 'Select an address from the suggestions for each field.' : 'Kies bij elk veld een adres uit de suggesties.' }}</p>
                <p class="booking-field-note" role="status" x-text="routeMessage"></p>
            </div>
        </div>

        <div class="booking-step" x-show="step === 2">
            <h3 class="booking-subtitle" tabindex="-1" x-ref="detailsTitle">{{ $isEnglish ? 'When are you travelling?' : 'Wanneer wil je reizen?' }}</h3>
            <div class="booking-fields" x-ref="detailsFields">
                <label class="booking-field">
                    {{ $isEnglish ? 'Date' : 'Datum' }}
                    <input class="husso-input" type="date" name="ride_date" x-model="date" required>
                </label>
                <label class="booking-field">
                    {{ $isEnglish ? 'Time' : 'Tijd' }}
                    <input class="husso-input" type="time" name="ride_time" x-model="time" required>
                </label>
                <div class="booking-field">
                    <label for="passengers">{{ $isEnglish ? 'Passengers' : 'Passagiers' }}</label>
                    <select class="husso-input" id="passengers" name="passengers" x-model="passengers">
                        @foreach (['1', '2', '3', '4', '5+'] as $count)
                            <option value="{{ $count }}">{{ $count }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="booking-field">
                    <label for="ride_type">{{ $isEnglish ? 'Ride type' : 'Type rit' }}</label>
                    <select class="husso-input" id="ride_type" name="ride_type" x-model="rideType">
                        @foreach ($rideTypes as $type => $label)
                            <option value="{{ $type }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <label class="booking-field booking-field--wide">
                    {{ $isEnglish ? 'Full name' : 'Volledige naam' }}
                    <input class="husso-input" type="text" name="customer_name" x-model="customerName" autocomplete="name" maxlength="120" required>
                </label>
                <label class="booking-field booking-field--wide">
                    {{ $isEnglish ? 'Email for confirmation' : 'E-mail voor bevestiging' }}
                    <input class="husso-input" type="email" name="customer_email" x-model="customerEmail" autocomplete="email" maxlength="255" required>
                </label>
                <label class="booking-field booking-field--wide">
                    {{ $isEnglish ? 'Phone number' : 'Telefoonnummer' }}
                    <input class="husso-input" type="tel" name="phone" x-model="phone" autocomplete="tel" maxlength="40" required>
                </label>
            </div>
        </div>

        <div class="booking-step" x-show="step === 3">
            <h3 class="booking-subtitle" tabindex="-1" x-ref="reviewTitle">{{ $isEnglish ? 'Booking summary' : 'Boekingsoverzicht' }}</h3>
            <div class="booking-review">
                <div class="booking-review-group">
                    <div class="booking-review-heading">
                        <h4>Route</h4>
                        <button type="button" class="husso-text-button" @click="goToStep(1)">{{ $isEnglish ? 'Edit route' : 'Wijzig route' }}</button>
                    </div>
                    <dl>
                        <div><dt>{{ $isEnglish ? 'Pickup' : 'Ophaallocatie' }}</dt><dd x-text="addresses.pickup"></dd></div>
                        <div><dt>{{ $isEnglish ? 'Destination' : 'Bestemming' }}</dt><dd x-text="addresses.destination"></dd></div>
                    </dl>
                </div>
                <div class="booking-review-group">
                    <div class="booking-review-heading">
                        <h4>{{ $isEnglish ? 'Ride details' : 'Ritgegevens' }}</h4>
                        <button type="button" class="husso-text-button" @click="goToStep(2)">{{ $isEnglish ? 'Edit details' : 'Wijzig gegevens' }}</button>
                    </div>
                    <dl>
                        <div><dt>{{ $isEnglish ? 'Date and time' : 'Datum en tijd' }}</dt><dd x-text="formattedDeparture()"></dd></div>
                        <div><dt>{{ $isEnglish ? 'Passengers' : 'Passagiers' }}</dt><dd x-text="passengers"></dd></div>
                        <div><dt>{{ $isEnglish ? 'Ride type' : 'Type rit' }}</dt><dd x-text="rideTypes[rideType]"></dd></div>
                        <div><dt>Contact</dt><dd><span x-text="customerName"></span><br><span x-text="customerEmail"></span><br><span x-text="phone"></span></dd></div>
                    </dl>
                </div>
            </div>
        </div>

        <div class="booking-total" x-show="step === 3 && quote" role="status">
            <p>{{ $isEnglish ? 'Total fare' : 'Totaalbedrag' }}</p>
            <strong x-text="money(estimateTotal())"></strong>
        </div>

        <div class="booking-action" x-show="step === 2">
            <button type="button" class="husso-button husso-button--primary" @click="reviewBooking()">
                {{ $isEnglish ? 'Review booking' : 'Boeking controleren' }}
                <span class="husso-arrow" aria-hidden="true"></span>
            </button>
        </div>

        <div class="booking-action" x-show="step === 3">
            <p class="booking-field-note">{{ $isEnglish
                ? 'After booking, you will receive a reference for your request. You can check your ride status using the tracking link.'
                : 'Na het boeken ontvang je een ritnummer voor je aanvraag. Met de trackinglink kun je de status van je rit volgen.' }}</p>
            <div class="booking-payment">
                <button type="submit" class="husso-button husso-button--primary" :disabled="submitting">
                    {{ $isEnglish ? 'Pay now with iDEAL' : 'Betaal nu met iDEAL' }}
                </button>
                <button type="submit" formaction="{{ route('bookings.pay-later.store') }}" class="husso-button husso-button--plain" :disabled="submitting">
                    {{ $isEnglish ? 'Book, pay later' : 'Boek, betaal later' }}
                </button>
            </div>
        </div>
    </form>

</section>
