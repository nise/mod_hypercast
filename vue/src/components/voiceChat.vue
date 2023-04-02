<script lang="ts">
import { defineComponent } from 'vue';
import { mapGetters, mapMutations, mapActions } from 'vuex';

declare interface Message {
    key: string;
    payload: {
        displayName: string;
        uuid: string;
        dest: number;
        destUUID: string;
        peerUUID: string;
        ice: RTCIceCandidate;
        sdp: RTCSessionDescription;
    };
}

export default defineComponent({
    data() {
        return {
            pcConfig: {
                iceServers: [
                    {
                        urls: ['stun:iphone-stun.strato-iphone.de'],
                    },
                    {
                        username: '-',
                        credential: '-',
                        urls: [
                            'turn:iphone-stun.strato-iphone.de:3478?transport=udp',
                            'turn:iphone-stun.strato-iphone.de:3478?transport=tcp',
                        ],
                    },
                ],
            } as RTCConfiguration,
            localStream: {} as MediaStream,
            peerConnections: [] as RTCPeerConnection[],
            localStreamConstraints: {
                audio: true,
                video: false,
            },
            localUUID: '',
            localDisplayName: '',
            muted: false,
            // connection set up
            bOn: false,
        };
    },
    computed: {
        ...mapGetters('groups', ['getGroupContext']),
        ...mapGetters('player', ['isPlaying']),
        ...mapGetters(['getPluginBaseURL', 'getCourseModuleID', 'getUserID']),
        ...mapGetters('communication', ['getWebSocket']),
    },
    watch: {
        async isPlaying(shouldplay: boolean): Promise<void> {
            if (!shouldplay) {
                await this.logPauseDueLiveSession(
                    this.getGroupContext.id,
                    this.getUserID
                );
            }
            this.muted = false;
            this.muteMic(false);
        },
        getWebSocket(webSocket): void {
            webSocket?.addEventListener('message', (event) => {
                this.gotMessageFromServer(event);
            });
        },
    },

    mounted: function (): void {
        this.start();
    },
    unmounted: function (): void {
        this.muted = false;
        this.muteMic(false);
        this.peerConnections.length = 0;
    },

    methods: {
        async start(): Promise<void> {
            this.localUUID = this.UUIDv4();
            // set up local audio stream
            if (navigator.mediaDevices.getUserMedia) {
                navigator.mediaDevices
                    .getUserMedia(this.localStreamConstraints)
                    .then((stream) => {
                        this.localStream = stream;
                        this.muteMic(false);
                        var locAudio = document.querySelector(
                            '#localAudio'
                        ) as HTMLAudioElement;
                        locAudio.srcObject = stream;
                    })
                    .catch(this.onError)

                    // set up websocket and message all existing clients
                    .then(() => {
                        this.getWebSocket.addEventListener(
                            'message',
                            (event) => {
                                this.gotMessageFromServer(event);
                            }
                        );
                    })

                    .catch(this.onError);
            } else {
                alert('Your browser does not support getUserMedia API');
            }
        },
        gotMessageFromServer(event) {
            let data = JSON.parse(event.data);
            let key = data['key'];
            if (key === 'userConnected') {
                this.setdisplayName(this.localUUID);
                this.setUUID(this.localUUID);
                this.commitChat();
                return;
            }
            if (key != 'getWebRTCMessage') return;
            let payload = data['payload'];
            let signal: Message = JSON.parse(payload);
            var peerUuid = signal.payload.uuid;

            // Ignore messages that are not for us or from ourselves
            if (
                peerUuid == this.localUUID ||
                //(signal.dest != this.localUUID && signal.dest != 'all')
                (signal.payload.destUUID != this.localUUID &&
                    signal.payload.dest != this.getGroupContext.id)
            )
                return;

            if (
                signal.payload.displayName &&
                signal.payload.dest == this.getGroupContext.id
            ) {
                // set up peer connection object for a newcomer peer
                this.setUpPeer(peerUuid, signal.payload.displayName);
                this.setdisplayName(this.localUUID);
                this.setUUID(this.localUUID);
                this.setdestUUID(peerUuid);
                this.commitPeer();
            } else if (
                signal.payload.displayName &&
                signal.payload.destUUID == this.localUUID
            ) {
                // initiate call if we are the newcomer peer
                this.setUpPeer(peerUuid, signal.payload.displayName, true);
            } else if (signal.payload.sdp) {
                this.peerConnections[peerUuid]
                    .setRemoteDescription(
                        new RTCSessionDescription(signal.payload.sdp)
                    )
                    .then(() => {
                        // Only create answers in response to offers
                        if (signal.payload.sdp.type === 'offer') {
                            this.peerConnections[peerUuid]
                                .createAnswer()
                                .then((description) =>
                                    this.createdDescription(
                                        description,
                                        peerUuid
                                    )
                                )
                                .catch(this.onError);
                        }
                    })
                    .catch(this.onError);
            } else if (signal.payload.ice) {
                this.peerConnections[peerUuid]
                    .addIceCandidate(new RTCIceCandidate(signal.payload.ice))
                    .catch(this.onError);
            }
        },
        setUpPeer(peerUuid, displayName, initCall = false) {
            this.peerConnections[peerUuid] = new RTCPeerConnection(
                this.pcConfig
            );
            this.peerConnections[peerUuid].onicecandidate = (event) =>
                this.gotIceCandidate(event, peerUuid);
            this.peerConnections[peerUuid].ontrack = (event) =>
                this.gotRemoteStream(event, peerUuid);
            this.peerConnections[peerUuid].oniceconnectionstatechange = (
                event
            ) => this.checkPeerConnection(event, peerUuid);

            this.peerConnections[peerUuid].onconnectionstatechange = (event) =>
                this.checkPeerConnection(event, peerUuid);

            for (const track of this.localStream.getTracks()) {
                this.peerConnections[peerUuid].addTrack(
                    track,
                    this.localStream
                );
            }

            if (initCall) {
                this.peerConnections[peerUuid]
                    .createOffer()
                    .then((description) =>
                        this.createdDescription(description, peerUuid)
                    )
                    .catch(this.onError);
            }
        },

        gotIceCandidate(event, peerUuid) {
            if (event.candidate != null) {
                this.setice(event.candidate);
                this.setUUID(this.localUUID);
                this.setdestUUID(peerUuid);
                this.commitICE();
            }
        },

        createdDescription(description, peerUuid) {
            this.peerConnections[peerUuid]
                .setLocalDescription(description)
                .then(() => {
                    this.setdestUUID(peerUuid);
                    this.setsdp(
                        this.peerConnections[peerUuid].localDescription
                    );
                    this.setUUID(this.localUUID);
                    this.commitSDP();
                })
                .catch(this.onError);
        },

        gotRemoteStream(event, peerUuid) {
            //StreamConnection is unique, so should the Audioelement
            let audContainer = document.getElementById(
                'remoteAudio_' + peerUuid
            ) as HTMLElement;
            if (typeof audContainer != 'undefined' && audContainer != null) {
                let audElementLoc = audContainer
                    .childNodes[0] as HTMLAudioElement;
                audElementLoc.srcObject = event.streams[0];
                return;
            }
            //assign stream to new HTML audio element
            let audElement = document.createElement('audio');
            audElement.setAttribute('autoplay', '');
            audElement.setAttribute('hidden', '');
            audElement.setAttribute('class', 'audioContainer');
            audElement.srcObject = event.streams[0];
            audContainer = document.createElement('div');
            audContainer.setAttribute('id', 'remoteAudio_' + peerUuid);
            audContainer.setAttribute('class', 'audioContainer');
            audContainer.appendChild(audElement);
            if (typeof audContainer != 'undefined' && audContainer != null) {
                return;
            }
            document.getElementById('audios')!.appendChild(audContainer);
        },

        checkPeerConnection(event, peerUuid) {
            var state = this.peerConnections[peerUuid].iceConnectionState;
            if (
                state === 'failed' ||
                state === 'closed' ||
                state === 'disconnected'
            ) {
                this.peerConnections = this.peerConnections.filter(
                    (pc) =>
                        pc.iceConnectionState === 'failed' ||
                        pc.iceConnectionState === 'closed' ||
                        pc.iceConnectionState === 'disconnected'
                );
                try {
                    document
                        .getElementById('audios')!
                        .removeChild(
                            document.getElementById('remoteAudio_' + peerUuid)!
                        );
                } catch (error) {
                    this.onError;
                }
            }
        },
        onError(error) {
            console.log(error);
        },

        // Streams where seperated by Fake-UUID, so generate one
        // Source: http://stackoverflow.com/a/105074/515584
        UUIDv4(): string {
            return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(
                /[xy]/g,
                function (c) {
                    var r = (Math.random() * 16) | 0,
                        v = c == 'x' ? r : (r & 0x3) | 0x8;
                    return v.toString(16);
                }
            );
        },

        async muteMic(fromEvent: boolean): Promise<void> {
            this.muted = !this.muted;
            if (!this.muted && fromEvent) {
                await this.logUnmuteVCDueLiveSession(
                    this.getGroupContext.id,
                    this.getUserID
                );
            }
            try {
                this.localStream.getAudioTracks().forEach((track) => {
                    track.enabled = !this.muted;
                    // set up connection only once
                    if (this.bOn) return;
                    this.setdisplayName(this.localUUID);
                    this.setUUID(this.localUUID);
                    this.commitChat();
                    this.bOn = true;
                });
            } catch (error) {
                this.onError;
            }
        },

        ...mapActions('communication', ['sendMessage']),
        ...mapActions('player', [
            'commitChat',
            'commitPeer',
            'commitICE',
            'commitSDP',
            'commitLeaveMsg',
            'logUnmuteVCDueLiveSession',
            'logPauseDueLiveSession',
        ]),
        ...mapMutations('player', [
            'setUUID',
            'setdisplayName',
            'setlocalUUID',
            'setpeerUUID',
            'setice',
            'setdestUUID',
            'setsdp',
        ]),
    },
});
</script>

<template>
    <div>
        <button
            type="button"
            class="btn speed"
            aria-haspopup="true"
            aria-expanded="false"
            :disabled="isPlaying"
            @click="muteMic(true)"
        >
            <font-awesome-icon
                v-if="muted === true"
                class="fas fa-microphone-slash fa-fw fa-1x"
                icon="fa-solid fa-microphone-slash"
            />
            <font-awesome-icon
                v-else
                icon="fa-solid fa-microphone"
                class="fas fa-microphone fa-fw fa-1x"
            />
        </button>
        <div id="audios" class="audios">
            <div id="localAudioContainer" class="audioContainer">
                <audio id="localAudio" autoplay muted hidden></audio>
            </div>
            <!-- Remote audios get appended here -->
        </div>
    </div>
</template>

<style scoped>
:root {
    --rowHeight: 0vh;
    --colWidth: 0vw;
}

body {
    background: black;
}

.audios {
    display: grid;
    grid-gap: 0px;
    grid-auto-flow: row;

    grid-template-columns: repeat(auto-fit, minmax(var(--colWidth), 1fr));
}

.audioContainer {
    position: relative;
    overflow: hidden;

    min-height: var(--rowHeight);
    min-width: var(--colWidth);
}

.audioContainer audio {
    position: absolute;
    left: 0;
    bottom: 0;
    height: 0;
    width: 0%;
}
</style>
