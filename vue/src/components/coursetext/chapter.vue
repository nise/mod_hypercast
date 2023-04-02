<template>
    <h1 class="font-weight-bold" @click="toggleShowOverlay">
        {{ getCurrentChapter.value || '' }}
    </h1>
    <Overlay
        class="overlay col"
        @close="toggleShowOverlay"
        :show="getShowChapterOverlay"
    >
        <template #header> </template>
        <div>
            <playerChapterControl class="col" />
        </div>
    </Overlay>
</template>

<script lang="ts">
import { defineComponent } from 'vue';
import Overlay from '../utils/overlay.vue';
import playerChapterControl from '@/components/playerChapterControl.vue';
import { mapMutations, mapGetters } from 'vuex';

export default defineComponent({
    components: {
        playerChapterControl,
        Overlay,
    },
    computed: {
        ...mapGetters('player', ['getCurrentTime', 'getCurrentChapter', 'getShowChapterOverlay']),
    },
    methods: {
        ...mapMutations('player', ['setShowChapterOverlay']),
        //Show ChapterNavigator
        toggleShowOverlay() {
            const value = !this.getShowChapterOverlay;
            this.setShowChapterOverlay(value)
        },
    },
});
</script>

<style scoped>
h1 {
    cursor: pointer;
    pointer-events: all;
    user-select: none;
    --webkit-user-select: none;
    transition: color 0.2s;
}

.overlay {
    height: calc(100vh - 115px);
    overflow: Auto;
    pointer-events: all;
}

h3 {
    font-size: 18px;
    margin-top: 25px;
    margin-bottom: 22px;
    text-align: center;
    font-family: 'Metropolis';
    font-style: normal;
    font-weight: 400;
    font-size: 18px;
    line-height: 100%;
}

h1:hover {
    color: var(--link-hover-color);
}
</style>
