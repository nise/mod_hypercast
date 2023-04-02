<script lang="ts">
import { defineComponent, PropType } from 'vue';
import { mapActions, mapGetters, mapMutations } from 'vuex';
import {
    AbstractComment,
    CommentThread,
    Comment,
    Reply,
} from '@/models/comment';
import { updateComment } from '@/scripts/commentService';
import { format, formatDistance, Interval, intervalToDuration } from 'date-fns';
import { de } from 'date-fns/locale';
import { CategoryMap } from '@/models/category';

export default defineComponent({
    data: function () {
        return {
            categoryMap: CategoryMap,
            reply: '',
            selectedReply: undefined as Reply | undefined,
            oldReply: '',
            mode: 'view',
        };
    },
    props: {
        commentLoc: {
            type: Object as PropType<CommentThread>,
            required: true,
        },
    },

    methods: {
        async sendReply() {
            const comment = new Comment(
                this.reply,
                this.commentLoc.groupid,
                this.commentLoc.timestamp,
                this.commentLoc?.id,
                null
            );
            await this.saveComment(comment);
            this.reply = '';
        },
        async updateReply() {
            if (!this.selectedReply) return;

            await updateComment(
                this.selectedReply.id,
                this.selectedReply.comment,
                null
            );
            this.mode = 'view';
            await this.loadComments(this.selectedReply.groupid);
            this.selectedReply = undefined;
        },
        sortReplies(list: Reply[]): Reply[] {
            return list.sort((a: Reply, b: Reply) => {
                if (a.timecreated >= b.timecreated) return 1;
                return -1;
            });
        },
        convertUnixTime(value: number): string {
            return format(new Date(value * 1000), 'dd.MM.yyy HH:mm');
        },
        getDisplayedTimeString(comment: AbstractComment): string {
            if (!comment.timemodified) {
                return this.toReadableTime(comment.timecreated);
            } else if (comment.deleted) {
                return `gelöscht ${this.toReadableTime(comment.timemodified)}`;
            } else {
                return `bearbeitet ${this.toReadableTime(
                    comment.timemodified
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
        getProfileImageUrl(comment: AbstractComment): string {
            return comment.deleted
                ? `${M.cfg.wwwroot}/pix/u/f2.png`
                : comment.user.profileimageurl;
        },
        getFullname(comment: AbstractComment): string {
            return `${comment.user.firstname} ${comment.user.lastname}`;
        },
        replyOwned(reply: Reply) {
            return reply.user.id === this.getUserID;
        },
        isEditable(reply: Reply) {
            return (
                this.mode === 'edit' &&
                this.selectedReply &&
                this.selectedReply.id === reply.id
            );
        },
        editReply(reply: Reply) {
            this.mode = 'edit';
            this.selectedReply = reply;
            this.oldReply = reply.comment;
        },
        cancelEdit() {
            if (!this.selectedReply) return;
            this.mode = 'view';
            // reset old reply comment
            this.selectedReply.comment = this.oldReply;
            this.selectedReply = undefined;
        },
        ...mapActions('comments', [
            'loadComments',
            'saveComment',
            'deleteComment',
            'clearCurrentThread',
        ]),
        ...mapMutations('comments', ['setCommentToDelete']),
    },
    computed: {
        sortedReplies(): Reply[] {
            return this.sortReplies(this.commentLoc.replies);
        },
        ...mapGetters('comments', { thread: 'getCurrentThread' }),
        ...mapGetters(['getUserID']),
    },
});
</script>

<template>
    <div class="replies">
        <div class="reply" v-for="reply of sortedReplies" :key="reply.id">
            <div class="header">
                <img
                    :key="reply.id"
                    :src="getProfileImageUrl(reply)"
                    width="35"
                    height="35"
                    :alt="reply.deleted ? undefined : getFullname(reply)"
                    :title="reply.deleted ? undefined : getFullname(reply)"
                />
                <div class="col d-flex flex-column justify-content-center">
                    <b v-if="!reply.deleted" class="mr-1">{{
                        getFullname(reply)
                    }}</b>
                    <i>{{ getDisplayedTimeString(reply) }}</i>
                </div>
                <div
                    class="dropdown col-auto p-0"
                    v-if="replyOwned(reply) && !reply.deleted"
                >
                    <button
                        class="btn btn-sm"
                        type="button"
                        data-toggle="dropdown"
                    >
                        <font-awesome-icon
                            icon="fa-solid fa-ellipsis-vertical"
                        />
                    </button>
                    <div class="dropdown-menu dropdown-menu-right">
                        <button
                            type="button"
                            class="btn dropdown-item"
                            @click="editReply(reply)"
                        >
                            Bearbeiten
                        </button>
                        <button
                            type="button"
                            class="btn dropdown-item"
                            @click="setCommentToDelete(reply)"
                        >
                            Löschen
                        </button>
                    </div>
                </div>
            </div>
            <div class="content" v-if="reply.deleted">
                <i>Dieser Kommentar wurde vom Nutzer gelöscht</i>
            </div>
            <div class="content" v-else-if="isEditable(reply)">
                <div>
                    <textarea v-model="reply.comment" />
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
                        @click="updateReply()"
                    >
                        Speichern
                    </button>
                </div>
            </div>
            <div class="content" v-else>{{ reply.comment }}</div>
        </div>

        <div class="new-reply mt-3">
            <div>
                <textarea
                    v-model="reply"
                    placeholder="Antwort hinzufügen&hellip;"
                    required
                />
            </div>
            <div>
                <button
                    class="btn btn-primary"
                    @click="sendReply()"
                    :disabled="reply.trim().length === 0"
                >
                    Antworten
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.badge {
    border-radius: 5px;
    padding: 0.6em;
}

.header {
    display: flex;
    margin-bottom: 10px;
    margin-top: 10px;
}

.header img {
    border-radius: 50%;
    flex: 0 0 35px;
}

.header i {
    font-size: 0.85em;
}

.content {
    border-bottom: 1px solid var(--border-color-dark);
    box-shadow: 0px 5px 5px -5px rgba(0, 0, 0, 0.25);
    padding: 15px;
}

.replies {
    border-top: 1px solid var(--border-color-light);
    padding: 10px 25px;
    margin-top: 10px;
}

.reply .content {
    border-bottom: 1px solid var(--border-color-light);
    box-shadow: none;
    padding: 10px;
}

.reply:last-of-type .content {
    border: none;
}

.new-reply {
    display: flex;
    flex-direction: column;
}

textarea {
    width: 100%;
}
</style>
