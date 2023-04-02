import store from '@/store';

export function filterAudioCues(soundfiles, cueSettings) {
    const filteredFiles = {...soundfiles}
    Object.keys(cueSettings).forEach((key) => {
        if (cueSettings[key] == false){
            filteredFiles[key] = undefined;
        }
    });
    return filteredFiles;
}

export function playSound(filename: string, volume = 1): void {
    // If there is no sound specified for the current cue, break
    if (!filename)
        return

    const audio = new Audio(
        `${store.getters.getPluginBaseURL}/assets/hyperaudio/audio/cues/${filename}`
    );

    audio.volume = volume;
    audio.play();
}