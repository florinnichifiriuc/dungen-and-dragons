import './bootstrap';
import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import { StrictMode } from 'react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { resolveZiggyConfig } from './ziggy';
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
        // Always resolve the Ziggy config at call time so HMR or navigation swaps don't leave us
        // with a stale or empty route manifest.
        const routeResolver = (
            name?: unknown,
            params?: unknown,
            absolute?: boolean,
            config?: unknown,
        ) => ziggyRoute(name as never, params as never, absolute, (config ?? resolveZiggyConfig()) as never);

        window.route = routeResolver as typeof ziggyRoute;

        createRoot(el).render(
            <StrictMode>
                <App {...props} />
            </StrictMode>,
        );
    },
});
