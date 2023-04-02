import { createStore } from 'vuex';
import {
    notifications,
    groups,
    comments,
    player,
    communication,
    settings,
} from './modules';
import moodleAjax from 'core/ajax';
import moodleStorage from 'core/localstorage';
import Notification from 'core/notification';
import $ from 'jquery';

const store = createStore({
    modules: {
        notifications,
        groups,
        comments,
        player,
        communication,
        settings,
    },
    state: {
        // variables brought by moodle
        pluginName: '',
        pluginBaseURL: '',
        courseModuleID: 0,
        contextID: 0,
        strings: {},
        isModerator: false,
        userID: 0,
        //main chapter
        chapterName: 'KE6: Awareness',
    },
    //strict: process.env.NODE_ENV !== 'production',
    mutations: {
        setPluginName(state, name) {
            state.pluginName = name;
        },
        setPluginBaseURL(state, value) {
            state.pluginBaseURL = value;
        },
        setModerator(state, isModerator) {
            state.isModerator = isModerator;
        },
        setCourseModuleID(state, id) {
            state.courseModuleID = id;
        },
        setContextID(state, id) {
            state.contextID = id;
        },
        setUserID(state, id) {
            state.userID = id;
        },
        setStrings(state, strings) {
            state.strings = strings;
        },
        setChapterName(state, value){
            state.chapterName = value;
        }
    },
    getters: {
        getPluginBaseURL: function (state) {
            return state.pluginBaseURL;
        },
        getModeratorStatus: function (state) {
            return state.isModerator;
        },
        getContextID: function (state) {
            return state.contextID;
        },
        getCourseModuleID: function (state) {
            return state.courseModuleID;
        },
        getPluginName: function (state) {
            return state.pluginName;
        },
        getUserID: function (state) {
            return +state.userID;
        },
        getChapterName: function (state) {
            return state.chapterName;
        },
    },
    actions: {
        initializeMoodleValues(
            { commit },
            {
                fullPluginName,
                coursemoduleid,
                contextid,
                isModerator,
                pluginBaseURL,
                userid,
            }
        ) {
            commit('setPluginName', fullPluginName);
            commit('setCourseModuleID', coursemoduleid);
            commit('setContextID', contextid);
            commit('setModerator', isModerator);
            commit('setUserID', userid);
            commit('setPluginBaseURL', pluginBaseURL);
        },
        async loadComponentStrings(context) {
            const lang = $('html')?.attr('lang')?.replace(/-/g, '_');
            const cacheKey = 'mod_hypercast/strings/' + lang;
            const cachedStrings = moodleStorage.get(cacheKey);
            if (cachedStrings) {
                context.commit('setStrings', JSON.parse(cachedStrings));
            } else {
                const request = {
                    methodname: 'core_get_component_strings',
                    args: {
                        component: 'mod_hypercast',
                        lang,
                    },
                };
                const loadedStrings = await moodleAjax.call([request])[0];
                const strings: any = {};
                loadedStrings.forEach((s: any) => {
                    strings[s.stringid] = s.string;
                });
                context.commit('setStrings', strings);
                moodleStorage.set(cacheKey, JSON.stringify(strings));
            }
        },
    },
});

/**
 * Single ajax call to Moodle.
 */
export async function ajax(method: any, args: any) {
    const request = {
        methodname: method,
        args: Object.assign(
            {
                coursemoduleid: store.state.courseModuleID,
            },
            args
        ),
    };

    try {
        return await moodleAjax.call([request])[0];
    } catch (e) {
        Notification.exception(e);
        throw e;
    }
}

export default store;
