import type { Ziggy as ZiggyContract } from 'ziggy-js';

declare global {
    interface Window {
        Ziggy?: ZiggyContract;
    }
}

export const Ziggy: ZiggyContract = window.Ziggy ?? {
    url: '',
    port: null,
    defaults: {},
    routes: {},
};
