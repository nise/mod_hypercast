import { CourseTextSnippet, Player } from '@/models';
import { storageGet, storageSet, clamp, RequireOnlyOne } from '@/utils';
import Communication from '@/scripts/communication';
import { LiveSession } from '@/models/liveSession';
import { WRTCSettings } from '@/models/wrtcSettings';

export default {
    namespaced: true,
    state: () =>
        ({
            isPlaying: false,
            speed: 1,
            volume: 100,
            currentTime: 0,
            duration: 0,
            courseText: [] as CourseTextSnippet[],
            chapters: [] as CourseTextSnippet[],
            currentChapter: {},
            scrolledByUser: false,
            isSeeking: false,
            percentageSeeked: 0,
            liveSessionJoined: false,
            wrtcSetting: {} as WRTCSettings,
            liveSession: {} as LiveSession,
            showChapterOverlay: false,
        } as Player),
    actions: {
        async initializePlayer({ rootGetters, dispatch }): Promise<number> {
            const { timestamp } = await Communication.webservice(
                'getPlaytime',
                {
                    cmid: rootGetters.getCourseModuleID,
                }
            );

            await dispatch('updateCurrentTime', { data: { exact: timestamp } });
            return timestamp;
        },
        updateCurrentTime(
            { commit, state, dispatch },
            {
                data,
                notifyGroup = false,
            }: {
                notifyGroup?: boolean;
                data: RequireOnlyOne<{ exact: number; delta: number }>;
            }
        ) {
            if (data.exact && data.delta)
                throw new Error(
                    'Only set one of the "exact" OR "delta" property for the updateCurrentTime operation.'
                );

            let updatedCurrentTime;

            // Update by a delta value (skip amount)
            if (data.delta) updatedCurrentTime = state.currentTime + data.delta;

            // update by a hard value to which the variable should be set (exact timestamp)
            if (data.exact || data.exact === 0) updatedCurrentTime = data.exact;

            // The duration will not be set onmounted of the player component, but when the audioplayer fires the `canplay` event.
            // So when the duration is 0, it is still not initialized which is why clamp will always clamp to 0 in the beginning.
            // Therefore we can just use the initial value from `updatedCurrentTime`.
            const timestamp = state.duration
                ? clamp(updatedCurrentTime, 0, state.duration)
                : updatedCurrentTime;

            if (state.liveSessionJoined && notifyGroup)
                dispatch('updateGroupMembersCurrentTime', {
                    timestamp,
                    notifyGroup,
                });

            commit('setCurrentTime', timestamp);
        },
        leaveLiveSession({ commit, dispatch }) {
            const message = { key: 'leaveLiveSession' };
            dispatch('communication/sendMessage', message, { root: true });
            commit('setLiveSessionJoined', false);
        },
        joinLiveSession({ commit, dispatch, state }) {
            const message = {
                key: 'joinLiveSession',
                payload: state.currentTime,
            };
            dispatch('communication/sendMessage', message, { root: true });
            commit('setLiveSessionJoined', true);
        },
        loadLiveSessionGroupInfo({ dispatch }) {
            const message = {
                key: 'getGroupInfo',
            };
            dispatch('communication/sendMessage', message, { root: true });
        },
        updateGroupMembersCurrentTime(
            { dispatch, rootGetters },
            { timestamp, notifyGroup }
        ) {
            const message = {
                key: 'updateTimestamp',
                payload: {
                    timestamp,
                    groupid: rootGetters['groups/getGroupContext'].id,
                    notifyGroup,
                },
            };

            dispatch('communication/sendMessage', message, { root: true });
        },
        commitChat({ dispatch, state, rootGetters }) {
            const message = {
                key: 'webRTCMessage',
                payload: {
                    displayName: state.wrtcSetting.displayName,
                    uuid: state.wrtcSetting.UUID,
                    dest: rootGetters['groups/getGroupContext'].id,
                },
            };
            dispatch('communication/sendMessage', message, { root: true });
        },

        commitPeer({ dispatch, state }) {
            const message = {
                key: 'webRTCMessage',
                payload: {
                    displayName: state.wrtcSetting.displayName,
                    uuid: state.wrtcSetting.UUID,
                    destUUID: state.wrtcSetting.destUUID,
                },
            };
            dispatch('communication/sendMessage', message, { root: true });
        },

        commitICE({ dispatch, state }) {
            const message = {
                key: 'webRTCMessage',
                payload: {
                    ice: state.wrtcSetting.ice,
                    uuid: state.wrtcSetting.UUID,
                    destUUID: state.wrtcSetting.destUUID,
                },
            };
            dispatch('communication/sendMessage', message, { root: true });
        },
        commitSDP({ dispatch, state }) {
            const message = {
                key: 'webRTCMessage',
                payload: {
                    sdp: state.wrtcSetting.sdp,
                    uuid: state.wrtcSetting.UUID,
                    destUUID: state.wrtcSetting.destUUID,
                },
            };
            dispatch('communication/sendMessage', message, { root: true });
        },
        commitLeaveMsg({ dispatch, state }) {
            const message = {
                key: 'leaveWebRTC',
                payload: {
                    uuid: state.wrtcSetting.UUID,
                },
            };
            dispatch('communication/sendMessage', message, { root: true });
        },

        async logPauseDueLiveSession({ state, rootGetters }) {
            const userid = rootGetters['getUserID'];
            const timestamp = await Communication.webservice('save_log_entry', {
                userid: rootGetters['getUserID'],
                groupid: rootGetters['groups/getGroupContext'].id,
                event: 'ls_paused',
                data: state.currentTime,
            });
        },

        async logUnmuteVCDueLiveSession({ state, rootGetters }) {
            const timestamp = await Communication.webservice('save_log_entry', {
                userid: rootGetters['getUserID'],
                groupid: rootGetters['groups/getGroupContext'].id,
                event: 'vc_unmute',
                data: state.currentTime,
            });
        },

        loadPlayerSettings({ commit }) {
            const speed = storageGet('player/speed');
            if (speed) commit('setSpeed', JSON.parse(speed));

            const volume = storageGet('player/volume');
            if (volume) commit('setVolume', JSON.parse(volume));
        },
        async loadCourseText({ commit, rootState }) {
            //TODO needs to be changed if other kes should be used.
            const rawKEResponse = await fetch(
                `${rootState.pluginBaseURL}/assets/hyperaudio/marks/ke6.json`
            );
            const keAsJSON = await rawKEResponse.json();
            const filteredCourseText = keAsJSON.filter(
                (block) =>
                    block.type === 'sentence' ||
                    block.type === 'chapter' ||
                    block.type === 'section' ||
                    block.type === 'subsection' ||
                    block.type === 'image' ||
                    block.type === 'url'
            );
            const filteredChapters = filteredCourseText.filter(
                (block) =>
                    block.type === 'chapter' ||
                    block.type === 'section' ||
                    block.type === 'subsection'
            );

            commit('setCourseText', filteredCourseText);
            commit('setChapters', filteredChapters);
        },
    },
    mutations: {
        setCourseText(state, value) {
            state.courseText = value;
        },
        setChapters(state, value) {
            state.chapters = value;
        },
        setScrolledByUser(state, value) {
            state.scrolledByUser = value;
        },
        setCurrentChapter(state, value) {
            state.currentChapter = value
                ? state.chapters.find(
                      (chapter) =>
                          chapter.value.toLowerCase() === value.toLowerCase()
                  )
                : {};
        },
        setShowChapterOverlay(state, value) {
            state.showChapterOverlay = value;
        },
        setPlaying(state, value) {
            state.isPlaying = value;
        },
        setCurrentTime(state, value) {
            state.currentTime = Math.floor(value);
        },
        setDuration(state, value) {
            state.duration = value;
        },
        setSpeed(state, value) {
            storageSet('player/speed', value);
            state.speed = value;
        },
        setVolume(state, value) {
            // If 'value' is actually an event object, convert to numerical value
            if (value.target) {
                value = value.target.valueAsNumber;
            }
            storageSet('player/volume', value);
            state.volume = value;
        },
        setIsSeeking(state, value) {
            state.isSeeking = value;
        },
        setPercentageSeeked(state, value) {
            state.percentageSeeked = value;
        },
        setScrollPosition(state, value) {
            state.scrollPosition = value;
        },
        setLiveSessionJoined(state, value) {
            state.liveSessionJoined = value;
        },
        setUUID(state, value) {
            state.wrtcSetting.UUID = value;
        },
        setlocalUUID(state, value) {
            state.wrtcSetting.localUUID = value;
        },
        setpeerUUID(state, value) {
            state.wrtcSetting.peerUUID = value;
        },
        setdestUUID(state, value) {
            state.wrtcSetting.destUUID = value;
        },
        setdisplayName(state, value) {
            state.wrtcSetting.displayName = value;
        },
        setice(state, value) {
            state.wrtcSetting.ice = value;
        },
        setsdp(state, value) {
            state.wrtcSetting.sdp = value;
        },
        setLiveSession(state, value) {
            state.liveSession = value;
        },
    },
    getters: {
        getCurrentTime(state) {
            return state.currentTime;
        },
        getPercentageSeekedToCurrentTime(state) {
            return state.duration * (state.percentageSeeked / 100);
        },
        getPercentageSeeked(state) {
            return state.percentageSeeked;
        },
        getDuration(state) {
            return state.duration;
        },
        isPlaying(state) {
            return state.isPlaying;
        },
        isSeeking(state) {
            return state.isSeeking;
        },
        getSpeed(state) {
            return state.speed;
        },
        getVolume(state) {
            return state.volume;
        },
        getCourseText(state) {
            return state.courseText;
        },
        getChapters(state) {
            return state.chapters;
        },
        getCurrentChapter(state) {
            return state.currentChapter;
        },
        getShowChapterOverlay(state) {
            return state.showChapterOverlay;
        },
        getScrolledByUser(state) {
            return state.scrolledByUser;
        },
        isLiveSessionJoined(state) {
            return state.liveSessionJoined;
        },
        isLiveSessionRunning(state) {
            return state.liveSession?.activeSession;
        },
        getLiveSession(state): LiveSession {
            return state.liveSession;
        },
    },
};
