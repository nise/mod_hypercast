<script lang="ts">
import { defineComponent } from 'vue';
import { mapMutations, mapGetters, mapActions } from 'vuex';
import { CourseTextSnippet } from '@/models';

export default defineComponent({
    computed: {
        getAudioPlayer(): HTMLAudioElement {
            return this.$refs.audioplayer as HTMLAudioElement;
        },
        ...mapGetters('player', [
            'getCurrentTime',
            'getCurrentChapter',
            'getChapters',
        ]),
    },

    methods: {
        navToChapter(key: CourseTextSnippet) {
            this.updateCurrentTime({
                notifyGroup: true,
                data: { exact: key.time },
            });
            this.setCurrentChapter(key.value);
            this.setShowChapterOverlay(false);
        },
        ...mapActions('player', ['updateCurrentTime']),
        ...mapMutations('player', [
            'setCurrentChapter',
            'setShowChapterOverlay',
        ]),
    },
});
</script>

<template>
    <div>
        <h3>Kapitelauswahl</h3>
        <div class="overlay">
            <div
                class="row"
                v-for="(mark, index) in getChapters"
                :key="index"
                @click="navToChapter(mark)"
            >
                <div
                    class="chapter-link"
                    v-if="mark === getCurrentChapter"
                    style="color: black; margin-left: -27px"
                >
                    {{ mark.value }}<br />
                </div>
                <div v-else class="chapter-link">{{ mark.value }}<br /></div>
            </div>
        </div>
    </div>
</template>

<style scoped>
h1,
form {
    padding: 12px;
}
@media only screen and (max-width: 612px) {
    .row {
        padding-left: 1px !important;
        margin-left: 1px !important;
    }
}
.chapter-link {
    cursor: pointer;
    transition: color 0.2s, ease-in-out 0.2s;
}
.chapter-link:hover {
    transform: translateX(-27px);
    transition: color 0.2s, ease-in-out 0.2s;
    color: var(--main-text-color);
}
.row {
    pointer-events: all;
    padding-right: 32px;
    padding-top: 15px;
    padding-bottom: 15px;
    padding-left: 59px;
    margin-left: 59px;
    font-family: 'Metropolis';
    font-style: normal;
    color: rgba(25, 25, 27, 0.3);
    font-size: 20px;
    font-weight: 700;
    font-size: 20px;
    line-height: 100%;
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
</style>
