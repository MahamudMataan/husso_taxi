<section id="booking" class="booking-panel" aria-labelledby="booking-title" tabindex="-1">
    <h2 id="booking-title" x-ref="bookingHeading" tabindex="-1">{{ $isEnglish ? 'Book a ride' : 'Rit boeken' }}</h2>

    @if (session('payment_error') || $errors->any())
        <div class="husso-notice husso-notice--error" role="alert">
            @if (session('payment_error'))
                <p>{{ session('payment_error') }}</p>
            @endif
            @if ($errors->any())
                <p>{{ $errors->first() }}</p>
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

    <form method="POST" action="{{ route('payments.mollie.store') }}" :action="paymentMethod === 'ideal' ? $el.dataset.online : $el.dataset.inTaxi"
        data-online="{{ route('payments.mollie.store') }}" data-in-taxi="{{ route('bookings.pay-later.store') }}"
        x-ref="form" @keydown.enter="formEnter($event)" @submit="submitBooking($event)" x-cloak>
        @csrf
        <input type="hidden" name="language" value="{{ $isEnglish ? 'en' : 'nl' }}">
        <input type="hidden" name="amount" :value="estimateTotal().toFixed(2)">
        <input type="hidden" name="request_token" :value="requestToken">
        <input type="hidden" name="in_taxi_method" :value="paymentMethod === 'ideal' ? '' : paymentMethod">
        <input type="hidden" name="pickup" :value="addresses.pickup">
        <input type="hidden" name="pickup_latitude" :value="places.pickup?.latitude ?? ''">
        <input type="hidden" name="pickup_longitude" :value="places.pickup?.longitude ?? ''">
        <input type="hidden" name="pickup_place_id" :value="places.pickup?.id ?? ''">
        <input type="hidden" name="destination" :value="addresses.destination">
        <input type="hidden" name="destination_latitude" :value="places.destination?.latitude ?? ''">
        <input type="hidden" name="destination_longitude" :value="places.destination?.longitude ?? ''">
        <input type="hidden" name="destination_place_id" :value="places.destination?.id ?? ''">
        <input type="hidden" name="distance_km" :value="quote?.distance_km ?? ''">
        <input type="hidden" name="duration_min" :value="quote?.duration_min ?? ''">

        <ol class="booking-steps" aria-label="{{ $isEnglish ? 'Booking steps' : 'Boekingsstappen' }}">
            <li><button type="button" @click="goToStep(1)" :aria-current="step === 1 ? 'step' : null"><span aria-hidden="true">1</span> {{ $isEnglish ? 'Route' : 'Rit' }}</button></li>
            <li><button type="button" @click="goToStep(2)" :disabled="!quote" :aria-current="step === 2 ? 'step' : null"><span aria-hidden="true">2</span> {{ $isEnglish ? 'Passengers' : 'Passagiers' }}</button></li>
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
            <div class="booking-fields" x-ref="schedule">
                <label class="booking-field">
                    {{ $isEnglish ? 'Pickup date' : 'Ophaaldatum' }}
                    <input class="husso-input" type="date" name="ride_date" x-model="date" :min="minimumDate" @change="resetRoute()" required>
                </label>
                <label class="booking-field">
                    {{ $isEnglish ? 'Pickup time' : 'Ophaaltijd' }}
                    <input class="husso-input" type="time" name="ride_time" x-model="time" @change="resetRoute()" required>
                </label>
            </div>

            <p class="booking-service-note" x-show="serviceSelected">
                {{ $isEnglish ? 'Ride type:' : 'Type rit:' }} <strong x-text="rideTypes[rideType]"></strong>
            </p>

            <div class="booking-action">
                <button type="button" class="husso-button husso-button--primary" @click="calculateRoute()" :disabled="calculating || locating || selecting.pickup || selecting.destination">
                    <span x-show="!calculating">{{ $isEnglish ? 'Calculate route and price' : 'Bereken route en prijs' }}</span>
                    <span x-show="calculating">{{ $isEnglish ? 'Calculating route…' : 'Route berekenen…' }}</span>
                    <span class="husso-arrow" aria-hidden="true"></span>
                </button>
                <p class="booking-field-note" x-show="!routeMessage">{{ $isEnglish ? 'Select an address from the suggestions for each field.' : 'Kies bij elk veld een adres uit de suggesties.' }}</p>
                <p class="booking-field-note" role="status" x-text="routeMessage"></p>
            </div>
        </div>

        <div class="booking-step" x-show="step === 2">
            <h3 class="booking-subtitle" tabindex="-1" x-ref="detailsTitle">{{ $isEnglish ? 'Passengers and payment' : 'Passagiers en betaling' }}</h3>
            <div class="booking-fields" x-ref="detailsFields">
                <div class="booking-field">
                    <label for="passengers">{{ $isEnglish ? 'Passengers' : 'Passagiers' }}</label>
                    <select class="husso-input" id="passengers" name="passengers" x-model="passengers">
                        @foreach (range(1, 5) as $count)
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
                <p class="booking-field-note booking-field--wide">
                    {{ $isEnglish ? 'More than 5 passengers? Arrange an extra taxi via' : 'Met meer dan 5 personen? Regel een extra taxi via' }}
                    <x-husso.whatsapp :inline="true" />.
                </p>
                <fieldset class="booking-field booking-field--wide booking-payment-options">
                    <legend>{{ $isEnglish ? 'Payment method' : 'Betaalwijze' }}</legend>
                    @foreach ($paymentOptions as $value => $label)
                        <label><input type="radio" name="payment_choice" value="{{ $value }}" x-model="paymentMethod"> {{ $label }}</label>
                    @endforeach
                </fieldset>
                <label class="booking-field booking-field--wide">
                    {{ $isEnglish ? 'Notes or luggage (optional)' : 'Opmerkingen of bagage (optioneel)' }}
                    <textarea class="husso-input" name="customer_notes" rows="2" maxlength="500" x-model="notes"></textarea>
                </label>
            </div>
        </div>

        <div class="booking-step" x-show="step === 3">
            <h3 class="booking-subtitle" tabindex="-1" x-ref="reviewTitle">{{ $isEnglish ? 'Booking summary' : 'Boekingsoverzicht' }}</h3>
            <div class="booking-fields">
                <label class="booking-field booking-field--wide">
                    {{ $isEnglish ? 'Full name' : 'Volledige naam' }}
                    <input class="husso-input" type="text" name="customer_name" x-model="customerName" autocomplete="name" maxlength="120" :required="step === 3">
                </label>
                <label class="booking-field booking-field--wide">
                    {{ $isEnglish ? 'Email for confirmation' : 'E-mail voor bevestiging' }}
                    <input class="husso-input" type="email" name="customer_email" x-model="customerEmail" autocomplete="email" maxlength="255" :required="step === 3">
                </label>
                <label class="booking-field booking-field--wide">
                    {{ $isEnglish ? 'Phone number' : 'Telefoonnummer' }}
                    <input class="husso-input" type="tel" name="phone" x-model="phone" autocomplete="tel" maxlength="40" :required="step === 3">
                </label>
            </div>
            <div class="booking-fields booking-invoice">
                <input type="hidden" name="invoice_requested" :value="invoiceRequested ? '1' : '0'">
                <label class="booking-invoice-toggle booking-field--wide">
                    <input type="checkbox" x-model="invoiceRequested">
                    <span>{{ $isEnglish ? 'I would like an invoice' : 'Ik wil een factuur ontvangen' }}</span>
                </label>
                @foreach ([
                    'name' => [$isEnglish ? 'Billing name or company' : 'Factuurnaam of bedrijfsnaam', 120, true],
                    'address' => [$isEnglish ? 'Billing address' : 'Factuuradres', 180, true],
                    'postcode' => [$isEnglish ? 'Postcode' : 'Postcode', 24, true],
                    'city' => [$isEnglish ? 'City' : 'Plaats', 100, true],
                    'country' => [$isEnglish ? 'Country if outside the Netherlands' : 'Land indien buiten Nederland', 100, false],
                    'vat' => [$isEnglish ? 'VAT number (optional)' : 'Btw-nummer (optioneel)', 30, false],
                    'email' => [$isEnglish ? 'Different invoice email (optional)' : 'Ander e-mailadres voor de factuur (optioneel)', 255, false],
                ] as $field => [$label, $length, $required])
                    <label class="booking-field {{ in_array($field, ['name', 'address', 'email']) ? 'booking-field--wide' : '' }}" x-show="invoiceRequested">
                        {{ $label }}
                        <input class="husso-input" name="invoice_{{ $field }}" type="{{ $field === 'email' ? 'email' : 'text' }}" maxlength="{{ $length }}" value="{{ old('invoice_'.$field) }}" :disabled="!invoiceRequested" @if($required) :required="step === 3 && invoiceRequested" @endif>
                    </label>
                @endforeach
            </div>
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
                        <div><dt>{{ $isEnglish ? 'Payment' : 'Betaalwijze' }}</dt><dd x-text="({{ Js::from($paymentOptions) }})[paymentMethod]"></dd></div>
                    </dl>
                </div>
            </div>
        </div>

        <dl class="booking-costs" x-show="step > 1 && quote" aria-live="polite">
            <div><dt x-text="quote?.fare_type === 'schiphol_fixed' ? @js($isEnglish ? 'Fixed Schiphol fare' : 'Vaste Schipholprijs') : @js($isEnglish ? 'Ride fare' : 'Ritprijs')"></dt><dd x-text="money(quote?.ride_price ?? 0)"></dd></div>
            <div x-show="quote?.departure_supplement_cents > 0"><dt>{{ $isEnglish ? 'Minimum fare adjustment' : 'Uitrijkosten (aanvulling minimumritprijs)' }}</dt><dd x-text="money((quote?.departure_supplement_cents ?? 0) / 100)"></dd></div>
            <div class="booking-costs-total"><dt>{{ $isEnglish ? 'Total' : 'Totaal' }}</dt><dd x-text="money(estimateTotal())"></dd></div>
        </dl>

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
                <button type="submit" class="husso-button husso-button--primary" :disabled="submitting" x-text="paymentMethod === 'ideal' ? @js($isEnglish ? 'Continue to iDEAL' : 'Verder naar iDEAL') : @js($isEnglish ? 'Submit my reservation' : 'Verstuur mijn reservering')">
                </button>
            </div>
        </div>
    </form>

</section>
