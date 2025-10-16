import { useCallback, useEffect, useRef, useState } from 'react';

import type { ConditionTimerSummaryResource } from '@/components/condition-timers/PlayerConditionTimerSummaryPanel';

type UpdateOptions = {
    allowStale?: boolean;
    persist?: boolean;
};

type UseConditionTimerSummaryCacheOptions = {
    storageKey: string;
    initialSummary: ConditionTimerSummaryResource;
};

type UseConditionTimerSummaryCacheResult = {
    summary: ConditionTimerSummaryResource;
    updateSummary: (next: ConditionTimerSummaryResource, options?: UpdateOptions) => void;
    hydratedFromCache: boolean;
};

const safeDate = (value: string | null | undefined): number => {
    if (!value) {
        return 0;
    }

    const timestamp = Date.parse(value);

    return Number.isNaN(timestamp) ? 0 : timestamp;
};

export function useConditionTimerSummaryCache({
    storageKey,
    initialSummary,
}: UseConditionTimerSummaryCacheOptions): UseConditionTimerSummaryCacheResult {
    const [summary, setSummary] = useState<ConditionTimerSummaryResource>(initialSummary);
    const [hydratedFromCache, setHydratedFromCache] = useState(false);
    const lastInitialTimestamp = useRef<string | null>(initialSummary?.generated_at ?? null);

    const updateSummary = useCallback(
        (next: ConditionTimerSummaryResource, options: UpdateOptions = {}) => {
            const { allowStale = false, persist = true } = options;

            if (!next) {
                return;
            }

            setSummary((current) => {
                const currentTimestamp = safeDate(current?.generated_at);
                const nextTimestamp = safeDate(next.generated_at);

                if (!allowStale && nextTimestamp < currentTimestamp && current) {
                    return current;
                }

                if (persist && typeof window !== 'undefined') {
                    try {
                        window.localStorage.setItem(storageKey, JSON.stringify(next));
                    } catch (error) {
                        // Swallow storage exceptions (quota, privacy mode, etc.).
                    }
                }

                return next;
            });
        },
        [storageKey],
    );

    useEffect(() => {
        if (typeof window === 'undefined') {
            setHydratedFromCache(true);
            return;
        }

        try {
            const stored = window.localStorage.getItem(storageKey);

            if (!stored) {
                return;
            }

            const parsed = JSON.parse(stored) as ConditionTimerSummaryResource | null;

            if (!parsed?.generated_at) {
                return;
            }

            updateSummary(parsed, { allowStale: false, persist: false });
        } catch (error) {
            // Ignore hydration failures and fall back to server payload.
        } finally {
            setHydratedFromCache(true);
        }
    }, [storageKey, updateSummary]);

    useEffect(() => {
        const incomingTimestamp = initialSummary?.generated_at ?? null;

        if (!incomingTimestamp || lastInitialTimestamp.current === incomingTimestamp) {
            return;
        }

        lastInitialTimestamp.current = incomingTimestamp;
        updateSummary(initialSummary, { allowStale: false, persist: true });
    }, [initialSummary, updateSummary]);

    return { summary, updateSummary, hydratedFromCache };
}

export default useConditionTimerSummaryCache;
