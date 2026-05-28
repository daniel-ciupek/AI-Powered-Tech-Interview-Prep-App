import { ref, watch, onMounted } from 'vue';

/**
 * Animates a number from 0 to `target` over `duration` ms using easeOutExpo.
 * Under prefers-reduced-motion the final value is shown immediately.
 */
export function useAnimatedNumber(
    getTarget: () => number,
    duration = 1200,
) {
    const displayed = ref(0);
    let rafId: number | null = null;

    const prefersReduced = typeof window !== 'undefined'
        ? window.matchMedia('(prefers-reduced-motion: reduce)').matches
        : false;

    function easeOutExpo(t: number): number {
        return t === 1 ? 1 : 1 - Math.pow(2, -10 * t);
    }

    function animate(target: number) {
        if (prefersReduced) {
            displayed.value = target;
            return;
        }

        if (rafId !== null) {
            cancelAnimationFrame(rafId);
        }

        const start = displayed.value;
        const delta = target - start;
        const startTime = performance.now();

        function step(now: number) {
            const elapsed = now - startTime;
            const progress = Math.min(elapsed / duration, 1);
            displayed.value = Math.round(start + delta * easeOutExpo(progress));

            if (progress < 1) {
                rafId = requestAnimationFrame(step);
            } else {
                displayed.value = target;
                rafId = null;
            }
        }

        rafId = requestAnimationFrame(step);
    }

    onMounted(() => {
        animate(getTarget());
    });

    watch(getTarget, (newVal) => {
        animate(newVal);
    });

    return { displayed };
}
