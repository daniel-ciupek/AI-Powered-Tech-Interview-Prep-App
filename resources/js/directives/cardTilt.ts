import type { Directive } from 'vue';

interface TiltOptions {
    maxTilt: number;
    perspective: number;
    scale: number;
}

interface TiltState {
    cleanup: () => void;
}

const STATE = new WeakMap<HTMLElement, TiltState>();
const DEFAULTS: TiltOptions = { maxTilt: 7, perspective: 1100, scale: 1.02 };
const FOCUS_CLASSES = ['card-focused', 'card-dimmed'];

function isFocusLocked(el: HTMLElement): boolean {
    return FOCUS_CLASSES.some((c) => el.classList.contains(c));
}

export const vCardTilt: Directive<HTMLElement, Partial<TiltOptions> | void> = {
    mounted(el, binding) {
        const opts = { ...DEFAULTS, ...(binding.value ?? {}) };
        const mq = window.matchMedia('(prefers-reduced-motion: reduce)');
        if (mq.matches) return;

        const idle = () => {
            el.style.transition = 'transform 360ms cubic-bezier(0.34, 1.56, 0.64, 1)';
            el.style.transform = `perspective(${opts.perspective}px) rotateX(0deg) rotateY(0deg) scale(1)`;
        };

        const onMove = (e: MouseEvent) => {
            if (isFocusLocked(el)) return;
            const rect = el.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            const cx = rect.width / 2;
            const cy = rect.height / 2;
            const ry = ((x - cx) / cx) * opts.maxTilt;
            const rx = -((y - cy) / cy) * opts.maxTilt;
            el.style.transition = 'transform 70ms linear';
            el.style.transform = `perspective(${opts.perspective}px) rotateX(${rx.toFixed(2)}deg) rotateY(${ry.toFixed(2)}deg) scale(${opts.scale})`;
        };

        const onLeave = () => {
            if (isFocusLocked(el)) return;
            idle();
        };

        el.style.willChange = 'transform';
        el.style.transformStyle = 'preserve-3d';

        el.addEventListener('mousemove', onMove);
        el.addEventListener('mouseleave', onLeave);

        STATE.set(el, {
            cleanup: () => {
                el.removeEventListener('mousemove', onMove);
                el.removeEventListener('mouseleave', onLeave);
                el.style.transform = '';
                el.style.willChange = '';
                el.style.transformStyle = '';
                el.style.transition = '';
            },
        });
    },

    unmounted(el) {
        STATE.get(el)?.cleanup();
        STATE.delete(el);
    },
};
