<script lang="ts">
import { defineComponent, PropType } from 'vue';
import { CategoryMap } from '@/models/category';

export default defineComponent({
    data() {
        return {
            chipLabels: CategoryMap,
            selected: null as string | null,
        };
    },
    props: {
        category: {
            type: String,
            required: false,
        },
    },
    watch: {
        // Reset when comment is saved
        category() {
            if (this.category) {
                this.selected = this.category;
            } else {
                this.selected = null;
            }
        },
    },
    methods: {
        setSelected(selection: string | null) {
            if (selection === this.selected) {
                this.selected = null;
            } else {
                this.selected = selection;
            }
            this.$emit('selection', this.selected);
        },
        isNotSelected(chip: string) {
            //all gray on showup
            if (!this.selected) return true;
            return chip !== this.selected;
        },
    },

    mounted: function (): void {
        if (this.category != undefined) this.setSelected(this.category);
    },

    emits: ['selection'],
});
</script>

<template>
    <div>
        <span
            :class="{ 'not-selected': isNotSelected(label) }"
            v-for="label in chipLabels.keys()"
            :key="label.toString()"
        >
            <div
                class="badge"
                :class="chipLabels.get(label)"
                @click="setSelected(label)"
            >
                <font-awesome-icon
                    icon="fa-solid fa-check"
                    style="padding-right: 5px"
                    v-if="label === selected"
                />
                <span class="not-selectable">{{ label }}</span>
            </div>
        </span>
    </div>
</template>

<style scoped>
.badge {
    font-size: 14px;
    margin: 2px;
    padding: 0.6em;
    border-radius: 5px;
}
.badge:hover {
    cursor: pointer;
}

.not-selectable {
    -webkit-touch-callout: none;
    -webkit-user-select: none;
    -khtml-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
}

.not-selected {
    opacity: 0.5;
}
</style>
