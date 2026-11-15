export function booking(config) {
    const locale = config.language === 'en' ? 'en-NL' : 'nl-NL';
    const priceFormat = new Intl.NumberFormat(locale, { style: 'currency', currency: 'EUR' });
    const dateFormat = new Intl.DateTimeFormat(locale, { dateStyle: 'medium' });

    return {
        step: 1,
        addresses: { pickup: '', destination: '' },
        places: { pickup: null, destination: null },
        suggestions: { pickup: [], destination: [] },
        placeMessages: { pickup: '', destination: '' },
        placeErrors: { pickup: false, destination: false },
        selecting: { pickup: false, destination: false },
        searches: {},
        timers: {},
        activeField: null,
        activeIndex: -1,
        routeRequest: null,
        quote: null,
        routeMessage: '',
        calculating: false,
        locating: false,
        locationRequest: 0,
        submitting: false,
        sessionToken: '',
        date: '',
        time: '',
        passengers: '1',
        rideType: config.requestedRide || 'standard',
        rideTypes: config.rideTypes,
        serviceSelected: Boolean(config.requestedRide),
        customerName: '',
        customerEmail: '',
        phone: '',

        init() {
            const today = new Intl.DateTimeFormat('sv-SE', { timeZone: 'Europe/Amsterdam' });
            const draft = config.draft;
            this.date = typeof draft.ride_date === 'string' && draft.ride_date ? draft.ride_date : today.format(new Date());
            this.time = typeof draft.ride_time === 'string' ? draft.ride_time : '';
            this.customerName = typeof draft.customer_name === 'string' ? draft.customer_name : '';
            this.customerEmail = typeof draft.customer_email === 'string' ? draft.customer_email : '';
            this.phone = typeof draft.phone === 'string' ? draft.phone : '';
            this.passengers = ['1', '2', '3', '4', '5+'].includes(draft.passengers) ? draft.passengers : '1';
            if (Object.hasOwn(this.rideTypes, draft.ride_type)) {
                this.rideType = draft.ride_type;
                this.serviceSelected = true;
            }
            for (const field of Object.keys(this.addresses)) {
                if (typeof draft[field] !== 'string') continue;
                this.addresses[field] = draft[field];
                const latitude = draft[field + '_latitude'];
                const longitude = draft[field + '_longitude'];
                if (latitude && longitude && Number.isFinite(Number(latitude)) && Number.isFinite(Number(longitude))) {
                    this.places[field] = { latitude: Number(latitude), longitude: Number(longitude) };
                }
            }
            this.sessionToken = window.crypto?.randomUUID?.() ?? String(Date.now()) + '-' + Math.random();
        },

        async request(url, body, signal) {
            const response = await fetch(url, {
                method: 'POST',
                signal,
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify(body),
            });
            if (!response.ok) {
                throw new Error('Booking request failed');
            }
            return response.json();
        },

        focusAddress(field) {
            this.activeField = field;
            this.activeIndex = -1;
        },

        blurAddress(field) {
            if (this.activeField === field) {
                this.activeField = null;
                this.activeIndex = -1;
            }
        },

        hasSuggestions(field) {
            return this.activeField === field && this.suggestions[field].length > 0;
        },

        activeOption(field) {
            return this.hasSuggestions(field) && this.activeIndex >= 0
                ? field + '-option-' + this.activeIndex
                : null;
        },

        addressKeydown(field, event) {
            if (event.isComposing || event.altKey || event.ctrlKey || event.metaKey || event.shiftKey) return;

            if (event.key === 'Escape' && this.hasSuggestions(field)) {
                event.preventDefault();
                this.blurAddress(field);
                return;
            }

            const options = this.suggestions[field];
            if (options.length && (event.key === 'ArrowDown' || event.key === 'ArrowUp')) {
                event.preventDefault();
                this.activeField = field;
                if (event.key === 'ArrowDown') {
                    this.activeIndex = Math.min(this.activeIndex + 1, options.length - 1);
                } else {
                    this.activeIndex = this.activeIndex < 0 ? options.length - 1 : Math.max(this.activeIndex - 1, 0);
                }
                this.$nextTick(() => document.getElementById(this.activeOption(field))?.scrollIntoView({ block: 'nearest' }));
            }

            if (event.key === 'Enter' && this.hasSuggestions(field)) {
                event.preventDefault();
                event.stopPropagation();
                if (this.activeIndex >= 0) this.selectPlace(field, options[this.activeIndex]);
            }
        },

        searchPlaces(field, value) {
            clearTimeout(this.timers[field]);
            this.searches[field]?.abort();
            this.addresses[field] = value;
            this.places[field] = null;
            this.suggestions[field] = [];
            this.selecting[field] = false;
            this.placeMessages[field] = '';
            this.placeErrors[field] = false;
            this.focusAddress(field);
            this.resetRoute();
            if (field === 'pickup') {
                this.locating = false;
                this.locationRequest++;
            }

            const input = value.trim();
            if (input.length < 2) return;

            this.placeMessages[field] = config.messages.searching;
            this.timers[field] = setTimeout(async () => {
                const request = new AbortController();
                this.searches[field] = request;

                try {
                    const data = await this.request(this.$root.dataset.autocomplete, {
                        input,
                        language: config.language,
                        session_token: this.sessionToken,
                    }, request.signal);
                    if (request.signal.aborted) return;

                    const seen = new Set();
                    this.suggestions[field] = (data.suggestions ?? []).filter((suggestion) => {
                        const label = suggestion.text.trim().replace(/\s+/g, ' ').toLocaleLowerCase(locale);
                        if (seen.has(label)) return false;
                        seen.add(label);
                        return true;
                    });
                    const count = this.suggestions[field].length;
                    this.placeMessages[field] = count
                        ? config.messages.results.replace('{count}', count)
                        : config.messages.noResults;
                } catch {
                    if (!request.signal.aborted) {
                        this.placeMessages[field] = config.messages.searchError;
                        this.placeErrors[field] = true;
                    }
                }
            }, 220);
        },

        async selectPlace(field, suggestion) {
            clearTimeout(this.timers[field]);
            this.searches[field]?.abort();
            const request = new AbortController();
            this.searches[field] = request;
            this.selecting[field] = true;
            this.suggestions[field] = [];
            this.placeErrors[field] = false;
            this.activeIndex = -1;
            this.placeMessages[field] = config.messages.selecting;
            this.resetRoute();

            try {
                const data = await this.request(this.$root.dataset.placeDetails, {
                    place_id: suggestion.place_id,
                    language: config.language,
                }, request.signal);
                if (request.signal.aborted) return;
                if (!Number.isFinite(data.location?.latitude) || !Number.isFinite(data.location?.longitude)) {
                    throw new Error('Missing place coordinates');
                }
                this.addresses[field] = suggestion.text;
                this.places[field] = { id: suggestion.place_id, ...data.location };
                this.placeMessages[field] = '';
            } catch {
                if (!request.signal.aborted) {
                    this.placeMessages[field] = config.messages.placeError;
                    this.placeErrors[field] = true;
                }
            } finally {
                if (!request.signal.aborted) this.selecting[field] = false;
            }
        },

        useCurrentLocation() {
            if (!navigator.geolocation) {
                this.placeMessages.pickup = config.messages.locationError;
                return;
            }

            clearTimeout(this.timers.pickup);
            this.searches.pickup?.abort();
            this.selecting.pickup = false;
            this.suggestions.pickup = [];
            this.placeErrors.pickup = false;
            this.locating = true;
            this.placeMessages.pickup = config.messages.locationPending;
            this.resetRoute();
            const request = ++this.locationRequest;

            navigator.geolocation.getCurrentPosition(({ coords }) => {
                if (this.locationRequest !== request) return;
                this.locating = false;
                this.addresses.pickup = config.messages.currentLocation + ' (GPS: '
                    + coords.latitude.toFixed(6) + ', ' + coords.longitude.toFixed(6) + ')';
                this.places.pickup = { latitude: coords.latitude, longitude: coords.longitude };
                this.placeMessages.pickup = '';
                this.resetRoute();
            }, () => {
                if (this.locationRequest !== request) return;
                this.locating = false;
                this.placeMessages.pickup = config.messages.locationError;
            }, { enableHighAccuracy: true, timeout: 10000 });
        },

        resetRoute() {
            this.routeRequest?.abort();
            this.quote = null;
            this.calculating = false;
            this.routeMessage = '';
        },

        async calculateRoute() {
            if (this.calculating || this.locating || this.selecting.pickup || this.selecting.destination) return;
            if (!this.places.pickup || !this.places.destination) {
                this.placeErrors.pickup = !this.places.pickup;
                this.placeErrors.destination = !this.places.destination;
                this.routeMessage = config.messages.chooseAddresses;
                return;
            }
            if (this.quote) {
                this.goToStep(2);
                return;
            }

            const request = new AbortController();
            this.routeRequest = request;
            this.calculating = true;
            this.routeMessage = '';

            try {
                const origin = this.places.pickup;
                const destination = this.places.destination;
                const quote = await this.request(this.$root.dataset.estimate, {
                    origin: { latitude: origin.latitude, longitude: origin.longitude },
                    destination: { latitude: destination.latitude, longitude: destination.longitude },
                }, request.signal);
                if (request.signal.aborted) return;
                if (!Number.isFinite(quote.distance_km) || !Number.isFinite(quote.duration_min) || !Number.isFinite(quote.quoted_amount)) {
                    throw new Error('Missing route estimate');
                }
                this.quote = quote;
                this.goToStep(2);
            } catch {
                if (!request.signal.aborted) this.routeMessage = config.messages.routeError;
            } finally {
                if (!request.signal.aborted) this.calculating = false;
            }
        },

        estimateTotal() {
            return this.quote?.quoted_amount ?? 0;
        },

        money(value) {
            return priceFormat.format(value);
        },

        formattedDeparture() {
            return this.date ? dateFormat.format(new Date(this.date + 'T12:00:00')) + ' · ' + this.time : '';
        },

        detailsReady() {
            return Boolean(this.customerName.trim() && this.customerEmail.trim() && this.phone.trim() && this.date && this.time);
        },

        goToStep(step) {
            if (this.step === step || (step > 1 && !this.quote)) return;
            this.step = step;
            this.$nextTick(() => {
                const heading = [this.$refs.routeTitle, this.$refs.detailsTitle, this.$refs.reviewTitle][step - 1];
                heading.focus({ preventScroll: true });
                document.getElementById('booking').scrollIntoView({ block: 'start' });
            });
        },

        reviewBooking() {
            if (!this.quote || this.step === 3) return;
            if (this.step === 1) {
                this.goToStep(2);
                this.$nextTick(() => this.reviewBooking());
                return;
            }
            const invalid = [...this.$refs.detailsFields.querySelectorAll('input')].find((input) => !input.validity.valid);
            if (invalid) {
                invalid.reportValidity();
                return;
            }
            if (!this.detailsReady()) return;
            this.goToStep(3);
        },

        chooseRideType(event, type) {
            if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
            event.preventDefault();
            this.rideType = type;
            this.serviceSelected = true;
            this.goToStep(1);
            this.$nextTick(() => {
                this.$refs.bookingHeading.focus({ preventScroll: true });
                window.location.hash = 'booking';
                document.getElementById('booking').scrollIntoView({ block: 'start' });
            });
        },

        formEnter(event) {
            if (event.isComposing || event.defaultPrevented || !event.target.matches('input[type="text"], input[type="email"], input[type="tel"]')) return;
            event.preventDefault();
            if (this.step === 1) this.calculateRoute();
            if (this.step === 2) this.reviewBooking();
        },

        submitBooking(event) {
            if (this.step !== 3 || !event.submitter || this.submitting) {
                event.preventDefault();
                return;
            }
            this.submitting = true;
        },
    };
}

export function cookieNotice() {
    return {
        visible: !document.cookie.split('; ').includes('husso_cookie_consent=yes'),
        dismiss() {
            const secure = location.protocol === 'https:' ? '; Secure' : '';
            document.cookie = 'husso_cookie_consent=yes; Max-Age=31536000; SameSite=Lax; Path=/' + secure;
            this.visible = false;
        },
    };
}
