<template>
    <div ref="courseTextWrapper" id="courseTextWrapper">
        <CourseTextSnippet
            v-for="(textBlock, index) in getCourseText"
            :key="index"
            :type="textBlock.type"
            :value="textBlock.value"
            :timeStart="textBlock.time"
            :timeEnd="textBlock.timeEnd"
            :isSeeking="isSeeking"
            :seeking-time="getPercentageSeekedToCurrentTime"
            :caption="textBlock.caption"
            :noAutoScroll="getScrolledByUser"
            :currentTime="getCurrentTime"
            :hasComments="hasComments(textBlock.time)"
            @click.prevent="clicked"
            v-memo="[
                getCurrentTime >= textBlock.time &&
                    getCurrentTime <= textBlock.timeEnd,
                getPercentageSeekedToCurrentTime >= textBlock.time &&
                    getPercentageSeekedToCurrentTime <= textBlock.timeEnd,
                hasComments(textBlock.time),
            ]"
        />
    </div>
    <div class="course-tooltip" ref="tooltip" role="tooltip">
        <button
            type="button"
            class="close"
            aria-label="Close"
            @click="closePopper()"
        >
            <span aria-hidden="true">&times;</span>
        </button>
        <button type="button" class="btn border-right" @click="resetPlayerTime">
            <font-awesome-icon icon="fa-solid fa-play" />
            <span class="ml-1"
                >@ {{ formattedAudioTime(getSelectedTime) }}</span
            >
        </button>
        <button type="button" class="btn" @click="openComments">
            <font-awesome-icon icon="fa-solid fa-comments" />
        </button>
        <div data-popper-arrow></div>
    </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue';
import CourseTextSnippet from '@/components/coursetext/courseTextSnippet.vue';
import { mapActions, mapGetters, mapMutations } from 'vuex';
import { createPopper, Instance as PopperInstance } from '@popperjs/core';
import { registerClickaway } from '@/utils';

export default defineComponent({
    components: {
        CourseTextSnippet,
    },
    data() {
        return {
            popper: undefined as PopperInstance | undefined,
        };
    },
    async mounted() {
        // We can't use the 'scroll' event for this, since the autoscroll will trigger it as well. Therefore rely on events triggered by real user input.
        // When the user scrolls the content by the mousewheel, block automatic scrolling.
        window.addEventListener('wheel', () => {
            this.setScrolledByUser(true);
        });

        // When the user scrolls the content by touchmove (mousewheel event for smartphones so to say), block automatic scrolling.
        (this.$refs.courseTextWrapper as Element).addEventListener(
            'touchmove',
            () => {
                this.setScrolledByUser(true);
            }
        );

        // calculate the currentChapter on every Scroll event
        window.addEventListener('scroll', () => {
            // get the chapter that is negative BUT closest to the chapter Header.
            const allChapters = Array.from(
                document.querySelectorAll('.chapter')
            );

            const fixedChapterY = document
                .querySelector('.chaptercontainer')
                ?.getBoundingClientRect().bottom;

            if (!fixedChapterY) return '';

            // We only need the chapters which delta with the fixedChapter bottom value is negative / the chapters which already surpassed the chapterHeader.
            const chaptersFilteredIfPositive = allChapters.filter(
                (chapter) =>
                    chapter.getBoundingClientRect().bottom - fixedChapterY < 0
            );

            // If you are on the top of the document, all chapters are > 0 if subtracted with the chapterHeader.
            // Therefore this array would be empty
            if (chaptersFilteredIfPositive.length === 0)
                return this.setCurrentChapter(null);

            // This will always return the chapter which is closest to the chapterHeader.
            const nextChapter = chaptersFilteredIfPositive.reduce(
                (chapterA, chapterB) => {
                    const chapterADelta =
                        chapterA.getBoundingClientRect().bottom - fixedChapterY;
                    const chapterBDelta =
                        chapterB.getBoundingClientRect().bottom - fixedChapterY;

                    return chapterADelta < chapterBDelta ? chapterB : chapterA;
                }
            );

            // finally, set the next ChapterHeader value.
            this.setCurrentChapter(nextChapter.innerHTML);
        });

        await this.loadTimestamps(this.getGroupContext.id);

        registerClickaway(
            [
                document.querySelector(
                    'div[role=tooltip].course-tooltip'
                ) as HTMLDivElement,
            ],
            this.closePopper
        );
    },
    methods: {
        clicked(e, payload) {
            this.setSelectedTime(payload.timeStart);

            const containerBouningBox: DOMRect | undefined = document
                .querySelector('.controlscontainer')
                ?.getBoundingClientRect();
            const paddingBottom: number = containerBouningBox
                ? containerBouningBox.bottom - containerBouningBox.top
                : 0;
            const paddingTop: number =
                document
                    .querySelector('.chaptercontainer')
                    ?.getBoundingClientRect().bottom || 0;

            this.popper = createPopper(
                e.target,
                this.$refs.tooltip as HTMLDivElement,
                {
                    placement: 'top',
                    modifiers: [
                        {
                            name: 'flip',
                            options: {
                                padding: {
                                    top: paddingTop,
                                    bottom: paddingBottom,
                                },
                            },
                        },
                    ],
                }
            );
        },
        closePopper() {
            if (this.popper) {
                this.popper.destroy();
                this.popper = undefined;
            }
        },
        hasComments(timestamp: number): boolean {
            return this.getTimestamps.includes(timestamp);
        },
        formattedAudioTime(time: number) {
            let hours = Math.floor(time / 3600);
            let minutes = Math.floor((time - hours * 3600) / 60);
            let seconds = Math.floor(time - hours * 3600 - minutes * 60);

            return `${hours ? hours + ':' : ''}${
                minutes < 10 ? '0' + minutes : minutes
            }:${seconds < 10 ? '0' + seconds : seconds}`;
        },
        resetPlayerTime(): void {
            this.setCurrentTime(this.getSelectedTime);
            this.setPlaying(true);
            this.closePopper();
        },
        openComments(): void {
            this.$router.push(
                `/player/${this.getGroupContext.id}/comments/${this.getSelectedTime}`
            );
            this.closePopper();
        },
        ...mapMutations('player', [
            'setCurrentChapter',
            'setScrolledByUser',
            'setCurrentTime',
            'setPlaying'
        ]),
        ...mapMutations('comments', ['setSelectedTime']),
        ...mapActions('comments', ['loadTimestamps', 'showOverlay']),
    },
    computed: {
        ...mapGetters('player', [
            'getCurrentTime',
            'getCourseText',
            'getScrolledByUser',
            'getPercentageSeekedToCurrentTime',
            'isSeeking',
        ]),
        ...mapGetters('groups', ['getGroupContext']),
        ...mapGetters('comments', ['getTimestamps', 'getSelectedTime']),
    },
});
</script>

<style scoped>
#courseTextWrapper {
    /*
  - 0.90375rem + 0.045vw = font-size Einstellungen von Bootstrap, damit der Header nicht den Text überdeckt.
  - 50px, weil der PlayerHeader 50px Höhe hat.
  - 1 rem aufgrund von einem pt-3 auf dem Kapiteltitel.
  - 25px als pufferzone.
  margin-top: calc(0.90375rem + 0.045vw + 50px + 1rem + 25px);
  */
    /*
  -1.75rem als größte einstellbare Schriftgröße
  */
    margin-top: calc(1.75rem + 50px + 1rem + 25px);
    /*
  - 50px, weil die Playercontrols eine 50px Höhe haben.
  - 1 rem aufgrund von einem mb-2 und mt-2 auf dem controlswrapper Element.
  */
    margin-bottom: calc(50px + 1rem);
}

.course-tooltip {
    background-color: var(--main-text-color);
    border-radius: 8px;
    padding: 8px;
}

div[data-popper-arrow],
div[data-popper-arrow]::before {
    position: absolute;
    width: 8px;
    height: 8px;
    background: inherit;
}

div[data-popper-arrow] {
    visibility: hidden;
}

div[data-popper-arrow]::before {
    visibility: visible;
    content: '';
    transform: rotate(45deg);
}

.course-tooltip[data-popper-placement^='top'] > div[data-popper-arrow] {
    bottom: -4px;
}

.course-tooltip[data-popper-placement^='bottom'] > div[data-popper-arrow] {
    top: -4px;
}

.course-tooltip[data-popper-placement^='left'] > div[data-popper-arrow] {
    right: -4px;
}

.course-tooltip[data-popper-placement^='right'] > div[data-popper-arrow] {
    left: -4px;
}

.close,
.course-tooltip a,
.course-tooltip button {
    color: white;
    opacity: 0.8;
}

.close:not(:disabled):not(.disabled):hover,
.close:not(:disabled):not(.disabled):focus,
.course-tooltip a:hover,
.course-tooltip a:focus,
.course-tooltip button:hover,
.course-tooltip button:focus {
    opacity: 0.9;
}
</style>
