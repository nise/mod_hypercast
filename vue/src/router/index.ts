import { createRouter, createWebHistory, Router } from 'vue-router';
import Player from '@/views/player.vue';
import GroupPage from '@/views/groups.vue';
import Settings from '@/components/playerSettings.vue';
import store from '@/store';
import { hashCode, isUserMemberOfGroup } from '@/utils/_groups';
import { useToast } from 'vue-toastification';
import Statistics from '@/views/statistics.vue';

const toast = useToast();

// You have to use child routes if you use the same component. Otherwise the component's beforeRouteUpdate
// will not be called.
const routes = [
    { path: '/statistics', component: Statistics },
    {
        path: '/settings/:id',
        component: Settings,
        beforeEnter: async (to) => {
            const group = await store.dispatch(
                'groups/setGroupContextFromDB',
                to.params.id
            );
            if (!isUserMemberOfGroup(store.getters.getUserID, group)) {
                toast.error(
                    'Einstellungen sind nur für Gruppen verfügbar, in denen du Mitglied bist'
                );
                return '/';
            }
        },
    },
    { path: '/', component: GroupPage },
    //enter group with invite link
    {
        path: '/group/:id',
        component: GroupPage,
        beforeEnter: async (to) => {
            if (typeof to.query.invite === 'string' && to.params.id) {
                const group = await store.dispatch(
                    'groups/setGroupContextFromDB',
                    to.params.id
                );
                const groupHash = hashCode('hypercast' + String(group.id));
                const inviteHash = parseInt(to.query.invite);
                if (groupHash === inviteHash) {
                    try {
                        await store.dispatch('groups/joinGroup', group);
                        return `player/${to.params.id}`;
                    } catch (err) {
                        toast.error('Beitritt zur Gruppe fehlgeschlagen');
                    }
                } else {
                    toast.error('Der Einladungslink ist nicht gültig');
                }
            }
        },
    },
    {
        path: '/player/:id',
        alias: '/player/:id/comments/:time?',
        component: Player,
        beforeEnter: async (to) => {
            const group = await store.dispatch(
                'groups/setGroupContextFromDB',
                to.params.id
            );
            if (!isUserMemberOfGroup(store.getters.getUserID, group)) {
                toast.error(
                    'Nur Mitglieder haben Zugriff auf den Gruppenplayer'
                );
                return '/';
            }
            if (to.params.time) {
                store.commit('comments/setSelectedTime', +to.params.time);
                store.dispatch('comments/showOverlay');
            } else {
                store.dispatch('comments/hideOverlay');
                const regex = new RegExp('/comments/?', 'i');
                if ((to.path as string).match(regex)) {
                    return (to.path as string).replace(new RegExp('/comments/?', 'i'), '');
                }
            }
        },
    },
];

export default (courseModuleID): Router => {
    // base URL is /mod/vuejsdemo/view.php/[course module id]/
    const currenturl = window.location.pathname;
    const base =
        currenturl.substring(0, currenturl.indexOf('.php')) +
        '.php/' +
        courseModuleID.toString() +
        '/';

    return createRouter({
        history: createWebHistory(base),
        routes,
    });
};
