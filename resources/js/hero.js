export default function hero(count) {
    return {
        current: 0,
        playing: false,
        timer: null,
        motion: null,
        motionChange: null,
        touchStart: null,

        init() {
            this.motion = window.matchMedia('(prefers-reduced-motion: reduce)');
            this.motionChange = event => { if (event.matches) this.pause(); };
            this.motion.addEventListener('change', this.motionChange);
            if (!this.motion.matches && !document.hidden) this.play();
        },

        destroy() {
            this.pause();
            this.motion?.removeEventListener('change', this.motionChange);
        },

        play() {
            if (this.playing || this.motion?.matches) return;
            this.playing = true;
            this.timer = setInterval(() => {
                if (!document.hidden) this.current = (this.current + 1) % count;
            }, 6000);
        },

        pause() {
            clearInterval(this.timer);
            this.playing = false;
        },

        show(index) {
            this.pause();
            this.current = (index + count) % count;
        },

        finishTouch(event) {
            if (!this.touchStart || !event.changedTouches.length) return;
            const touch = event.changedTouches[0];
            const horizontal = touch.clientX - this.touchStart.clientX;
            const vertical = touch.clientY - this.touchStart.clientY;
            this.touchStart = null;
            if (Math.abs(horizontal) > 55 && Math.abs(horizontal) > Math.abs(vertical) * 1.3) {
                this.show(this.current + (horizontal < 0 ? 1 : -1));
            }
        },
    };
}
