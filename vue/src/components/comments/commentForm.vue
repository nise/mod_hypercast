<script lang="ts">
import { defineComponent } from 'vue';
import { mapActions, mapGetters, mapMutations } from 'vuex';
import { Comment } from '@/models/comment';
import { Group } from '@/models';
import Communication from '@/scripts/communication';
import { saveComment } from '@/scripts/commentService';
import Chips from '@/components/comments/commentChips.vue';
import { useToast } from 'vue-toastification';

const toast = useToast()

export default defineComponent({
    components: {
        Chips,
    },
    data() {
        return {
            comment: '',
            groups: [] as Group[],
            selectedGroup: {} as Group,
            selectedCategory: null as string | null,
        };
    },
    methods: {
        async onSubmit() {
            const groupId = this.getGroupContext.id;
            const commentText = this.comment.replace(/  +/g, ' ').trim()

            if (!commentText){
                toast.error('Kommentartext darf nicht leer sein')
                return;
            }

            const comment = new Comment(
                this.comment,
                groupId,
                this.getSelectedTime,
                null,
                this.selectedCategory
            );
            await saveComment(comment);

            this.comment = '';
            this.selectedCategory = null;

            await this.loadComments(groupId);
        },
        ...mapMutations('notifications', ['showAlert']),
        ...mapActions('comments', ['loadComments']),
    },
    computed: {
        ...mapGetters(['getCourseModuleID', 'getUserID']),
        ...mapGetters('comments', ['getSelectedTime']),
        ...mapGetters('groups', ['getGroupContext']),
    },
    async mounted() {
        try {
            const cmid = this.getCourseModuleID;
            this.groups = await Communication.webservice('getAllGroupDetails', {
                cmid,
            });
        } catch (err) {
            this.showAlert([
                'danger',
                'Die Gruppen konnten nicht geladen werden. Bitte versuche es erneut.',
            ]);
        }
    },
});
</script>

<template>
    <div>
        <form id="comment-form" @submit.prevent="onSubmit">
            <div class="comment">
                <label
                    id="comment-form_comment_label"
                    for="comment-form_comment_input"
                >
                    Neue Diskussion
                </label>
                <Chips
                    :category="selectedCategory"
                    @selection="(value) => (selectedCategory = value)"
                />
                <textarea
                    id="comment-form_comment_input"
                    class="form-control"
                    v-model="comment"
                    placeholder="Neue Diskussion starten..."
                    required
                />
            </div>
            <button
                type="submit"
                id="comment-form_submit-button"
                class="btn btn-primary"
            >
                Anlegen
            </button>
        </form>
    </div>
</template>

<style scoped>
div {
    padding-bottom: 12px;
}

.comment {
    padding-top: 48px;
}
</style>
