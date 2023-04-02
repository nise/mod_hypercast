import { createApp } from 'vue';
import Toast, { PluginOptions } from 'vue-toastification';
import initRouter from './router';
import store from './store';
import App from './app.vue';
import Communication from './scripts/communication';
import { FontAwesomeIcon } from './utils/_fontawesome';

import 'vue-toastification/dist/index.css';
import './styles/main.css'

export function init(
    coursemoduleid: string,
    contextid: number,
    fullPluginName: string,
    isModerator: boolean,
    userid: number,
) {
    Communication.setPluginName(fullPluginName);

    store.dispatch('initializeMoodleValues', {
        fullPluginName,
        coursemoduleid,
        contextid,
        isModerator,
        pluginBaseURL: `${M.cfg.wwwroot}/mod/hypercast`,
        userid
    });
    store.dispatch('player/loadPlayerSettings');
    store.dispatch('settings/loadTextSize');
    store.dispatch('player/loadCourseText')
    store.dispatch('loadComponentStrings');

    // We need to overwrite the variable for lazy loading.
    __webpack_public_path__ = M.cfg.wwwroot + '/mod/hypercast/amd/build/';
    const app = createApp(App)

    // Add font-awesome-icon component to Vue
    app.component('font-awesome-icon', FontAwesomeIcon)

    // will not allow devtools on production builds.
    // browser has to be restarted to take effect.
    // see https://vuejs.org/api/application.html#app-config-performance for more info.
    app.config.performance = process.env.NODE_ENV === 'development'

    // Config Vue Toasts
    const toastOptions: PluginOptions = {
      maxToasts: 5,
      newestOnTop: false,
      transition: 'Vue-Toastification__fade',
    };

    app.use(initRouter(coursemoduleid)).use(Toast, toastOptions).use(store).mount('#page');
}
