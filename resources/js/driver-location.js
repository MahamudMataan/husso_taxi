export default function driverLocation(url, id) {
    const preference = 'husso-location:' + id;
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const savedPreference = () => {
        try { return sessionStorage.getItem(preference) === 'on'; } catch { return false; }
    };
    const savePreference = enabled => {
        try {
            if (enabled) sessionStorage.setItem(preference, 'on');
            else sessionStorage.removeItem(preference);
        } catch {
            return;
        }
    };
    const request = (method, body) => fetch(url, {
        method,
        headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
        ...(body ? { body: JSON.stringify(body) } : {}),
    });

    return {
        sharing: false,
        stopping: false,
        message: '',
        timer: null,
        pending: null,

        init() {
            if (savedPreference()) this.start();
        },

        destroy() {
            this.sharing = false;
            clearInterval(this.timer);
        },

        start() {
            if (this.sharing || this.stopping) return;
            if (!navigator.geolocation) {
                this.message = 'Locatiedeling is niet beschikbaar in deze browser.';
                return;
            }
            this.sharing = true;
            this.message = 'Locatie ophalen…';
            this.refresh();
            this.timer = setInterval(() => this.refresh(), 15000);
        },

        refresh() {
            if (!this.sharing || document.hidden || this.pending) return;
            this.pending = this.postPosition().finally(() => this.pending = null);
        },

        async postPosition() {
            try {
                const position = await new Promise((resolve, reject) => navigator.geolocation.getCurrentPosition(
                    resolve, reject, { enableHighAccuracy: true, timeout: 10000, maximumAge: 8000 },
                ));
                if (!this.sharing) return;
                const response = await request('PUT', {
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude,
                });
                if (!this.sharing) return;
                if ([401, 403, 409, 419].includes(response.status)) {
                    this.destroy();
                    savePreference(false);
                    this.message = 'Locatie delen is gestopt. Open de actieve rit opnieuw.';
                    return;
                }
                if (!response.ok) throw new Error('Location update failed');
                savePreference(true);
                this.message = 'Je locatie wordt gedeeld met de klant van deze rit.';
            } catch (error) {
                if (!this.sharing) return;
                if (error?.code === 1) {
                    this.destroy();
                    savePreference(false);
                    this.message = 'Geef de browser toegang tot je locatie om die te delen.';
                } else {
                    this.message = 'Je locatie is tijdelijk niet beschikbaar. We proberen het opnieuw.';
                }
            }
        },

        async stop() {
            if (!this.sharing || this.stopping) return;
            this.stopping = true;
            this.destroy();
            savePreference(false);
            try {
                await this.pending;
                const response = await request('DELETE');
                if (!response.ok) throw new Error('Location stop failed');
                this.message = 'Locatie delen gestopt.';
            } catch {
                this.message = 'Delen gestopt op dit toestel. De laatst gedeelde locatie verdwijnt automatisch.';
            } finally {
                this.stopping = false;
            }
        },
    };
}
