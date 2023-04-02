<template>
    <div class="row justify-content-end flex-grow-1">
        <!-- In the design, the progressbar is 25px off the right side and has a width of 5px. A column adds a margin-x of 15px so we have to add a margin-left of 10px to achieve the 25px offset. This also aligns the progressbar with the settings icon which by design has an offset of 15/16 pixels to the container edge -->
        <div
            class="progressbarcontainer col-2 d-flex justify-content-end"
            style="margin-right: 10px"
        >
            <!-- <ProgressBar
                :current-time="getCurrentTime"
                :duration="getDuration"
            /> -->
        </div>
    </div>
    <div class="controlscontainer row justify-content-center">
        <div class="col-12 col-sm-9 mt-2 mb-2">
            <PlayerControls @skip="updateCurrentTime" />
            <audio
                ref="audioplayer"
                @play="setPlaying(true)"
                @pause="setPlaying(false)"
                @timeupdate="onTimeUpdate"
                @canplay="onCanPlay"
            >
                Your browser does not support the audio element.
                <AudioCues />
            </audio>
        </div>
    </div>
</template>
<script lang="ts">
import { defineComponent } from 'vue';
import { mapMutations, mapGetters, mapActions } from 'vuex';
import AudioCues from './audioCues.vue';
import PlayerControls from './playerControls.vue';
import Communication from '@/scripts/communication';
import { useToast } from 'vue-toastification';
import { LiveSession } from '@/models/liveSession';
// import { ResonanceAudio } from 'resonance-audio';

const progressSaveIntervalSeconds = 3;
let progressTime = 0;
const toast = useToast();

function saveCurrentTime(cmid, time, groupid, live) {
    Communication.webservice('savePlaytime', {
        cmid: cmid,
        timestamp: time,
        groupid: groupid,
        live: live,
    });
}

export default defineComponent({
    components: {
        AudioCues,
        PlayerControls,
    },
    computed: {
        getAudioPlayer(): HTMLAudioElement {
            return this.$refs.audioplayer as HTMLAudioElement;
        },
        ...mapGetters('player', [
            'isPlaying',
            'getCurrentTime',
            'getDuration',
            'getSpeed',
            'getVolume',
            'isLiveSessionJoined',
            'isLiveSessionRunning',
        ]),
        ...mapGetters('communication', ['getWebSocket']),
        ...mapGetters('groups', ['getGroupContext']),
        ...mapGetters(['getPluginBaseURL', 'getCourseModuleID', 'getUserID']),
    },
    watch: {
        isPlaying(shouldPlay: boolean): void {
            if (shouldPlay) {
                this.getAudioPlayer.play();
                let message = {
                    key: 'play',
                    payload: {
                        timestamp: this.getCurrentTime,
                        userId: this.getUserID,
                    },
                };
                this.sendMessage(message);
            } else {
                this.getAudioPlayer.pause();
                let message = {
                    key: 'pause',
                    payload: {
                        timestamp: this.getCurrentTime,
                        userId: this.getUserID,
                    },
                };
                this.sendMessage(message);
            }
        },
        getSpeed(newValue: number): void {
            this.getAudioPlayer.playbackRate = newValue;
        },
        getVolume(newValue: number): void {
            this.getAudioPlayer.volume = newValue * 0.01;
        },
        getCurrentTime(newValue: number): void {
            // Only set the audioplayer time, if we actually modified the time by hand (seeking or +15 / -15 seconds buttons). While playing, we update currentTime always with < 1 second. That means that we can set the audioplayers time if we get a value that is > 1 second (needs to be tested when seeking is implemented).
            if (Math.abs(this.getAudioPlayer.currentTime - newValue) > 1)
                this.getAudioPlayer.currentTime = newValue;
        },
        getWebSocket(websocket): void {
            websocket?.addEventListener('message', (event) => {
                this.onWebSocketMessage(event);
            });
        },
    },
    async mounted() {
        document.addEventListener('mousedown', this.validateConnection)

        // Create an AudioElement.
        let audioElement = this.getAudioPlayer;

        // Load an audio file into the AudioElement.
        audioElement.src = `${this.getPluginBaseURL}/assets/hyperaudio/audio/courses/ke6.mp3`;
        audioElement.playbackRate = this.getSpeed;
        audioElement.volume = this.getVolume * 0.01;

        const cmid = this.getCourseModuleID;
        let { timestamp } = await Communication.webservice('getPlaytime', {
            cmid,
        });
        audioElement.currentTime = timestamp;
        progressTime = timestamp;
        this.getWebSocket.addEventListener('message', (event) => {
            this.onWebSocketMessage(event);
        });
    },
    async unmounted() {
        document.removeEventListener('mousedown', this.validateConnection);
    },
    methods: {
        onWebSocketMessage(value) {
            let data = JSON.parse(value.data);
            let key = data['key'];
            let payload = data['payload'];

            if (key === 'updateTimestamp') {
                this.updateCurrentTime({ data: { exact: payload.timestamp } });
            }

            if (key === 'play' && this.getAudioPlayer.paused) {
                this.getAudioPlayer.currentTime = payload.timestamp;
                this.getAudioPlayer.play();
                this.onPlayerResumedByUser(payload.userId);
            } else if (key === 'pause' && !this.getAudioPlayer.paused) {
                this.getAudioPlayer.pause();
                this.getAudioPlayer.currentTime = payload.timestamp;
                this.onPlayerPauseByUser(payload.userId);
            } else if (key === 'userConnected') {
                let member = this.getGroupContext.members.find(
                    (member) => member.id === payload
                );
                if (!member) {
                    this.setGroupContextFromDB(this.getGroupContext.id).then(
                        () => {
                            let member = this.getGroupContext.members.find(
                                (member) => member.id === payload
                            );
                            let name = `${member?.firstname} ${member?.lastname}`;
                            toast.info(
                                `${name} ist der Live-Session beigetreten`
                            );
                        }
                    );
                } else {
                    let name = `${member?.firstname} ${member?.lastname}`;
                    toast.info(`${name} ist der Live-Session beigetreten`);
                }
            } else if (key === 'groupInfo') {
                if (this.isLiveSessionJoined) {
                    this.getAudioPlayer.pause();
                    this.getAudioPlayer.currentTime = payload.timestamp;
                }
                this.setLiveSession(payload as LiveSession);
            }
        },
        onCanPlay() {
            this.setDuration(this.getAudioPlayer.duration);
        },
        onPlayerPauseByUser(userId) {
            let member = this.getGroupContext.members.find(
                (member) => member.id === userId
            );
            let name = `${member?.firstname} ${member?.lastname}`;
            toast.info(`${name} hat die Wiedergabe angehalten`);
        },
        onPlayerResumedByUser(userId) {
            let member = this.getGroupContext.members.find(
                (member) => member.id === userId
            );
            let name = `${member?.firstname} ${member?.lastname}`;
            toast.info(`${name} hat die Wiedergabe fortgesetzt`);
        },
        onTimeUpdate() {
            const audioPlayer = this.getAudioPlayer;
            this.updateCurrentTime({
                data: { exact: audioPlayer.currentTime },
            });

            const currentTime = this.getCurrentTime;
            // Defines the next Timestamp on which the players currentTime will be saved.
            let newSaveTimestamp = progressTime + progressSaveIntervalSeconds;
            // If the player reached a new save mark...
            if (newSaveTimestamp === currentTime && this.isPlaying) {
                // Save the currentTime to the Database only if audio is playing to avoid saving during seeking
                progressTime = currentTime;
                return saveCurrentTime(
                    this.getCourseModuleID,
                    currentTime,
                    this.getGroupContext.id,
                    this.isLiveSessionJoined
                );
            }

            if (currentTime < progressTime || currentTime > newSaveTimestamp) {
                // Finally only update progressTime with the currentTime because user has seeked through audio file or reached saveTimestamp
                progressTime = currentTime;
            }
        },
        joinChat() {
            //this.chatIsRunning = !this.chatIsRunning;
        },
        ...mapMutations('player', [
            'setDuration',
            'setPlaying',
            'setLiveSessionJoined',
            'setLiveSession',
        ]),
        ...mapActions('player', [
            'updateCurrentTime',
            'joinLiveSession',
            'leaveLiveSession',
        ]),
        ...mapActions('groups', ['setGroupContextFromDB']),
        ...mapActions('communication', ['sendMessage', 'validateConnection']),
    },
});
</script>
<style>
.controlscontainer {
    background-color: white;
}

audio {
    width: 100%;
}

.speed {
    font-weight: bold;
}

button {
    pointer-events: all;
}
</style>
