import '../css/app.css';
import './bootstrap';

import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createGtm } from '@gtm-support/vue-gtm'
import { createApp, h } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import { createPinia } from 'pinia';
import PhosphorIcons from "@phosphor-icons/vue"

import PrimeVue from 'primevue/config';
import Aura from '@primevue/themes/aura';

const appName = import.meta.env.VITE_APP_NAME || '8OHM';
const pinia = createPinia();

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(pinia)
            .use(PhosphorIcons)
            .use(PrimeVue, {
                theme: {
                    preset: Aura,
                    options: {
                        darkModeSelector: '.dark',
                    }
                }
            })
            .use(createGtm({
                id: 'GTM-M7VMQ8GR',
                defer: false,
                compatibility: false,
                enabled: true,
                debug: true,
                loadScript: true,
            })
            )
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

