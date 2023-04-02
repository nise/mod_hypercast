<script lang="ts">
import $ from 'jquery';
import { defineComponent } from 'vue';
import Overlay from '@/components/utils/overlay.vue';
import CommentSection from './commentSection.vue';
import CommentForm from './commentForm.vue';
import { mapActions, mapGetters } from 'vuex';
import { CourseTextSnippet } from '@/models';
import CourseTextSnippets from '@/components/coursetext/courseTextSnippet.vue';
import { CommentThread, Reply } from '@/models/comment';

export default defineComponent({
    components: {
        Overlay,
        CommentSection,
        CommentForm,
        CourseTextSnippets,
    },
    methods: {
        getTextFocus(): CourseTextSnippet[] {
            const courseText = this.getCourseText as CourseTextSnippet[];

            const index = courseText.findIndex(
                (element) => element.time === this.getSelectedTime
            );
            const lowerBound = Math.max(index - 1, 0);
            const upperBound = Math.min(index + 1, courseText.length - 1);

            return courseText.slice(lowerBound, upperBound + 1); // upperBound is excluded
        },
        closeOverlay(): void {
            this.$router.push(`/player/${this.getGroupContext.id}`);
            this.hideOverlay();
        },
        ...mapActions('comments', ['hideOverlay', 'deleteComment']),
    },
    computed: {
        ...mapGetters('comments', [
            'getShowOverlay',
            'getSelectedTime',
            'getCommentToDelete',
        ]),
        ...mapGetters('player', ['getCourseText']),
        ...mapGetters('groups', ['getGroupContext']),
    },
    watch: {
        getCommentToDelete(comment: CommentThread | Reply | undefined): void {
            if (comment) {
                ($('#deleteCommentModal') as any).modal('show');
            }
        },
    },
});
</script>

<template>
    <Overlay :show="getShowOverlay" @close="closeOverlay">
        <div class="col snippet">
            <CourseTextSnippets
                v-for="(textBlock, index) in getTextFocus()"
                :key="index"
                :type="textBlock.type"
                :value="textBlock.value"
                :timeStart="textBlock.time"
                :timeEnd="textBlock.timeEnd"
                :isSeeking="false"
                :seeking-time="100"
                :noAutoScroll="true"
                :currentTime="getSelectedTime + 1"
                @click.prevent="false"
                :hasNoHoverOrPointer="true"
            />
        </div>

        <CommentSection class="col" style="margin-top: 30px" />
        <CommentForm class="col" />
    </Overlay>
    <div
        class="modal fade"
        id="deleteCommentModal"
        tabindex="-1"
        aria-labelledby="deleteModalLabel"
    >
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">
                        Kommentar löschen
                    </h5>
                    <button
                        type="button"
                        class="close"
                        data-dismiss="modal"
                        aria-label="Close"
                    >
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    Sind Sie sicher, dass sie den Kommentar löschen möchten?
                </div>
                <div class="modal-footer">
                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-dismiss="modal"
                    >
                        Abbrechen
                    </button>
                    <button
                        type="button"
                        class="btn btn-primary"
                        data-dismiss="modal"
                        @click="deleteComment(getCommentToDelete)"
                    >
                        Löschen
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.snippet {
    border: 1px solid var(--border-color-dark);
    border-radius: 5px;
    box-shadow: 0px 1px 5px rgba(0, 0, 0, 0.25);
    margin-bottom: 20px;
    padding: 25px;
    width: 80%;
    margin: auto;
}
</style>
