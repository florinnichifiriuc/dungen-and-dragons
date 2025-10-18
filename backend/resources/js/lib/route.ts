import { route as ziggyRoute } from 'ziggy-js';

export function safeRoute(
    name: Parameters<typeof ziggyRoute>[0],
    fallback: string,
    params?: Parameters<typeof ziggyRoute>[1],
    absolute?: Parameters<typeof ziggyRoute>[2],
): string {
    try {
        return route(name, params, absolute);
    } catch (error) {
        if (import.meta.env.DEV) {
            // eslint-disable-next-line no-console
            console.warn(`Missing Ziggy route '${String(name)}', falling back to '${fallback}'.`, error);
        }
        return fallback;
    }
}

