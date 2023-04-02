import { Group } from '@/models';
import store from '@/store';

export function sortGroups(groups: Group[], userid: number): Group[] {
    groups.forEach((element) => {
        element.sortStatus = 4;
        // joinable  groups
        if (element.maxsize > element.members.length) element.sortStatus = 3;
        // joined groups
        if (isUserMemberOfGroup(userid, element)) element.sortStatus = 2;
        // created  groups
        if (element.usercreated.id === userid) element.sortStatus = 1;
    });

    return groups.sort((a: Group, b: Group) => {
        if (a.sortStatus === b.sortStatus) return a.id - b.id;
        return a.sortStatus > b.sortStatus ? 1 : -1;
    });
}
export const isUserMemberOfGroup = (userid: number, group: Group): boolean =>
    group.members.some((member) => member.id === userid);

export function hashCode(str: string): number {
    let hash = 0;
    for (let i = 0, len = str.length; i < len; i++) {
        hash = (31 * hash + str.charCodeAt(i)) << 0;
    }
    return Math.abs(hash);
}

export function generateInviteLink(group: Group) {
    const groupHash = hashCode('hypercast' + String(group.id));
    const baseURL = `${store.getters.getPluginBaseURL}/view.php/${store.getters.getCourseModuleID}`;
    return `${baseURL}/group/${group.id}?invite=${groupHash}`;
}