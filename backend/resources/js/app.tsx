import './bootstrap';
import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import { StrictMode } from 'react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { Ziggy } from './ziggy';
import { route as ziggyRoute } from 'ziggy-js';

declare global {
    // eslint-disable-next-line no-var
    var route: typeof ziggyRoute;
}

const pages = import.meta.glob('./Pages/**/*.tsx');

createInertiaApp({
    progress: {
        color: '#f97316',
    },
    resolve: (name) => resolvePageComponent(`./Pages/${name}.tsx`, pages),
    setup({ el, App, props }) {
        window.route = (name: Parameters<typeof ziggyRoute>[0], params?: Parameters<typeof ziggyRoute>[1], absolute?: Parameters<typeof ziggyRoute>[2]) =>
            ziggyRoute(name, params, absolute, Ziggy);

        createRoot(el).render(
            <StrictMode>
                <App {...props} />
            </StrictMode>,
        );
    },
});
