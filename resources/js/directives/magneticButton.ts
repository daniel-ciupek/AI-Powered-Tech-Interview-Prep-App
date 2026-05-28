import type { Directive } from 'vue';

interface MagneticOptions {
    strength: number;
    radius: number;
}

interface MagneticState {
    cleanup: () => void;
}

const STATE = new WeakMap<HTMLElement, MagneticState>();
const DEFAULTS: MagneticOptions = { strength: 0.28, radius: 110 };

export const vMagneticButton: Directive<HTMLElement, Partial<MagneticOptions> | void> = {
    mounted(el, binding) {
        const opts = { ...DEFAULTS, ...(binding.value ?? {}) };
        const mq = window.matchMedia('(prefers-reduced-motion: reduce)');
        if (mq.matches) return;

        let lastApplied = false;

        const onMove = (e: MouseEvent) => {
            const rect = el.getBoundingClientRect();
            const cx = rect.left + rect.width / 2;
            const cy = rect.top + rect.height / 2;
            const dx = e.clientX - cx;
            const dy = e.clientY - cy;
            const distance = Math.hypot(dx, dy);

            if (distance < opts.radius) {
                const pull = (1 - distance / opts.radius) * opts.strength;
                el.style.transition = 'transform 90ms ease-out';
                el.style.transform = `translate3d(${(dx * pull).toFixed(1)}px, ${(dy * pull).toFixed(1)}px, 0)`;
                lastApplied = true;
            } else if (lastApplied) {
                el.style.transition = 'transform 420ms cubic-bezier(0.34, 1.56, 0.64, 1)';
                el.style.transform = 'translate3d(0, 0, 0)';
                lastApplied = false;
            }
        };

        el.style.willChange = 'transform';
        window.addEventListener('mousemove', onMove, { passive: true });

        STATE.set(el, {
            cleanup: () => {
                window.removeEventListener('mousemove', onMove);
                el.style.transform = '';
                el.style.willChange = '';
                el.style.transition = '';
            },
        });
    },

    unmounted(el) {
        STATE.get(el)?.cleanup();
        STATE.delete(el);
    },
};
