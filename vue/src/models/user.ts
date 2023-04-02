export interface User {
    id: number;
    firstname: string;
    lastname: string;
    username: string;
    profileimageurl: string;
}

export interface UserProgess {
    user: User;
    timestamp: number;
}