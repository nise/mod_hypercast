import { User } from './user';
export interface Group {
    id: number;
    courseid: number;
    name: string;
    description: string;
    usercreated: User;
    visible: boolean;
    maxsize: number;
    members: User[];
    sortStatus: number;
}

export interface MembersPlaytime {
    userid: number,
    timestamp: number
}