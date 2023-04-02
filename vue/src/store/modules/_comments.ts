import { Comment, CommentThread, Reply } from '@/models/comment';
import {
    loadComments,
    loadTimestamps,
    saveComment,
    deleteComment,
} from '@/scripts/commentService';

export default {
    namespaced: true,
    state: () => ({
        comments: [] as CommentThread[],
        timestamps: [] as number[],
        selectedTime: -1,
        showOverlay: false,
        commentToDelete: undefined as CommentThread | Reply | undefined,
    }),
    actions: {
        async loadTimestamps({ commit }, groupId: number) {
            commit('setTimestamps', await loadTimestamps(groupId));
        },
        async loadComments({ commit, state, dispatch }, groupId: number) {
            commit(
                'setComments',
                await loadComments(groupId, state.selectedTime)
            );
            dispatch('loadTimestamps', groupId);
        },
        async saveComment({ dispatch }, comment: Comment) {
            await saveComment(comment);
            dispatch('loadComments', comment.groupid);
        },
        async deleteComment(
            { dispatch, commit },
            comment: CommentThread | Reply
        ) {
            await deleteComment(comment.id);
            commit('setCommentToDelete', undefined);
            dispatch('loadComments', comment.groupid);
        },
        setCurrentThread(context, currentThread: CommentThread): void {
            context.commit('setCurrentThread', currentThread);
        },
        clearCurrentThread(context): void {
            context.commit('setCurrentThread', undefined);
        },
        showOverlay({ commit }): void {
            commit('setShowOverlay', true);
        },
        hideOverlay({ commit }): void {
            commit('setShowOverlay', false);
        },
    },
    mutations: {
        setComments(state, comments) {
            state.comments = comments;
        },
        setSelectedTime(state, time) {
            state.selectedTime = time;
        },
        setTimestamps(state, timestamps) {
            state.timestamps = timestamps;
        },
        setShowOverlay(state, value) {
            state.showOverlay = value;
        },
        setCommentToDelete(state, value) {
            state.commentToDelete = value;
        },
    },
    getters: {
        getComments: function (state): CommentThread[] {
            return state.comments;
        },
        getSelectedTime: function (state): number {
            return state.selectedTime;
        },
        getTimestamps: function (state): number[] {
            return state.timestamps;
        },
        getShowOverlay: function (state): boolean {
            return state.showOverlay;
        },
        getCommentToDelete: function (
            state
        ): CommentThread | Reply | undefined {
            return state.commentToDelete;
        },
    },
};
