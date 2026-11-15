export default function driverAvailability(url) {
    return {
        online: false,
        checking: false,
        timer: null,

        init() {
            this.refresh();
            this.timer = setInterval(() => this.refresh(), 20000);
        },

        destroy() {
            clearInterval(this.timer);
        },

        async refresh() {
            if (document.hidden || this.checking) return;
            this.checking = true;
            const request = new AbortController();
            const timeout = setTimeout(() => request.abort(), 8000);

            try {
                const response = await fetch(url, {
                    headers: { Accept: 'application/json' },
                    cache: 'no-store',
                    signal: request.signal,
                });
                if (!response.ok) throw new Error('Driver status request failed');
                this.online = (await response.json()).driver_online === true;
            } catch {
                this.online = false;
            } finally {
                clearTimeout(timeout);
                this.checking = false;
            }
        },
    };
}
