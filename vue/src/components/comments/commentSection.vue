<script lang="ts">
import { defineComponent } from 'vue';
import { CommentThread } from '@/models/comment';
import CommentThreadRenderer from '@/components/comments/commentThreadRenderer.vue';
import CommentRenderer from '@/components/comments/commentRenderer.vue';
import { mapActions, mapGetters } from 'vuex';

export default defineComponent({
    components: {
        CommentThreadRenderer,
        CommentRenderer,
    },
    computed: {
        sortedComments() {
            const comments: CommentThread[] = this.getComments;
            return comments.sort((a: CommentThread, b: CommentThread) => {
                if (a.timecreated <= b.timecreated) return 1;
                return -1;
            });
        },
        ...mapGetters('comments', ['getComments', 'getSelectedTime']),
        ...mapGetters('groups', ['getGroupContext']),
    },
    methods: {
        getDiscussionLabel(): string {
            return `${this.sortedComments.length} ${
                this.sortedComments.length === 1 ? 'Diskussion' : 'Diskussionen'
            }`;
        },
        ...mapActions('comments', ['loadComments']),
    },
    async beforeMount() {
        await this.loadComments(this.getGroupContext.id);
    },
});
</script>

<template>
    <div>
        <h1>{{ getDiscussionLabel() }}</h1>
        <CommentRenderer
            v-for="comment of sortedComments"
            :key="comment.id"
            :comment="comment"
        />
    </div>
</template>

<style scoped></style>
