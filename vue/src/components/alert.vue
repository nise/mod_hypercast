<script lang="ts">
import { defineComponent } from 'vue';
import { mapMutations } from 'vuex';

export default defineComponent({
    props: {
        show: {
            type: Boolean,
            required: true,
        },
        type: {
            type: String,
            default: 'primary',
        },
        message: {
            type: String,
            default: '',
        },
    },
    methods: {
        ...mapMutations('notifications', [
            'closeAlert'
        ])
    }
});
</script>

<template>
    <div
        v-if="show"
        class="alert alert-dismissible"
        :class="'alert-' + type"
        role="alert"
    >
        {{ message }}
        <button
            type="button"
            class="close"
            aria-label="Close"
            @click="closeAlert"
        >
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
</template>
