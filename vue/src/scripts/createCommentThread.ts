import {CommentResponse, CommentThread, Reply} from "@/models/comment";

export function createCommentThread(commentListResponse: CommentResponse[]): CommentThread[] {

    const commentThreads = commentListResponse
        .filter((commentResponse) => commentResponse.referenceid === null)
        .map((commentResponse) => new CommentThread(
                commentResponse.id,
                commentResponse.comment,
                commentResponse.groupid,
                commentResponse.user,
                commentResponse.timecreated,
                commentResponse.timemodified,
                commentResponse.timestamp,
                commentResponse.deleted,
                [],
                commentResponse.category
            )
        )

    commentListResponse
        .filter((commentResponse) => commentResponse.referenceid != null)
        .forEach((commentResponse) => {
            const reply = new Reply(
                commentResponse.id,
                commentResponse.comment,
                commentResponse.groupid,
                commentResponse.user,
                commentResponse.timecreated,
                commentResponse.timemodified,
                commentResponse.timestamp,
                commentResponse.deleted
            )
            commentThreads
                .find((commentThread) => commentResponse.referenceid === commentThread.id)!
                .replies.push(reply)
        })

    return commentThreads;
}