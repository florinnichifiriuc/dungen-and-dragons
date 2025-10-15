import axios from 'axios';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

declare global {
    interface Window {
        axios: typeof axios;
        Echo?: Echo;
        Pusher?: typeof Pusher;
    }
}

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;

const reverbKey = import.meta.env.VITE_REVERB_APP_KEY as string | undefined;

if (reverbKey) {
    const host = (import.meta.env.VITE_REVERB_HOST as string | undefined) ?? window.location.hostname;
    const scheme = (import.meta.env.VITE_REVERB_SCHEME as string | undefined) ?? 'https';
    const portEnv = import.meta.env.VITE_REVERB_PORT as string | undefined;
    const port = portEnv ? Number(portEnv) : scheme === 'https' ? 443 : 8080;

    window.Pusher = Pusher;

    window.Echo = new Echo({
        broadcaster: 'pusher',
        key: reverbKey,
        wsHost: host,
        wsPort: port,
        wssPort: port,
        forceTLS: scheme === 'https',
        enabledTransports: scheme === 'https' ? ['wss'] : ['ws', 'wss'],
        disableStats: true,
    });
}
