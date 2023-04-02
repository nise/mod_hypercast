<template>
    <div ref="playeroverlay" class="playeroverlay container-fluid">
        <div class="row h-100 justify-content-center">
            <div class="col-12 col-md-10 col-lg-7 col-xl-6">
                <div class="d-flex h-100 flex-column position-relative">
                    <div class="headercontainer row pt-4">
                        <div class="col-12">
                            <PlayerHeader />
                        </div>
                    </div>
                    <div class="chaptercontainer row pt-3">
                        <div class="col-10 d-flex align-items-center">
                            <Chapter />
                        </div>
                    </div>
                    <div class="row flex-grow-1">
                        <div class="col-12 d-flex flex-column">
                            <AudioPlayer />
                        </div>
                    </div>
                    <ProgressBar
                        :current-time="getCurrentTime"
                        :duration="getDuration"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<script lang="ts">
import PlayerHeader from '@/components/playerHeader.vue';
import AudioPlayer from '@/components/player.vue';
import ProgressBar from '@/components/progressBar.vue';
import Chapter from '@/components/coursetext/chapter.vue';
import { reactOnDrawerChange } from '@/scripts/drawer';
import { mapGetters } from 'vuex';
import { defineComponent } from 'vue';

export default defineComponent({
    components: {
        PlayerHeader,
        AudioPlayer,
        Chapter,
        ProgressBar,
    },
    computed: {
        ...mapGetters('player', ['getCurrentTime', 'getDuration']),
    },
    mounted() {
        // Get the root playeroverlay div itself.
        const playeroverlay = this.$refs.playeroverlay as HTMLDivElement;
        reactOnDrawerChange(playeroverlay);
    },
});
</script>

<style scoped>
.playeroverlay {
    position: fixed;
    top: 50px;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 1;
    pointer-events: none;
    transition: padding-left 0.5s ease;
}

.playeroverlay.drawer-open {
    padding-left: 285px;
}

/* reset the padding left for phone sized screens  */
@media (max-width: 767.98px) {
    .playeroverlay.drawer-open {
        padding-left: 15px;
    }
}

.headercontainer,
.chaptercontainer {
    background-color: white;
}
</style>
