<script lang="ts">
import { defineComponent } from 'vue';
import { mapActions, mapGetters, mapMutations } from 'vuex';
import VoiceChat from './voiceChat.vue';

export default defineComponent({
    components: {
        VoiceChat,
    },

    data() {
        return {
            speedValues: [0.25, 0.5, 0.75, 1, 1.25, 1.5, 1.75, 2],
        };
    },
    computed: {
        ...mapGetters('player', [
            'isLiveSessionRunning',
            'isLiveSessionJoined',
            'getVolume',
            'getSpeed',
            'isPlaying',
        ]),
    },
    methods: {
        ...mapMutations('player', ['setPlaying', 'setVolume', 'setSpeed']),
        ...mapActions('player', ['updateCurrentTime']),
    },
});
</script>

<template>
    <div class="player-controls row justify-content-between align-items-center">
        <!-- For later issue: Back to Autoscroll functionality -->
        <!-- <button class="reset-view-btn">
            <font-awesome-icon icon="fa-arrow-rotate-left" />
        </button> -->
        <div class="btn-group dropup">
            <button
                type="button"
                class="btn volume"
                data-toggle="dropdown"
                aria-haspopup="true"
                aria-expanded="false"
            >
                <font-awesome-icon
                    v-if="getVolume === 0"
                    icon="fa-solid fa-volume-xmark"
                />
                <font-awesome-icon
                    v-else-if="getVolume < 51"
                    icon="fa-solid fa-volume-low"
                />
                <font-awesome-icon v-else icon="fa-solid fa-volume-high" />
            </button>
            <div class="dropdown-menu volume-range-menu">
                <div class="volume-range-wrapper">
                    <input
                        type="range"
                        min="0"
                        max="100"
                        :value="getVolume"
                        @input="setVolume"
                        class="volume-range"
                    />
                </div>
            </div>
        </div>
        <button
            class="btn backward"
            @click="
                updateCurrentTime({ notifyGroup: true, data: { delta: -15 } })
            "
        >
            <font-awesome-icon icon="fa-solid fa-rotate-left" />
            <span>15</span>
        </button>
        <button
            class="btn play-pause"
            @click="isPlaying ? setPlaying(false) : setPlaying(true)"
        >
            <font-awesome-icon
                :icon="`fa-solid ${isPlaying ? 'fa-pause' : 'fa-play'}`"
            />
        </button>
        <button
            class="btn forward"
            @click="
                updateCurrentTime({ notifyGroup: true, data: { delta: 15 } })
            "
        >
            <font-awesome-icon icon="fa-solid fa-rotate-right" />
            <span>15</span>
        </button>
        <VoiceChat v-if="isLiveSessionJoined" class="btn speed" />
        <div class="btn-group dropup" v-if="!isLiveSessionJoined">
            <button
                type="button"
                class="btn speed"
                data-toggle="dropdown"
                aria-haspopup="true"
                aria-expanded="false"
            >
                <font-awesome-icon icon="fa-solid fa-xmark" />
                <span>{{ getSpeed.toLocaleString() }}</span>
            </button>
            <div class="dropdown-menu">
                <button
                    v-for="speed in speedValues"
                    :key="speed"
                    type="button"
                    class="dropdown-item"
                    @click="setSpeed(speed)"
                >
                    {{ speed.toLocaleString() }}
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* For later issue: Back to Autoscroll functionality */
/* .reset-view-btn {
    position: fixed;
    left: calc(50% - 25px);
    width: 50px;
    height: 50px;
    background-color: crimson;
    border: none;
    border-radius: 50px;
    bottom: 50px;
    font-size: 16pt;
    display: flex;
    justify-content: center;
    align-items: center;
} */
.player-controls {
    /* gap: 5px; */ /* This will break the player-controls, when the side-drawer opens and a padding-left: 285px will arise. (doesn't seem to do much if i disable it either) */
    height: 50px;
}

button,
input {
    pointer-events: all;
}

.btn {
    transition: color 0.2s;
    font-size: 16pt;
    line-height: 20pt;
    color: var(--main-text-color);
    width: 80px;
    padding-left: 0;
    padding-right: 0;
}
.btn:hover {
    color: var(--link-hover-color);
}
.play-pause {
    transition: background-color 0.2s;
    color: white;
    background-color: var(--button-primary-background-color);
    border-radius: 7px;
    width: 40px;
    height: 40px;
    display: flex;
    justify-content: center;
    align-items: center;
}
.play-pause:hover {
    background-color: var(--button-primary-hover-color);
    color: white;
}
.forward,
.backward {
    position: relative;
    width: 50px;
}
.forward span,
.backward span {
    background-color: white;
    position: absolute;
    font-size: 13pt;
    font-weight: bold;
    line-height: 13pt;
    bottom: 4px;
}
.forward span {
    right: 5px;
}
.backward span {
    left: 5px;
}
.dropdown-menu {
    min-width: 100%;
}
.volume-range-menu {
    background: transparent;
    border-color: transparent;
}
.dropdown-item {
    padding-left: 1rem;
    padding-right: 1rem;
}
.dropdown-item:hover {
    background-color: var(--button-primary-background-color);
}

.volume-range {
    transform: rotate(270deg);
    position: absolute;
    bottom: 2em;
}
</style>
