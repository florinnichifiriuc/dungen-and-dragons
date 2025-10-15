import type Echo from 'laravel-echo';

export function getEcho(): Echo | null {
    if (typeof window === 'undefined') {
        return null;
    }

    return window.Echo ?? null;
}
