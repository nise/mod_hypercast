import { Group } from '@/models';
import { sortGroups, isUserMemberOfGroup } from '@/utils';
import Communication from '@/scripts/communication';

import { useToast } from 'vue-toastification';

const toast = useToast();

export default {
    namespaced: true,
    state: () => ({
        groupList: [] as Group[],
        groupContext: {} as Group,
        groupPreferences: {
            sizePreset: 20,
            maxGroupSize: 20,
            nameFieldLength: 30,
            isGroupSizeMandatory: true,
        },
        editExistingGroup: false,
        showOnboarding: false,
    }),
    mutations: {
        setEditExistingGroup(state, value) {
            state.editExistingGroup = value;
        },
        setGroupContext(state, value) {
            state.groupContext = value;
        },
        setGroupList(state, value) {
            state.groupList = value;
        },
        setGroupMembers(state, { groupIndex, newMembers }) {
            state.groupList[groupIndex].members = newMembers;
        },
        removeUserFromGroup(state, { userid, group }) {
            const groupIndex = state.groupList
                .map((GroupListitem) => GroupListitem.id)
                .indexOf(group.id);
            const userIndex = state.groupList[groupIndex].members
                .map((member) => member.id)
                .indexOf(userid);
            state.groupList[groupIndex].members.splice(userIndex, 1);
            // Sort Groups again
            sortGroups(state.groupList, userid);
        },
        setShowOnboarding(state, value) {
            state.showOnboarding = value;
        },
    },
    actions: {
        editGroup({ commit }, group: Group) {
            commit('setEditExistingGroup', true);
            commit('setGroupContext', group);
        },

        async updateGroupListFromDB({ commit, rootGetters }) {
            try {
                const cmid = rootGetters.getCourseModuleID;
                const unsortedGroupList = await Communication.webservice(
                    'getAllGroupDetails',
                    { cmid }
                );
                commit(
                    'setGroupList',
                    sortGroups(unsortedGroupList, rootGetters.getUserID)
                );
            } catch (e) {
                toast.error('Die Gruppenübersicht konnte nicht geladen werden');
            }
        },
        async leaveGroup(context, group) {
            try {
                await Communication.webservice('leaveGroup', {
                    groupid: group.id,
                });
                const userid = context.rootGetters.getUserID;
                toast.success(`Du hast die Gruppe "${group.name}" verlassen.`);
                context.commit('removeUserFromGroup', { userid, group });
            } catch (err) {
                toast.error(
                    'Verlassen der Gruppe fehlgeschlagen. Bitte versuche es erneut.'
                );
            }
        },

        async joinGroup(context, group) {
            if (isUserMemberOfGroup(context.rootGetters.getUserID, group)) {
                toast.info(
                    `Du bist bereits Mitglied der Gruppe "${group.name}"`
                );
                return;
            }
            try {
                await Communication.webservice('joinGroup', {
                    groupid: group.id,
                });
                toast.success(`Willkommen in der Gruppe "${group.name}"`);
                context.dispatch('updateGroupListFromDB');
            } catch (err) {
                toast.error(
                    'Beitritt zur Gruppe fehlgeschlagen. Vielleicht ist die Gruppe schon voll.'
                );
            }
        },
        async deleteGroup({ commit, state }, group) {
            try {
                await Communication.webservice('deleteGroup', {
                    id: group.id,
                });
                const newGroupList = state.groupList.filter(
                    (groupListItem) => group.id !== groupListItem.id
                );
                commit('setGroupList', newGroupList);
                toast.success(`Die Gruppe "${group.name}" wurde gelöscht`);
            } catch (err) {
                toast.error('Die Gruppe konnte nicht gelöscht werden');
            }
        },
        async setGroupContextFromDB({ commit }, groupid) {
            try {
                const group = await Communication.webservice(
                    'getGroupDetails',
                    { id: groupid }
                );
                commit('setGroupContext', group);
                return group;
            } catch (err) {
                toast.error('Die Gruppe wurde nicht gefunden');
            }
        },
        showOnboarding({ commit }) {
            commit('setShowOnboarding', true);
        },
        hideOnboarding({ commit }) {
            commit('setShowOnboarding', false);
        },
    },
    getters: {
        getGroupPreferences(state) {
            return state.groupPreferences;
        },
        getGroupSizePreset(state) {
            return state.groupPreferences.sizePreset;
        },
        getMaxGroupSize(state) {
            return state.groupPreferences.maxGroupSize;
        },
        getNameFieldLength(state) {
            return state.groupPreferences.nameFieldLength;
        },
        getEditExistingGroup(state) {
            return state.editExistingGroup;
        },
        getGroupContext(state) {
            return state.groupContext;
        },
        getGroupList(state) {
            return state.groupList;
        },
        getShowOnboarding(state) {
            return state.showOnboarding;
        },
    },
};
