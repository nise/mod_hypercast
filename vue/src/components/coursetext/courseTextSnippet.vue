<template>
    <span
        ref="snippet"
        v-if="type === 'sentence'"
        @click="clicked"
        :class="{
            isActive: isActive,
            isActiveProxy: isActiveProxy,
            hasComments: hasComments,
        }"
        >{{ value + ' ' }}
    </span>
    <span v-else-if="type === 'url'">
        (<a :href="value" target="_blank">{{ value }}</a
        >)
    </span>
    <figure v-else-if="type === 'image'" class="mt-3 mb-3 text-center">
        <img
            class="mb-2"
            :src="`${getPluginBaseURL}/assets/hyperaudio/images/ke6/${value}`"
        />
        <figcaption>{{ caption }}</figcaption>
    </figure>
    <h3 v-else class="mt-5 mb-3 chapter" @click="clicked">{{ value }}</h3>
</template>

<script lang="ts">
import { defineComponent } from 'vue';
import { isBetween } from '@/utils';
import { mapGetters } from 'vuex';

export default defineComponent({
    emits: ['click'],
    props: {
        type: {
            type: String,
            required: true,
        },
        caption: {
            type: String,
        },
        timeStart: {
            type: Number,
            required: true,
        },
        timeEnd: {
            type: Number,
            required: true,
        },
        hasComments: {
            type: Boolean,
        },
        value: {
            type: String,
            required: true,
        },
        currentTime: {
            type: Number,
            required: true,
        },
        noAutoScroll: {
            type: Boolean,
            required: true,
        },
        isSeeking: {
            type: Boolean,
            required: true,
        },
        seekingTime: {
            type: Number,
            required: true,
        },
        hasNoHoverOrPointer: {
            type: Boolean,
            required: false,
        },
    },
    methods: {
        clicked(event) {
            this.$emit('click', event, {
                timeStart: this.timeStart,
            });
        },
        scrollToActiveSnippet() {
            const spanElement = this.$refs.snippet as HTMLSpanElement;
            if (!spanElement) return;

            // Scroll to the active span element so that it is in the center of the window
            window.scrollTo({
                top:
                    spanElement.getBoundingClientRect().top -
                    document.body.getBoundingClientRect().top -
                    window.innerHeight / 2,
            });
        },
    },
    computed: {
        cursorStyle(): string {
            return this.hasNoHoverOrPointer ? 'auto' : 'pointer';
        },
        hoverColor(): string {
            if (this.hasNoHoverOrPointer) return 'rgba(0, 0, 0, 0.0)';
            return this.hasComments ? '#abe3b1' : 'rgba(0, 0, 0, 0.05)';
        },
        isActive(): boolean {
            return isBetween(
                this.currentTime,
                this.timeStart,
                this.timeEnd,
                true
            );
        },
        isActiveProxy(): boolean {
            return (
                isBetween(
                    this.seekingTime,
                    this.timeStart,
                    this.timeEnd,
                    true
                ) && this.isSeeking
            );
        },
        ...mapGetters('settings', ['getTextSizeInRem']),
        ...mapGetters('player', ['getScrolledByUser']),
        ...mapGetters(['getPluginBaseURL']),
    },
    watch: {
        getScrolledByUser(value: boolean) {
            if (!value && this.isActive) {
                this.scrollToActiveSnippet();
            }
        },
        isActive(newValue) {
            // Actual Autoscroll functionality.
            if (!newValue) return;

            // If this element became active, we should scroll to it unless the user has scrolled before.
            if (this.noAutoScroll) return;

            const chapterContainerY = document
                .querySelector('.chaptercontainer')
                ?.getBoundingClientRect().bottom;
            if (!chapterContainerY) return;

            this.scrollToActiveSnippet();
        },
        seekingTime(newValue) {
            const spanElement = this.$refs.snippet as HTMLSpanElement;
            if (!spanElement) return;

            if (this.isSeeking) {
                window.scrollTo({
                    top:
                        spanElement.getBoundingClientRect().top -
                        document.body.getBoundingClientRect().top -
                        window.innerHeight / 2,
                });
            }
        },
    },
});
</script>

<style scoped>
span {
    padding: 2px 0;
    cursor: v-bind(cursorStyle);
    text-decoration: underline;
    text-decoration-color: rgba(0, 0, 0, 0);
    font-weight: normal;
    font-size: v-bind(getTextSizeInRem);
    opacity: 0.75;
    background-color: rgba(0, 0, 0, 0);
    font-family: 'Source Serif Pro', -apple-system, BlinkMacSystemFont,
        'Segoe UI', Roboto, 'Helvetica Neue', Arial, 'Noto Sans', sans-serif,
        'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol',
        'Noto Color Emoji';
    transition: background-color 0.2s, text-decoration-color 0.2s;
}

img {
    max-width: 100%;
    max-height: 100%;
}

figcaption {
    font-size: 10pt;
    font-style: italic;
    color: rgba(0, 0, 0, 0.75);
}

span:hover,
span.selected {
    background-color: v-bind(hoverColor);
    transition: background-color 0.2s;
}

h3 {
    cursor: pointer;
    font-weight: bold;
}

.isActive {
    /* text-decoration: underline; */
    text-decoration-color: rgba(0, 0, 0, 0.65);
    /* color: #217DB0; */
    opacity: 1;
    transition: background-color 0.2s, text-decoration-color 0.2s, opacity 0.2s;
}

.isActiveProxy {
    text-decoration-color: rgba(0, 0, 0, 0.65);
    background-color: rgba(0, 0, 0, 0.05);
    transition: background-color 0.2s, text-decoration-color 0.2s, opacity 0.2s;
}

.hasComments {
    background-color: #b9f5bf;
    transition: background-color 0.2s, text-decoration-color 0.2s, opacity 0.2s;
}
</style>
