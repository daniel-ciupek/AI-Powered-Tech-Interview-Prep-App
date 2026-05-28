import confetti from 'canvas-confetti';

const MILESTONES = [7, 14, 30, 60, 100, 200, 365] as const;
const STORAGE_KEY = 'prepmind:celebrated-streaks';

function loadCelebrated(): number[] {
    try {
        const raw = localStorage.getItem(STORAGE_KEY);
        return raw ? (JSON.parse(raw) as number[]) : [];
    } catch {
        return [];
    }
}

function persistCelebrated(list: number[]): void {
    try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(list));
    } catch {
        /* ignore */
    }
}

function reducedMotion(): boolean {
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

export function useMilestoneConfetti() {
    function fire(streak: number): void {
        if (reducedMotion()) return;
        if (!MILESTONES.includes(streak as (typeof MILESTONES)[number])) return;

        const celebrated = loadCelebrated();
        if (celebrated.includes(streak)) return;

        const colors = ['#10b981', '#14b8a6', '#06b6d4', '#8b5cf6', '#ec4899'];
        const particles = Math.min(60 + streak, 220);

        confetti({
            particleCount: particles,
            spread: 90,
            origin: { x: 0.5, y: 0.35 },
            startVelocity: 38,
            ticks: 220,
            gravity: 0.8,
            scalar: 1.05,
            colors,
        });

        window.setTimeout(() => {
            confetti({
                particleCount: Math.floor(particles * 0.5),
                spread: 110,
                origin: { x: 0.25, y: 0.45 },
                angle: 60,
                startVelocity: 32,
                colors,
            });
            confetti({
                particleCount: Math.floor(particles * 0.5),
                spread: 110,
                origin: { x: 0.75, y: 0.45 },
                angle: 120,
                startVelocity: 32,
                colors,
            });
        }, 180);

        persistCelebrated([...celebrated, streak]);
    }

    return { fire };
}
