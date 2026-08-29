import { createApp, h, type DefineComponent } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';

// Set from the initial page props during setup(), which runs before the first
// <Head> renders. The build-time VITE_APP_NAME is gone: the name is a runtime
// setting now.
let appName = 'Trove';

createInertiaApp({
    title: (title) => (title ? `${title} — ${appName}` : appName),

    resolve: (name) => {
        const pages = import.meta.glob<DefineComponent>('./pages/**/*.vue', { eager: true });
        const page = pages[`./pages/${name}.vue`];

        if (!page) {
            throw new Error(`Inertia page not found: ./pages/${name}.vue`);
        }

        return page;
    },

    setup({ el, App, props, plugin }) {
        appName = props.initialPage.props.app_name ?? appName;

        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },

    progress: {
        color: '#4b5563',
    },
});
