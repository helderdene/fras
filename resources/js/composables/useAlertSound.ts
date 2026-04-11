import type { Ref } from 'vue';
import { ref } from 'vue';

export type UseAlertSoundReturn = {
    isEnabled: Ref<boolean>;
    enable: () => void;
    disable: () => void;
    play: () => void;
};

export function useAlertSound(): UseAlertSoundReturn {
    let audio: HTMLAudioElement | null = null;
    const isEnabled = ref(false);

    function enable(): void {
        if (!audio) {
            audio = new Audio('/sounds/alert-chime.mp3');
        }

        // User gesture unlocks audio context per D-09
        audio
            .play()
            .then(() => {
                audio!.pause();
                audio!.currentTime = 0;
                isEnabled.value = true;
            })
            .catch(() => {
                isEnabled.value = false;
            });
    }

    function disable(): void {
        isEnabled.value = false;
    }

    function play(): void {
        if (isEnabled.value && audio) {
            // Clone for overlapping playback of rapid events per D-08
            const clone = audio.cloneNode() as HTMLAudioElement;
            clone.play().catch(() => {});
        }
    }

    return { isEnabled, enable, disable, play };
}
