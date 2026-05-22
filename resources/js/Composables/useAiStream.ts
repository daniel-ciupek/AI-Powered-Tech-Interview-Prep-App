import { ref } from 'vue';

export interface StreamCallbacks {
    onChunk?: (chunk: string) => void;
    onDone?: (fullText: string) => void;
    onError?: (message: string) => void;
}

export function useAiStream() {
    const streaming = ref(false);
    const streamedText = ref('');
    let eventSource: EventSource | null = null;

    function stream(url: string, callbacks: StreamCallbacks = {}): void {
        if (streaming.value) return;

        streaming.value = true;
        streamedText.value = '';

        eventSource = new EventSource(url, { withCredentials: true });

        eventSource.addEventListener('chunk', (e: MessageEvent) => {
            const chunk = e.data as string;
            streamedText.value += chunk;
            callbacks.onChunk?.(chunk);
        });

        eventSource.addEventListener('done', () => {
            streaming.value = false;
            callbacks.onDone?.(streamedText.value);
            eventSource?.close();
            eventSource = null;
        });

        eventSource.addEventListener('error', () => {
            streaming.value = false;
            callbacks.onError?.('Stream connection failed.');
            eventSource?.close();
            eventSource = null;
        });
    }

    function stop(): void {
        eventSource?.close();
        eventSource = null;
        streaming.value = false;
    }

    return { streaming, streamedText, stream, stop };
}
