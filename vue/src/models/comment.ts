import {User} from "@/models/user";

export interface CommentResponse {
    id: number;
    comment: string;
    groupid: number;
    user: User;
    timecreated: number;
    timemodified: number;
    timestamp: number;
    deleted: boolean;
    referenceid: number | null;
    category: string | null;
}

export interface CommentInformation {
    comment: string;
    user: User;
    timecreated: number;
    timemodified: number | null;
    timestamp: number;
}

export abstract class AbstractComment {
    id: number;
    user: User;
    comment: string;
    timecreated: number;
    timemodified: number | null;
    timestamp: number;
    deleted: boolean;

    protected constructor(
        id: number,
        user: User,
        comment: string,
        timecreated: number,
        timemodified: number | null,
        timestamp: number,
        deleted: boolean,
    ) {
        this.id = id;
        this.comment = comment;
        this.user = user;
        this.timecreated = timecreated;
        if (timecreated === timemodified) {
            this.timemodified = null
        } else {
            this.timemodified = timemodified
        }
        this.timestamp = timestamp;
        this.deleted = deleted;
    }

    abstract getCommentInformation(): CommentInformation;
}

export class CommentThread extends AbstractComment {
    groupid: number;
    replies: Reply[];
    category: string | null;

    constructor(
        id: number,
        comment: string,
        groupid: number,
        user: User,
        timecreated: number,
        timemodified: number,
        timestamp: number,
        deleted: boolean,
        replies: Reply[],
        category: string| null) {
        super(id, user, comment, timecreated, timemodified, timestamp, deleted);
        this.groupid = groupid;
        this.replies = replies;
        this.category = category;
    }

    getCommentInformation(): CommentInformation {
        return {
            comment: this.comment,
            user: this.user,
            timecreated: this.timecreated,
            timemodified: this.timemodified,
            timestamp: this.timestamp
        }
    }
}

export class Reply extends AbstractComment {
    groupid: number;

    constructor(
        id: number,
        comment: string,
        groupid: number,
        user: User,
        timecreated: number,
        timemodified: number,
        timestamp: number,
        deleted: boolean) {
        super(id, user, comment, timecreated, timemodified, timestamp, deleted);
        this.groupid = groupid;
    }

    getCommentInformation(): CommentInformation {
        return {
            comment: this.comment,
            user: this.user,
            timecreated: this.timecreated,
            timemodified: this.timemodified,
            timestamp: this.timestamp
        }
    }
}

export class Comment {
    comment: string;
    groupid: number;
    timestamp: number;
    referenceid: number | null;
    category: string | null;


    constructor(comment: string,
                groupid: number,
                timestamp: number,
                referenceid: number | null,
                category: string | null) {
        this.comment = comment;
        this.groupid = groupid;
        this.timestamp = timestamp;
        this.referenceid = referenceid;
        this.category = category;
    }
}
