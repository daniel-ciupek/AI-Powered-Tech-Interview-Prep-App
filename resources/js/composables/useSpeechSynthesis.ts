import { onBeforeUnmount, readonly, ref } from 'vue';

interface SpeakOptions {
    lang?: string;
    rate?: number;
    pitch?: number;
    voice?: SpeechSynthesisVoice;
}

const speaking = ref(false);
const supported = ref(typeof window !== 'undefined' && 'speechSynthesis' in window);
const voices = ref<SpeechSynthesisVoice[]>([]);

function loadVoices(): void {
    if (!supported.value) return;
    voices.value = window.speechSynthesis.getVoices();
}

if (supported.value) {
    loadVoices();
    window.speechSynthesis.addEventListener('voiceschanged', loadVoices);
}

function pickVoice(lang: string): SpeechSynthesisVoice | undefined {
    const all = voices.value;
    if (all.length === 0) return undefined;
    const exact = all.find((v) => v.lang.toLowerCase() === lang.toLowerCase());
    if (exact) return exact;
    const prefix = lang.split('-')[0].toLowerCase();
    return all.find((v) => v.lang.toLowerCase().startsWith(prefix));
}

export function useSpeechSynthesis() {
    let currentUtterance: SpeechSynthesisUtterance | null = null;

    function cancel(): void {
        if (!supported.value) return;
        window.speechSynthesis.cancel();
        speaking.value = false;
        currentUtterance = null;
    }

    function speak(text: string, options: SpeakOptions = {}): void {
        if (!supported.value || text.trim().length === 0) return;
        cancel();

        const utterance = new SpeechSynthesisUtterance(text);
        utterance.lang = options.lang ?? 'pl-PL';
        utterance.rate = options.rate ?? 1;
        utterance.pitch = options.pitch ?? 1;

        const voice = options.voice ?? pickVoice(utterance.lang);
        if (voice) utterance.voice = voice;

        utterance.onstart = () => {
            speaking.value = true;
        };
        utterance.onend = () => {
            speaking.value = false;
            currentUtterance = null;
        };
        utterance.onerror = () => {
            speaking.value = false;
            currentUtterance = null;
        };

        currentUtterance = utterance;
        window.speechSynthesis.speak(utterance);
    }

    onBeforeUnmount(() => {
        if (currentUtterance) cancel();
    });

    return {
        speak,
        cancel,
        speaking: readonly(speaking),
        supported: readonly(supported),
        voices: readonly(voices),
    };
}
