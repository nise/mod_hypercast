<script lang="ts">
import { defineComponent, PropType } from 'vue';
import { CommentThread } from '@/models/comment';
import { format, formatDistance, Interval, intervalToDuration } from 'date-fns';
import { updateComment } from '@/scripts/commentService';
import { mapActions, mapGetters, mapMutations } from 'vuex';
import { de } from 'date-fns/locale';
import { CategoryMap } from '@/models/category';
import CommentThreadRenderer from './commentThreadRenderer.vue';
import Chips from '@/components/comments/commentChips.vue';

export default defineComponent({
    components: {
        Chips,
        CommentThreadRenderer,
    },
    props: {
        comment: {
            type: Object as PropType<CommentThread>,
            required: true,
        },
    },
    data() {
        return {
            categoryMap: CategoryMap,
            editMode: false,
            commentEdit: '',
            showReplies: false,
            selectedCategory: null as string | null,
        };
    },
    computed: {
        ...mapGetters(['getUserID']),
    },
    methods: {
        async saveComment() {
            await updateComment(
                this.comment.id,
                this.commentEdit,
                this.selectedCategory
            );

            this.editMode = false;
            this.commentEdit = '';
            await this.loadComments(this.comment.groupid);
        },
        editComment() {
            this.editMode = true;
            this.commentEdit = this.comment.comment;
        },
        cancelEdit() {
            this.editMode = false;
            this.commentEdit = '';
        },
        commentOwned() {
            return this.comment.user.id === this.getUserID;
        },
        convertUnixTime(value: number): string {
            return format(new Date(value * 1000), 'dd.MM.yyy HH:mm');
        },
        getDisplayedTimeString(): string {
            if (!this.comment.timemodified) {
                return this.toReadableTime(this.comment.timecreated);
            } else if (this.comment.deleted) {
                return `gelöscht ${this.toReadableTime(
                    this.comment.timemodified
                )}`;
            } else {
                return `bearbeitet ${this.toReadableTime(
                    this.comment.timemodified
                )}`;
            }
        },
        toReadableTime(value: number): string {
            const now = new Date();
            const interval: Interval = { start: value * 1000, end: now };
            if (intervalToDuration(interval).days) {
                return `am ${this.convertUnixTime(value)}`;
            } else {
                const formatted = formatDistance(interval.start, interval.end, {
                    locale: de,
                });
                return `vor ${formatted}`;
            }
        },
        getProfileImageUrl(): string {
            return this.comment.deleted
                ? `${M.cfg.wwwroot}/pix/u/f2.png`
                : this.comment.user.profileimageurl;
        },
        getFullname(): string {
            return `${this.comment.user.firstname} ${this.comment.user.lastname}`;
        },
        getRepliesLabel(): string {
            return `${this.comment.replies.length} ${
                this.comment.replies.length === 1 ? 'Antwort' : 'Antworten'
            }`;
        },
        toggleReplies() {
            this.showReplies = !this.showReplies;
        },
        ...mapActions('comments', [
            'loadComments',
            'deleteComment',
            'setCurrentThread',
        ]),
        ...mapMutations('comments', ['setCommentToDelete']),
    },
});
</script>

<template>
    <div class="comment">
        <div class="header">
            <img
                :key="comment.id"
                :src="getProfileImageUrl()"
                width="35"
                height="35"
                :alt="comment.deleted ? undefined : getFullname()"
                :title="comment.deleted ? undefined : getFullname()"
            />
            <div class="col d-flex flex-column justify-content-center">
                <b v-if="!comment.deleted" class="mr-1">{{ getFullname() }}</b>
                <i>{{ getDisplayedTimeString() }}</i>
            </div>
            <div
                class="dropdown col-auto p-0"
                v-if="commentOwned() && !comment.deleted"
            >
                <button class="btn btn-sm" type="button" data-toggle="dropdown">
                    <font-awesome-icon icon="fa-solid fa-ellipsis-vertical" />
                </button>
                <div class="dropdown-menu dropdown-menu-right">
                    <button
                        type="button"
                        class="btn dropdown-item"
                        @click="editComment()"
                    >
                        Bearbeiten
                    </button>
                    <button
                        type="button"
                        class="btn dropdown-item"
                        @click="setCommentToDelete(comment)"
                    >
                        Löschen
                    </button>
                </div>
            </div>
        </div>
        <div class="content" v-if="comment.deleted">
            <i>Dieser Kommentar wurde vom Nutzer gelöscht</i>
        </div>
        <div class="content" v-else-if="editMode">
            <Chips
                :category="comment.category"
                @selection="(value) => (selectedCategory = value)"
            />
            <div>
                <textarea v-model="commentEdit" />
            </div>
            <div>
                <button
                    type="button"
                    class="btn btn-outline-secondary mr-2"
                    @click="cancelEdit()"
                >
                    Abbrechen
                </button>
                <button
                    type="button"
                    class="btn btn-primary"
                    @click="saveComment()"
                >
                    Speichern
                </button>
            </div>
        </div>
        <div class="content" v-else>
            {{ comment.comment }}
            <div>
                <span
                    class="badge"
                    :class="categoryMap.get(comment.category)"
                    >{{ comment.category }}</span
                >
            </div>
            <div class="separator" @click="toggleReplies()">
                <font-awesome-icon class="icon" icon="fa-regular fa-comment" />
                {{ getRepliesLabel() }}
            </div>
            <CommentThreadRenderer v-if="showReplies" :commentLoc="comment" />
        </div>
    </div>
</template>

<style scoped>
.header img {
    border-radius: 50%;
    flex: 0 0 35px;
}

textarea {
    width: 100%;
}

.comment {
    border: 1px solid var(--border-color-dark);
    border-radius: 5px;
    box-shadow: 0px 1px 5px rgba(0, 0, 0, 0.25);
    margin-bottom: 20px;
    padding: 15px;
}

.badge {
    border-radius: 5px;
    padding: 0.6em;
}

.header {
    display: flex;
    margin-bottom: 10px;
}

.header i {
    font-size: 0.85em;
}

.separator {
    border-top: 1px solid var(--border-color-light);
    cursor: pointer;
    margin-top: 10px;
    padding-top: 10px;
}

.footer .icon {
    flex: 0 0 35px;
}
</style>
