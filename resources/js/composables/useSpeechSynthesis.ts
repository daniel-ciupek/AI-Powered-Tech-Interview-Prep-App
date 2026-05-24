import { onBeforeUnmount, readonly, ref } from 'vue';

type VoiceGender = 'male' | 'female' | 'any';

interface SpeakOptions {
    lang?: string;
    rate?: number;
    pitch?: number;
    voice?: SpeechSynthesisVoice;
    preferGender?: VoiceGender;
}

// Web Speech API does not expose gender, so we heuristically match
// common voice names by language. Lowercased substrings to look for.
const MALE_VOICE_HINTS = [
    'marek', 'krzysztof', 'jacek', 'filip', 'paweł', 'pawel', 'tomasz',
    'wojciech', 'adam', 'piotr', 'jan',
    'male', 'man',
];
const FEMALE_VOICE_HINTS = [
    'paulina', 'ewa', 'agata', 'zofia', 'agnieszka', 'maja', 'ola', 'anna',
    'female', 'woman',
];

const STORAGE_KEY = 'prepmind.tts.voiceName';

const speaking = ref(false);
const supported = ref(typeof window !== 'undefined' && 'speechSynthesis' in window);
const voices = ref<SpeechSynthesisVoice[]>([]);
const selectedVoiceName = ref<string | null>(
    typeof window !== 'undefined' ? window.localStorage.getItem(STORAGE_KEY) : null,
);

function loadVoices(): void {
    if (!supported.value) return;
    voices.value = window.speechSynthesis.getVoices();
}

function setSelectedVoice(name: string | null): void {
    selectedVoiceName.value = name;
    if (typeof window === 'undefined') return;
    if (name === null || name === '') window.localStorage.removeItem(STORAGE_KEY);
    else window.localStorage.setItem(STORAGE_KEY, name);
}

if (supported.value) {
    loadVoices();
    window.speechSynthesis.addEventListener('voiceschanged', loadVoices);
}

function matchesLang(voice: SpeechSynthesisVoice, lang: string): boolean {
    const v = voice.lang.toLowerCase();
    const target = lang.toLowerCase();
    return v === target || v.startsWith(target.split('-')[0]);
}

function pickVoice(lang: string, preferGender: VoiceGender = 'male'): SpeechSynthesisVoice | undefined {
    const all = voices.value;
    if (all.length === 0) return undefined;

    if (selectedVoiceName.value) {
        const stored = all.find((v) => v.name === selectedVoiceName.value);
        if (stored) return stored;
    }

    const langMatches = all
        .filter((v) => matchesLang(v, lang))
        .sort((a, b) => {
            // Prefer exact language match (pl-PL) over prefix-only (pl)
            const exact = lang.toLowerCase();
            return Number(b.lang.toLowerCase() === exact) - Number(a.lang.toLowerCase() === exact);
        });
    if (langMatches.length === 0) return undefined;
    if (preferGender === 'any') return langMatches[0];

    const wanted = preferGender === 'male' ? MALE_VOICE_HINTS : FEMALE_VOICE_HINTS;
    const avoid = preferGender === 'male' ? FEMALE_VOICE_HINTS : MALE_VOICE_HINTS;

    const wantedHit = langMatches.find((v) => wanted.some((h) => v.name.toLowerCase().includes(h)));
    if (wantedHit) return wantedHit;

    const neutralHit = langMatches.find((v) => !avoid.some((h) => v.name.toLowerCase().includes(h)));
    return neutralHit ?? langMatches[0];
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

        const voice = options.voice ?? pickVoice(utterance.lang, options.preferGender ?? 'male');
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
        selectedVoiceName: readonly(selectedVoiceName),
        setSelectedVoice,
    };
}
