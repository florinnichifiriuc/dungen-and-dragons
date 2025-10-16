type AnalyticsEventOptions = {
    key: string;
    groupId?: number | null;
    payload?: Record<string, unknown>;
};

function csrfToken(): string | null {
    if (typeof document === 'undefined') {
        return null;
    }

    const element = document.querySelector('meta[name="csrf-token"]');
    return element?.getAttribute('content') ?? null;
}

export async function recordAnalyticsEvent({
    key,
    groupId = null,
    payload = {},
}: AnalyticsEventOptions): Promise<void> {
    const url = route('analytics.events.store');
    const body = JSON.stringify({ key, group_id: groupId, payload });

    try {
        if (typeof navigator !== 'undefined' && typeof navigator.sendBeacon === 'function') {
            const blob = new Blob([body], { type: 'application/json' });

            if (navigator.sendBeacon(url, blob)) {
                return;
            }
        }

        const token = csrfToken();

        await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                ...(token ? { 'X-CSRF-TOKEN': token } : {}),
            },
            body,
            credentials: 'same-origin',
            keepalive: true,
        });
    } catch (error) {
        if (process.env.NODE_ENV !== 'production') {
            console.warn('Failed to record analytics event', error);
        }
    }
}

export function recordAnalyticsEventSync(options: AnalyticsEventOptions): void {
    recordAnalyticsEvent(options).catch(() => {
        // noop
    });
}
