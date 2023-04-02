import { Comment, CommentThread } from '@/models/comment';
import Communication from '@/scripts/communication';
import store from '@/store/index';
import { createCommentThread } from '@/scripts/createCommentThread';
import { useToast } from 'vue-toastification';

const toast = useToast();

export async function loadTimestamps(groupId: number): Promise<number[]> {
    return new Promise((resolve, reject) => {
        const cmid = store.getters.getCourseModuleID;
        Communication.webservice('getCommentsTimestamps', {
            cmid,
            groupid: groupId,
        })
            .then((response) => resolve(response.timestamps))
            .catch((error) => {
                toast.error(
                    'Die Kommentarmetadaten konnten nicht geladen werden. Bitte versuche es erneut.'
                );
                reject(error);
            });
    });
}

export async function loadComments(
    groupId: number,
    selectedTime: number
): Promise<CommentThread[]> {
    return new Promise((resolve, reject) => {
        const cmid = store.getters.getCourseModuleID;
        Communication.webservice('getComments', {
            cmid,
            groupid: groupId,
            timestamp: selectedTime,
        })
            .then((response) => resolve(createCommentThread(response)))
            .catch((error) => {
                toast.error(
                    'Die Kommentarübersicht konnte nicht geladen werden. Bitte versuche es erneut.'
                );
                reject(error);
            });
    });
}

export async function saveComment(comment: Comment): Promise<boolean> {
    const cmid = store.getters.getCourseModuleID;
    return Communication.webservice('createComment', {
        cmid,
        comment: comment.comment,
        groupid: comment.groupid,
        timestamp: comment.timestamp,
        referenceid: comment.referenceid,
        category: comment.category,
    });
}

export async function updateComment(
    id: number,
    comment: string,
    category: string | null
): Promise<boolean> {
    return new Promise((resolve, reject) => {
        Communication.webservice('updateComment', {
            id,
            comment,
            category,
        })
            .then((response) => {
                toast.success('Der Kommentar wurde gespeichert');
                resolve(response);
            })
            .catch((error) => {
                toast.error(
                    'Der Kommentar konnte nicht gespeichert werden. Bitte versuche es erneut.'
                );
                reject(error);
            });
    });
}

export async function deleteComment(id: number): Promise<boolean> {
    return new Promise((resolve, reject) => {
        Communication.webservice('deleteComment', {
            id,
        })
            .then((response) => {
                toast.success('Der Kommentar wurde gelöscht');
                resolve(response);
            })
            .catch((error) => {
                toast.error(
                    'Der Kommentar konnte nicht gelöscht werden. Bitte versuche es erneut.'
                );
                reject(error);
            });
    });
}
