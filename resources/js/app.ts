import { createApp, h, type DefineComponent } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';

const appName = import.meta.env.VITE_APP_NAME ?? 'Trove';

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
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },

    progress: {
        color: '#4b5563',
    },
});
