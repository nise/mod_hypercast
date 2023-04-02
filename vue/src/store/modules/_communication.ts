import store from '@/store';

export default {
    namespaced: true,
    state: () => ({
        websocket: null,
        initializing: true,
    }),
    actions: {
        initializeWebSocket({ state, commit, rootGetters }, nextGroupID): void {
            commit('setInitializing', true);

            const protocol =
                window.location.protocol === 'https:' ? 'wss' : 'ws';
            const url = `${protocol}://${window.location.hostname}:${window.location.port}/websocket?userid=${rootGetters.getUserID}&groupid=${nextGroupID}`;

            const webSocket = new WebSocket(url);

            webSocket.onclose = function () {
                store.commit('player/setLiveSessionJoined', false);
                store.commit('player/setLiveSession', {});
            };
            webSocket.onopen = function () {
                commit('setInitializing', false);
            };
            commit('setWebSocket', webSocket);
        },
        closeWebSocket({ state, commit }): void {
            state.websocket.close();
            commit('setWebSocket', null);
        },
        validateConnection({ state, rootGetters }): void {
            if (state.websocket.readyState === WebSocket.CLOSED) {
                store.dispatch(
                    'communication/initializeWebSocket',
                    rootGetters['groups/getGroupContext'].id
                );
            }
        },
        sendMessage(
            { state, rootGetters },
            message: { key: string; payload: Record<string, any> }
        ) {
            if (state.websocket.readyState === WebSocket.OPEN) {
                state.websocket.send(JSON.stringify(message));
            } else {
                const connectionInterval = setInterval(function () {
                    if (!state.websocket) {
                        return;
                    }

                    if (state.websocket.readyState === WebSocket.OPEN) {
                        clearInterval(connectionInterval);
                        state.websocket.send(JSON.stringify(message));
                    } else {
                        if (
                            !state.initializing &&
                            state.websocket.readyState === WebSocket.CLOSED
                        ) {
                            store.dispatch(
                                'communication/initializeWebSocket',
                                rootGetters['groups/getGroupContext'].id
                            );
                        }
                    }
                }, 100);
            }
        },
    },
    mutations: {
        setWebSocket(state, payload: WebSocket | null) {
            state.websocket = payload;
        },
        setInitializing(state, value) {
            state.initializing = value;
        },
    },
    getters: {
        getWebSocket(state) {
            return state.websocket;
        },
    },
};
