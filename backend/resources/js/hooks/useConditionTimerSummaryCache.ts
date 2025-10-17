import { useCallback, useEffect, useRef, useState } from 'react';

import type { ConditionTimerSummaryResource } from '@/components/condition-timers/PlayerConditionTimerSummaryPanel';
import { getEncryptedItem, setEncryptedItem } from '@/lib/secureStorage';

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

                if (persist) {
                    void setEncryptedItem(storageKey, next);
                }

                return next;
            });
        },
        [storageKey],
    );

    useEffect(() => {
        let cancelled = false;

        const hydrate = async () => {
            const stored = await getEncryptedItem<ConditionTimerSummaryResource>(storageKey);

            if (!stored || !stored.generated_at || cancelled) {
                setHydratedFromCache(true);
                return;
            }

            updateSummary(stored, { allowStale: false, persist: false });
            setHydratedFromCache(true);
        };

        hydrate().catch(() => {
            setHydratedFromCache(true);
        });

        return () => {
            cancelled = true;
        };
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
