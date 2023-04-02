<template>
    <Playeroverlay />
    <div class="row">
        <div class="col-10">
            <CourseText />
        </div>
    </div>
    <CommentOverlay />
</template>

<script lang="ts">
import { defineComponent } from 'vue';
import { mapActions, mapGetters } from 'vuex';
import Playeroverlay from '@/components/playeroverlay.vue';
import CourseText from '@/components/coursetext/courseText.vue';
import CommentOverlay from '@/components/comments/commentOverlay.vue';
import store from '@/store';

export default defineComponent({
    components: {
        Playeroverlay,
        CourseText,
        CommentOverlay,
    },
    async beforeRouteLeave(to, from, next) {
        if(this.isLiveSessionJoined)
            this.leaveLiveSession();
        await this.closeWebSocket();
        next();
    },
    // 'this' will be not available during the beforeRouteEnter, because the component has not been rendered yet.
    // Therefore we have to use the store directly.
    async beforeRouteEnter(to, from, next) {
        await store.dispatch('communication/initializeWebSocket', to.params.id);
        next();
    },
    computed: {
        ...mapGetters('player', ['isLiveSessionJoined']),
    },
    methods: {
        ...mapActions('communication', ['closeWebSocket']),
        ...mapActions('player', ['leaveLiveSession']),
    },
});
</script>
