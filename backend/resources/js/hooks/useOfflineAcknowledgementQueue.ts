import { useCallback, useEffect, useMemo, useRef, useState } from 'react';

import type { ConditionAcknowledgementPayload } from '@/lib/conditionAcknowledgements';
import { applyAcknowledgementToSummary } from '@/lib/conditionAcknowledgements';
import { recordAnalyticsEventSync } from '@/lib/analytics';
import { getEncryptedItem, setEncryptedItem } from '@/lib/secureStorage';
import type { ConditionTimerSummaryResource } from '@/components/condition-timers/PlayerConditionTimerSummaryPanel';

type QueueItem = {
    id: string;
    tokenId: number;
    conditionKey: string;
    summaryGeneratedAt: string;
    queuedAt: string;
    attempts: number;
    lastError?: string | null;
};

type OfflineQueueState = {
    isOffline: boolean;
    pendingCount: number;
    syncing: boolean;
    conflicts: string[];
    enqueue: (tokenId: number, conditionKey: string, summaryGeneratedAt: string) => Promise<void>;
    acknowledge: (
        tokenId: number,
        conditionKey: string,
        summaryGeneratedAt: string,
    ) => Promise<{ applied?: ConditionTimerSummaryResource; queued?: boolean }>;
    resolveConflict: (id: string) => void;
    pendingItems: QueueItem[];
};

const OFFLINE_ERROR_CODES = ['Failed to fetch', 'NetworkError', 'TypeError'];

const isOfflineError = (error: unknown): boolean => {
    if (typeof navigator !== 'undefined' && navigator.onLine === false) {
        return true;
    }

    if (!error) {
        return false;
    }

    const message = error instanceof Error ? error.message : String(error);

    return OFFLINE_ERROR_CODES.some((code) => message.includes(code));
};

const serializeKey = (groupId: number): string => `group.${groupId}.condition-ack-queue`;

export function useOfflineAcknowledgementQueue(
    groupId: number,
    summary: ConditionTimerSummaryResource,
    onSummaryUpdate?: (next: ConditionTimerSummaryResource) => void,
): OfflineQueueState {
    const [queue, setQueue] = useState<QueueItem[]>([]);
    const [syncing, setSyncing] = useState(false);
    const [conflicts, setConflicts] = useState<string[]>([]);
    const onlineRef = useRef<boolean>(typeof navigator === 'undefined' ? true : navigator.onLine);
    const storageKey = useMemo(() => serializeKey(groupId), [groupId]);

    useEffect(() => {
        let cancelled = false;

        const hydrate = async () => {
            const stored = await getEncryptedItem<QueueItem[]>(storageKey);

            if (stored && !cancelled) {
                const normalised = stored.map((item) => ({
                    ...item,
                    queuedAt: item.queuedAt ?? new Date().toISOString(),
                    attempts: item.attempts ?? 0,
                }));

                setQueue(normalised);

                if (normalised.some((item, index) => item.queuedAt !== stored[index]?.queuedAt || item.attempts !== stored[index]?.attempts)) {
                    await setEncryptedItem(storageKey, normalised);
                }
            }
        };

        hydrate().catch(() => {});

        return () => {
            cancelled = true;
        };
    }, [storageKey]);

    useEffect(() => {
        if (typeof window === 'undefined') {
            return;
        }

        const handleOnline = () => {
            onlineRef.current = true;
            void flushQueue();
        };

        const handleOffline = () => {
            onlineRef.current = false;
        };

        window.addEventListener('online', handleOnline);
        window.addEventListener('offline', handleOffline);

        return () => {
            window.removeEventListener('online', handleOnline);
            window.removeEventListener('offline', handleOffline);
        };
    }, []);

    const persistQueue = useCallback(
        async (items: QueueItem[]) => {
            setQueue(items);
            await setEncryptedItem(storageKey, items);
        },
        [storageKey],
    );

    const enqueue = useCallback<OfflineQueueState['enqueue']>(
        async (tokenId, conditionKey, summaryGeneratedAt) => {
            const item: QueueItem = {
                id: `${tokenId}:${conditionKey}:${Date.now()}`,
                tokenId,
                conditionKey,
                summaryGeneratedAt,
                queuedAt: new Date().toISOString(),
                attempts: 0,
                lastError: null,
            };

            recordAnalyticsEventSync({
                key: 'timer_summary.acknowledgement.queued',
                groupId,
                payload: { token_id: tokenId, condition_key: conditionKey, queued_at: item.queuedAt },
            });

            const deduped = queue.filter((existing) => !(existing.tokenId === tokenId && existing.conditionKey === conditionKey));

            await persistQueue([...deduped, item]);
        },
        [groupId, persistQueue, queue],
    );

    const applyPayload = useCallback(
        (payload: ConditionAcknowledgementPayload) => {
            if (!onSummaryUpdate) {
                return;
            }

            const next = applyAcknowledgementToSummary(summary, payload);
            onSummaryUpdate(next);
        },
        [onSummaryUpdate, summary],
    );

    const acknowledge = useCallback<OfflineQueueState['acknowledge']>(
        async (tokenId, conditionKey, summaryGeneratedAt) => {
            const csrf = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content ?? undefined;

            try {
                const response = await fetch(
                    route('groups.condition-timers.acknowledgements.store', groupId),
                    {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            Accept: 'application/json',
                            ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
                        },
                        body: JSON.stringify({
                            map_token_id: tokenId,
                            condition_key: conditionKey,
                            summary_generated_at: summaryGeneratedAt,
                            source: 'online',
                        }),
                    },
                );

                if (!response.ok) {
                    if (response.status === 409) {
                        const composite = `${tokenId}:${conditionKey}`;
                        setConflicts((current) => (current.includes(composite) ? current : [...current, composite]));
                        recordAnalyticsEventSync({
                            key: 'timer_summary.acknowledgement.conflict',
                            groupId,
                            payload: { token_id: tokenId, condition_key: conditionKey, source: 'online' },
                        });
                    }

                    throw new Error(`Failed with status ${response.status}`);
                }

                const payload = (await response.json()) as { acknowledgement?: ConditionAcknowledgementPayload & { queued_at?: string; acknowledged_at?: string } };
                const acknowledgement = payload.acknowledgement;

                if (acknowledgement) {
                    applyPayload(acknowledgement);
                }

                recordAnalyticsEventSync({
                    key: 'timer_summary.acknowledgement.recorded',
                    groupId,
                    payload: {
                        token_id: tokenId,
                        condition_key: conditionKey,
                        source: 'online',
                        queued_at: acknowledgement?.queued_at ?? null,
                        acknowledged_at: acknowledgement?.acknowledged_at ?? new Date().toISOString(),
                    },
                });

                return { applied: summary };
            } catch (error) {
                if (isOfflineError(error)) {
                    await enqueue(tokenId, conditionKey, summaryGeneratedAt);
                    return { queued: true };
                }

                throw error;
            }
        },
        [applyPayload, enqueue, groupId, summary],
    );

    const flushQueue = useCallback(async () => {
        if (queue.length === 0 || syncing) {
            return;
        }

        setSyncing(true);

        const nextQueue: QueueItem[] = [];

        for (const item of queue) {
            try {
                const csrf = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content ?? undefined;
                const response = await fetch(
                    route('groups.condition-timers.acknowledgements.store', groupId),
                    {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            Accept: 'application/json',
                            ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
                        },
                        body: JSON.stringify({
                            map_token_id: item.tokenId,
                            condition_key: item.conditionKey,
                            summary_generated_at: item.summaryGeneratedAt,
                            source: 'offline',
                            queued_at: item.queuedAt,
                        }),
                    },
                );

                if (!response.ok) {
                    if (response.status === 409) {
                        const composite = `${item.tokenId}:${item.conditionKey}`;
                        setConflicts((current) => (current.includes(composite) ? current : [...current, composite]));
                        recordAnalyticsEventSync({
                            key: 'timer_summary.acknowledgement.conflict',
                            groupId,
                            payload: { token_id: item.tokenId, condition_key: item.conditionKey, source: 'offline' },
                        });
                        continue;
                    }

                    throw new Error(`Failed with status ${response.status}`);
                }

                const payload = (await response.json()) as { acknowledgement?: ConditionAcknowledgementPayload & { queued_at?: string; acknowledged_at?: string } };
                const acknowledgement = payload.acknowledgement;

                if (acknowledgement) {
                    applyPayload(acknowledgement);
                }

                const queuedTimestamp = Date.parse(item.queuedAt);
                const queueLag = Number.isNaN(queuedTimestamp) ? null : Math.max(Date.now() - queuedTimestamp, 0);

                recordAnalyticsEventSync({
                    key: 'timer_summary.acknowledgement.flushed',
                    groupId,
                    payload: {
                        token_id: item.tokenId,
                        condition_key: item.conditionKey,
                        queued_at: item.queuedAt,
                        acknowledged_at: acknowledgement?.acknowledged_at ?? null,
                        attempts: item.attempts + 1,
                        source: 'offline',
                        sync_lag_ms: queueLag,
                    },
                });
            } catch (error) {
                const message = error instanceof Error ? error.message : String(error);

                if (isOfflineError(error)) {
                    recordAnalyticsEventSync({
                        key: 'timer_summary.acknowledgement.retry_scheduled',
                        groupId,
                        payload: { token_id: item.tokenId, condition_key: item.conditionKey, attempts: item.attempts + 1, last_error: message },
                    });
                    nextQueue.push({ ...item, attempts: item.attempts + 1, lastError: message });
                    break;
                }

                recordAnalyticsEventSync({
                    key: 'timer_summary.acknowledgement.flush_failed',
                    groupId,
                    payload: { token_id: item.tokenId, condition_key: item.conditionKey, attempts: item.attempts + 1, last_error: message },
                });

                nextQueue.push({ ...item, attempts: item.attempts + 1, lastError: message });
            }
        }

        await persistQueue(nextQueue);
        setSyncing(false);
    }, [applyPayload, groupId, persistQueue, queue, syncing]);

    useEffect(() => {
        if (onlineRef.current && queue.length > 0) {
            void flushQueue();
        }
    }, [flushQueue, queue.length]);

    const resolveConflict = useCallback((id: string) => {
        setConflicts((current) => current.filter((value) => value !== id));
    }, []);

    return {
        isOffline: !onlineRef.current,
        pendingCount: queue.length,
        syncing,
        conflicts,
        enqueue,
        acknowledge,
        resolveConflict,
        pendingItems: queue,
    };
}

export default useOfflineAcknowledgementQueue;
