<template>
    <!-- see https://developer.mozilla.org/en-US/docs/Web/HTML/Element/track for details -->
    <track
        ref="audioMarkerTrack"
        :src="
            getPluginBaseURL + '/assets/hyperaudio/vtt/ke6.vtt'
        "
        kind="metadata"
        label="Audio Cues"
        srclang="de"
        mode="hidden"
        default
        @load="setupCueEnterEvents()"
    />
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { mapGetters } from 'vuex';
import { playSound } from '@/utils';

interface VTTCueData {
    time: number
    value: string
}

export default defineComponent({
    name: 'AudioCues',
    computed: {
        ...mapGetters([
            'getPluginBaseURL',
        ]),
        ...mapGetters('settings', [
            'getFilteredSoundfiles'
        ]),
        ...mapGetters('player', ['getVolume'])
    },
    methods: {
        setupCueEnterEvents(){
            // This code could not be used in the 'mounted' lifecycle hook, since the corresponding .vtt file was not loaded during that call time.
            // Therefore we can call this function at the 'load' event and give every cue an event listener that it should play a sound when it enters.
            const track = (this.$refs.audioMarkerTrack as HTMLTrackElement).track;
            if (!track.cues)
                return

            for (const cuekey of Object.keys(track.cues)) {
                // The cue itself will hold the values 'time' and 'value' in a 'text' variable. That is JSON in text format
                // Therefore we first have to parse this JSON so we can use it later in our code.
                // 'time' then corresponds to the timestamp this cue happens and 'value' states the type of cue that happens.
                const cue: VTTCue = track.cues[cuekey] as VTTCue
                const cueData: VTTCueData = JSON.parse(cue.text)

                cue.addEventListener('enter', () => {
                    // The string in front of the dash will always be the type of the cue. Therefore we can split the type string
                    // and use the first element from it so we can finally lookup the corresponding soundfile for this cue from the 'soundfile' array above.
                    playSound(this.getFilteredSoundfiles[cueData.value.split('-')[0]], this.getVolume * 0.01)
                })
            }
        },
    },
});
</script>
