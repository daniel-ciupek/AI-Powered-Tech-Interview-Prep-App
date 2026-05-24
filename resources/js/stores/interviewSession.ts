import { defineStore } from 'pinia';
import { ref } from 'vue';

export interface ChatMessage {
    id: number;
    role: 'user' | 'assistant';
    content: string;
    created_at: string;
}

export interface InterviewSessionData {
    id: number;
    difficulty: 'junior' | 'mid' | 'senior';
    topic_tags: string[];
    status: 'active' | 'completed' | 'abandoned';
    final_report: string | null;
    messages: ChatMessage[];
}

export const useInterviewSession = defineStore('interviewSession', () => {
    const session = ref<InterviewSessionData | null>(null);
    const loading = ref(false);
    const sending = ref(false);
    const error = ref<string | null>(null);

    async function start(tags: string[], difficulty: string): Promise<void> {
        loading.value = true;
        error.value = null;
        try {
            const res = await window.axios.post<{ data: InterviewSessionData }>('/api/interview/start', {
                tags,
                difficulty,
            });
            session.value = res.data.data;
        } catch (err: unknown) {
            const e = err as { response?: { data?: { message?: string } } };
            error.value = e.response?.data?.message ?? 'Failed to start interview.';
        } finally {
            loading.value = false;
        }
    }

    async function sendMessage(content: string): Promise<void> {
        if (!session.value || sending.value) return;

        // Optimistic update
        const tempId = Date.now();
        session.value.messages.push({
            id: tempId,
            role: 'user',
            content,
            created_at: new Date().toISOString(),
        });

        sending.value = true;
        error.value = null;

        try {
            const res = await window.axios.post<{ data: ChatMessage }>(
                `/api/interview/${session.value.id}/message`,
                { content },
            );
            session.value.messages.push(res.data.data);
        } catch (err: unknown) {
            // Remove optimistic message on failure
            session.value.messages = session.value.messages.filter((m) => m.id !== tempId);
            const e = err as { response?: { data?: { message?: string } } };
            error.value = e.response?.data?.message ?? 'Failed to send message.';
        } finally {
            sending.value = false;
        }
    }

    const finishing = ref(false);
    const reportQueued = ref(false);
    const reportPolling = ref(false);

    let pollTimer: ReturnType<typeof setInterval> | null = null;
    let pollAttempts = 0;
    const POLL_INTERVAL_MS = 3000;
    const POLL_MAX_ATTEMPTS = 20; // 60 seconds total

    function stopPolling(): void {
        if (pollTimer !== null) {
            clearInterval(pollTimer);
            pollTimer = null;
        }
        reportPolling.value = false;
        pollAttempts = 0;
    }

    async function pollForReport(): Promise<void> {
        if (!session.value) return;
        pollAttempts++;

        if (pollAttempts > POLL_MAX_ATTEMPTS) {
            stopPolling();
            return;
        }

        try {
            const res = await window.axios.get<{ data: InterviewSessionData }>(
                `/api/interview/${session.value.id}`,
            );
            if (res.data.data.final_report) {
                session.value.final_report = res.data.data.final_report;
                session.value.status = res.data.data.status;
                reportQueued.value = false;
                stopPolling();
            }
        } catch {
            // silent — keep polling
        }
    }

    async function finishSession(): Promise<void> {
        if (!session.value || finishing.value) return;
        finishing.value = true;
        error.value = null;
        try {
            await window.axios.post(`/api/interview/${session.value.id}/finish`);
            session.value.status = 'completed';
            reportQueued.value = true;
            reportPolling.value = true;
            pollAttempts = 0;
            pollTimer = setInterval(pollForReport, POLL_INTERVAL_MS);
        } catch (err: unknown) {
            const e = err as { response?: { data?: { message?: string } } };
            error.value = e.response?.data?.message ?? 'Failed to finish session.';
        } finally {
            finishing.value = false;
        }
    }

    function reset(): void {
        stopPolling();
        session.value = null;
        loading.value = false;
        sending.value = false;
        finishing.value = false;
        reportQueued.value = false;
        reportPolling.value = false;
        error.value = null;
    }

    return {
        session,
        loading,
        sending,
        finishing,
        reportQueued,
        reportPolling,
        error,
        start,
        sendMessage,
        finishSession,
        reset,
    };
});
