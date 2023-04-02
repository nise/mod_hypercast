export default {
    namespaced: true,
    state: () => ({
        show: false,
        type: 'primary',
        message: 'unknown',
    }),
    mutations: {
        showAlert(state, [type, message]) {
            state.type = type;
            state.message = message;
            state.show = true;
        },

        closeAlert(state) {
            state.show = false;
            state.type = 'primary';
            state.message = '';
        }
     },
    actions: {},
    getters: {
        getAlertType: function (state) {
            return state.type;
        },
        getAlertState: function (state) {
            return state.show;
        },
        getAlertMessage: function (state) {
            return state.message;
        }
     }
  }