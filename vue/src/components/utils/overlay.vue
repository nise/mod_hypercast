<script lang="ts">
import { reactOnDrawerChange } from '@/scripts/drawer';
import { defineComponent } from 'vue';

export default defineComponent({
    props: {
        show: Boolean,
        showHeader: {
            type: Boolean,
            default: true,
        },
    },
    data() {
        return {
            removeClickEventListener: undefined as (() => void) | undefined,
        };
    },
    emits: ['close'],
    watch: {
        show(shouldShow: boolean) {
            document.body.style.overflow = shouldShow ? 'hidden' : 'visible';
        },
    },
    methods: {
        enter(): void {
            const wrapper = this.$refs.wrapper as HTMLDivElement;
            this.removeClickEventListener = reactOnDrawerChange(wrapper);
        },
        leave(): void {
            if (this.removeClickEventListener) {
                this.removeClickEventListener();
            }
        },
    },
    mounted() {
        this.enter();
        if (this.show) {
            document.body.style.overflow = 'hidden';
        }
    },
});
</script>

<template>
    <Transition name="slide" @enter="enter" @leave="leave">
        <div v-if="show" class="wrapper" ref="wrapper">
            <div class="col-12 col-md-10 col-lg-7 col-xl-6 m-auto">
                <div v-if="showHeader" class="header">
                    <div class="col">
                        <slot name="header"></slot>
                    </div>
                    <button
                        type="button"
                        class="btn close-overlay"
                        @click="$emit('close')"
                    >
                        <font-awesome-icon icon="fa-solid fa-xmark" />
                    </button>
                </div>
                <div class="content">
                    <slot></slot>
                </div>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.slide-leave-active,
.slide-enter-active {
    transition: 0.5s ease-in-out;
}
.slide-enter-from,
.slide-leave-to {
    transform: translateY(100%);
}

.plop-leave-active,
.plop-enter-active {
    transition: 0.2s ease-out;
}

.plop-enter-from,
.plop-leave-to {
    transform: scale(0.95);
    opacity: 0;
}

.plop-leave-from,
.plop-enter-to {
    transform: scale(1);
    opacity: 1;
}
.wrapper {
    background-color: white;
    top: 50px;
    left: 0;
    position: fixed;
    height: calc(100vh - 50px);
    overflow: auto;
    width: 100vw;
    z-index: 1;
}

.wrapper.drawer-open {
    padding-left: 285px;
}

/* reset the padding left for phone sized screens  */
@media (max-width: 767.98px) {
    .wrpper.drawer-open {
        padding-left: 15px;
    }
}

.header {
    display: flex;
    pointer-events: auto;
}

.header .close-overlay {
    color: var(--main-text-color);
    flex: 0 0 auto;
    font-size: 20pt;
    transition: color 0.2s;
}

.header .close:hover {
    color: var(--link-hover-color);
}
</style>
