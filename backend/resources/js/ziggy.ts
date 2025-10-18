type RawParameterValue = string | number;

interface RouteConfig {
    uri: string;
    methods: Array<'GET' | 'HEAD' | 'POST' | 'PATCH' | 'PUT' | 'OPTIONS' | 'DELETE'>;
    domain?: string;
    parameters?: string[];
    bindings?: Record<string, string>;
    wheres?: Record<string, unknown>;
    middleware?: string[];
}

interface ZiggyContract {
    url: string;
    port: number | null;
    defaults: Record<string, RawParameterValue>;
    routes: Record<string, RouteConfig>;
    location?: {
        host?: string;
        pathname?: string;
        search?: string;
    };
}

declare global {
    interface Window {
        Ziggy?: ZiggyContract;
    }
}

const fallbackConfig: ZiggyContract = {
    url: '',
    port: null,
    defaults: {},
    routes: {},
};

export const resolveZiggyConfig = (): ZiggyContract => {
    if (typeof window !== 'undefined' && window.Ziggy) {
        return window.Ziggy;
    }

    const meta = document.querySelector('meta[name="ziggy"]') as HTMLMetaElement | null;

    if (meta?.content) {
        try {
            const parsed = JSON.parse(meta.content) as ZiggyContract;
            window.Ziggy = parsed;

            return parsed;
        } catch (error) {
            if (import.meta.env.DEV) {
                // eslint-disable-next-line no-console
                console.warn('Failed to parse Ziggy config from meta tag.', error);
            }
        }
    }

    return fallbackConfig;
};
