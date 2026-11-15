export default function rideUpdates(url, rideId = null, locale = 'nl-NL') {
    const formatTime = new Intl.DateTimeFormat(locale, { timeStyle: 'short' });

    return {
        state: null,
        updatedAt: '',
        offline: false,
        copied: false,
        copyFailed: false,
        poll: null,
        copyTimer: null,
        fetching: false,
        mapVisible: false,

        init() {
            this.fetchState();
            this.poll = setInterval(() => this.fetchState(), 5000);
        },

        destroy() {
            clearInterval(this.poll);
            clearTimeout(this.copyTimer);
        },

        get sameRide() {
            return Boolean(this.state?.active_booking && this.state.active_booking.id === rideId);
        },

        get changedRide() {
            return this.state && (this.state.active_booking?.id ?? null) !== rideId;
        },

        driverMapUrl(location) {
            if (!location) return null;
            const { latitude, longitude } = location;
            return 'https://www.openstreetmap.org/?mlat=' + latitude + '&mlon=' + longitude + '#map=16/' + latitude + '/' + longitude;
        },

        driverUpdateTime(location) {
            return location ? formatTime.format(new Date(location.updated_at)) : '';
        },

        driverMapEmbedUrl(location) {
            const { latitude, longitude } = location;
            const params = new URLSearchParams({
                bbox: [longitude - 0.012, latitude - 0.008, longitude + 0.012, latitude + 0.008].join(','),
                layer: 'mapnik',
                marker: latitude + ',' + longitude,
            });

            return 'https://www.openstreetmap.org/export/embed.html?' + params.toString();
        },

        async fetchState() {
            if (this.fetching || document.hidden) return;
            this.fetching = true;
            try {
                const response = await fetch(url, { headers: { Accept: 'application/json' }, cache: 'no-store' });
                if (!response.ok) throw new Error('Ride status request failed');
                this.state = await response.json();
                this.updatedAt = formatTime.format(new Date());
                this.offline = false;
            } catch {
                this.offline = true;
            } finally {
                this.fetching = false;
            }
        },

        async copyLink() {
            clearTimeout(this.copyTimer);
            try {
                await navigator.clipboard.writeText(location.href);
                this.copied = true;
                this.copyFailed = false;
                this.copyTimer = setTimeout(() => this.copied = false, 1800);
            } catch {
                this.copied = false;
                this.copyFailed = true;
            }
        },
    };
}
