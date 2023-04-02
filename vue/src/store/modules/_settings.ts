import { PrivacySettings } from '@/models';
import { filterAudioCues, storageGet, storageSet } from '@/utils';
import Communication from '@/scripts/communication';

import { useToast } from 'vue-toastification';

const toast = useToast();
const soundfiles = {
    // chapter: 'newspaper-flip-page-notification.mp3',         // Would be too much for the listener to hear a sound on this mark
    // section: 'newspaper-flip-page-notification.mp3',         // Would be too much for the listener to hear a sound on this mark
    // subsection: 'newspaper-flip-page-notification.mp3',      // Would be too much for the listener to hear a sound on this mark
    // subsubsection: 'newspaper-flip-page-notification.mp3',   // Would be too much for the listener to hear a sound on this mark
    // paragraph: 'newspaper-flip-page-notification.mp3',       // Would be too much for the listener to hear a sound on this mark

    // pattern: 'newspaper-flip-page-notification.mp3',         // Would be too much for the listener to hear a sound on this mark
    // citation: 'newspaper-flip-page-notification.mp3',        // Would be too much for the listener to hear a sound on this mark
    figureref: 'newspaper-flip-page-notification.mp3',          // Figurereferences (visual content)
    tableref: 'newspaper-flip-page-notification.mp3',           // Tablereferences (visual content)
    // textref: 'newspaper-flip-page-notification.mp3',         // Not present in any VTT
    // footnote: 'tarea-completa-63839.mp3',                    // Would be too much for the listener to hear a sound on this mark
    // listing: 'tarea-completa-63839.mp3',                     // Not present in any VTT
    image: 'image-plop-notification.mp3',                       // Images (visual content)
    // example: 'tarea-completa-63839.mp3',                     // Not present in any VTT
    // discussion: 'tarea-completa-63839.mp3',                  // Not present in any VTT
    url: 'double-click-notification.mp3',                       // Links (any type of urls: YouTube, Forum, Soundcloud...)
    // link: 'tarea-completa-63839.mp3',                        // Not present in any VTT
    // book: 'tarea-completa-63839.mp3',                        // Not present in any VTT
    // video: 'tarea-completa-63839.mp3',                       // Not present in any VTT
    // icon_goal: 'tarea-completa-63839.mp3',                   // Not present in any VTT
    // icon_paper: 'tarea-completa-63839.mp3',                  // Not present in any VTT
    // icon_quiz: 'tarea-completa-63839.mp3',                   // Not present in any VTT
    // icon_discussion: 'tarea-completa-63839.mp3',             // Not present in any VTT
};
const flashy_soundfiles = {
    // chapter: 'newspaper-flip-page-notification.mp3',         // Would be too much for the listener to hear a sound on this mark
    // section: 'newspaper-flip-page-notification.mp3',         // Would be too much for the listener to hear a sound on this mark
    // subsection: 'newspaper-flip-page-notification.mp3',      // Would be too much for the listener to hear a sound on this mark
    // subsubsection: 'newspaper-flip-page-notification.mp3',   // Would be too much for the listener to hear a sound on this mark
    // paragraph: 'newspaper-flip-page-notification.mp3',       // Would be too much for the listener to hear a sound on this mark

    // pattern: 'newspaper-flip-page-notification.mp3',         // Would be too much for the listener to hear a sound on this mark
    // citation: 'newspaper-flip-page-notification.mp3',        // Would be too much for the listener to hear a sound on this mark
    figureref: 'interface-1-126517.mp3',          // Figurereferences (visual content)
    tableref: 'interface-1-126517.mp3',           // Tablereferences (visual content)
    // textref: 'newspaper-flip-page-notification.mp3',         // Not present in any VTT
    // footnote: 'tarea-completa-63839.mp3',                    // Would be too much for the listener to hear a sound on this mark
    // listing: 'tarea-completa-63839.mp3',                     // Not present in any VTT
    image: 'notification-for-game-scenes-132473.mp3',                       // Images (visual content)
    // example: 'tarea-completa-63839.mp3',                     // Not present in any VTT
    // discussion: 'tarea-completa-63839.mp3',                  // Not present in any VTT
    url: 'message-incoming-132126.mp3',                       // Links (any type of urls: YouTube, Forum, Soundcloud...)
    // link: 'tarea-completa-63839.mp3',                        // Not present in any VTT
    // book: 'tarea-completa-63839.mp3',                        // Not present in any VTT
    // video: 'tarea-completa-63839.mp3',                       // Not present in any VTT
    // icon_goal: 'tarea-completa-63839.mp3',                   // Not present in any VTT
    // icon_paper: 'tarea-completa-63839.mp3',                  // Not present in any VTT
    // icon_quiz: 'tarea-completa-63839.mp3',                   // Not present in any VTT
    // icon_discussion: 'tarea-completa-63839.mp3',             // Not present in any VTT
};


export default {
    namespaced: true,
    state: () => ({
        privacySettings: {
            hideUser: false,
            hideOthers: false,
            audioCues: 'test',
        },
        textSize: 1,
    }),
    mutations: {
        setPrivacySettings(state, value) {
            state.privacySettings = value;
        },
        setTextSize(state, value) {
            storageSet('settings/textsize', value);
            state.textSize = value;
        },
    },
    actions: {
        async loadPrivacySettings({ commit, rootGetters }): Promise<PrivacySettings | undefined> {
            try {
                const groupid = rootGetters['groups/getGroupContext'].id;
                const settings: PrivacySettings = await Communication.webservice(
                    'getPrivacySettings',
                    { groupid }
                );
                if (!settings.audioCues){
                    settings.audioCues = JSON.stringify({
                        figureref: true,
                        tableref: true,
                        image: true,
                        url: true,
                        type: 'subtle'
                    });

                }
                commit('setPrivacySettings', settings);
                return settings;
            } catch (e) {
                toast.error('Die Einstellungen konnten nicht geladen werden');
            }
        },
        async savePrivacySettings({ commit }, settings: PrivacySettings) {
            try {
                await Communication.webservice('savePrivacySettings', {
                    groupid: settings.groupid,
                    hideUser: settings.hideUser,
                    hideOthers: settings.hideOthers,
                    audioCues: settings.audioCues,
                });
                commit('setPrivacySettings', settings);
            } catch (e) {
                toast.error(
                    'Die Einstellungen konnten nicht gespeichert werden'
                );
            }
        },
        loadTextSize({ commit }) {
            const TextSize = storageGet('settings/textsize');
            if (TextSize) commit('setTextSize', JSON.parse(TextSize));
        },
    },
    getters: {
        getPrivacySettings(state) {
            return state.privacySettings;
        },
        getSoundfiles() {
            return soundfiles;
        },
        getFlashySoundfiles() {
            return flashy_soundfiles;
        },
        getFilteredSoundfiles(state, getters) {
            if (getters.getCueSettings.type === 'flashy') {
                return filterAudioCues(flashy_soundfiles, getters.getCueSettings);
            }
            return filterAudioCues(soundfiles, getters.getCueSettings);
        },
        getCueSettings(state) {
            return JSON.parse(state.privacySettings.audioCues);
        },
        getTextSize(state) {
            return state.textSize;
        },
        getTextSizeInRem(state) {
            return `${state.textSize}rem`;
        }
    },
};
