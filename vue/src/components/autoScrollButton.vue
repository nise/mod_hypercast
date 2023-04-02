<script lang="ts">
import {defineComponent} from "vue";
import {mapGetters, mapMutations} from "vuex";

export default defineComponent({
    name: 'AutoScrollButton',
    methods: {
        ...mapMutations('player', ['setScrolledByUser'])
    },
    computed: {
        ...mapGetters('player', ['getScrolledByUser']),
    }
})
</script>

<template>
    <button type="button" class="btn-primary" @click="setScrolledByUser(false)">
        <font-awesome-icon icon="fa-solid fa-arrows-down-to-line" />
        zur aktuellen Textstelle
    </button>
</template>