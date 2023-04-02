declare module '*.vue' {
    // NOTE: ts-loader
    // source: https://github.com/vuejs/core/issues/2627#issuecomment-799364296
    import { defineComponent } from 'vue';

    const component: ReturnType<typeof defineComponent>;
    export default component;
}
