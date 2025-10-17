import { Link } from '@inertiajs/react';
import { useEffect, useMemo, useState, useCallback } from 'react';
import { AlertTriangle, CheckCircle2, Circle, History, Users2, WifiOff } from 'lucide-react';

import { cn } from '@/lib/utils';
import { recordAnalyticsEventSync } from '@/lib/analytics';
import useOfflineAcknowledgementQueue from '@/hooks/useOfflineAcknowledgementQueue';
import { useTranslations } from '@/hooks/useTranslations';

export type ConditionTimerSummaryCondition = {
    key: string;
    label: string;
    rounds: number | null;
    rounds_hint: string | null;
    urgency: 'calm' | 'warning' | 'critical';
    summary: string;
    acknowledged_by_viewer?: boolean;
    acknowledged_count?: number;
    exposes_exact_rounds?: boolean;
    timeline?: ConditionTimerTimelineEntry[];
};

export type ConditionTimerSummaryEntry = {
    map: { id: number; title: string | null };
    token: {
        id: number;
        label: string;
        visibility: 'visible' | 'obscured';
        disposition: 'ally' | 'neutral' | 'hazard' | 'adversary' | 'unknown';
    };
    conditions: ConditionTimerSummaryCondition[];
};

export type ConditionTimerSummaryResource = {
    group_id: number;
    generated_at: string;
    entries: ConditionTimerSummaryEntry[];
};

export type ConditionTimerTimelineEntry = {
    id: number;
    recorded_at: string;
    reason: string;
    kind: 'started' | 'extended' | 'reduced' | 'cleared' | 'ticked' | 'adjusted';
    summary: string;
    detail?: {
        summary?: string;
        previous_rounds?: number | null;
        new_rounds?: number | null;
        delta?: number | null;
        actor?: { id: number | null; name: string | null; role: string | null } | null;
        context?: Record<string, unknown> | null;
    };
};

type PlayerConditionTimerSummaryPanelProps = {
    summary: ConditionTimerSummaryResource;
    shareUrl?: string;
    className?: string;
    source: string;
    viewerRole?: string | null;
    onDismiss?: () => void;
    onSummaryUpdate?: (next: ConditionTimerSummaryResource) => void;
    allowAcknowledgements?: boolean;
};

const urgencyStyles: Record<ConditionTimerSummaryCondition['urgency'], string> = {
    calm: 'border-emerald-500/30 bg-emerald-500/10 text-emerald-200',
    warning: 'border-amber-500/30 bg-amber-500/10 text-amber-200',
    critical: 'border-rose-500/30 bg-rose-500/10 text-rose-200',
};

export function PlayerConditionTimerSummaryPanel({
    summary,
    shareUrl,
    className,
    source,
    viewerRole,
    onDismiss,
    onSummaryUpdate,
    allowAcknowledgements,
}: PlayerConditionTimerSummaryPanelProps) {
    const { t, locale } = useTranslations('condition_timers');
    const entries = useMemo(() => summary.entries ?? [], [summary.entries]);
    const [pendingAcknowledgements, setPendingAcknowledgements] = useState<Record<string, boolean>>({});
    const [queuedNotice, setQueuedNotice] = useState<string | null>(null);
    const viewerIsFacilitator = viewerRole === 'owner' || viewerRole === 'dungeon-master';
    const acknowledgementsEnabled = allowAcknowledgements ?? true;

    const formatTimestamp = useCallback(
        (timestamp: string): string => {
        try {
            return new Intl.DateTimeFormat(locale, {
                dateStyle: 'medium',
                timeStyle: 'short',
            }).format(new Date(timestamp));
        } catch {
            return timestamp;
        }
        },
        [locale]
    );

    const formatRelativeTimestamp = useCallback(
        (timestamp: string): string => {
            const parsed = Date.parse(timestamp);

            if (Number.isNaN(parsed)) {
                return timestamp;
            }

            const diffMilliseconds = Date.now() - parsed;
            const diffSeconds = Math.round(diffMilliseconds / 1000);
            const formatter = new Intl.RelativeTimeFormat(locale, { numeric: 'auto' });

            if (Math.abs(diffSeconds) < 60) {
                return formatter.format(-diffSeconds, 'second');
            }

            const diffMinutes = Math.round(diffSeconds / 60);

            if (Math.abs(diffMinutes) < 60) {
                return formatter.format(-diffMinutes, 'minute');
            }

            const diffHours = Math.round(diffMinutes / 60);

            if (Math.abs(diffHours) < 24) {
                return formatter.format(-diffHours, 'hour');
            }

            const diffDays = Math.round(diffHours / 24);

            if (Math.abs(diffDays) < 30) {
                return formatter.format(-diffDays, 'day');
            }

            const diffMonths = Math.round(diffDays / 30);

            return formatter.format(-diffMonths, 'month');
        },
        [locale]
    );

    const roundsDisplay = useCallback(
        (condition: ConditionTimerSummaryCondition): string => {
            if (condition.rounds !== null) {
                return t('player_panel.rounds.exact', undefined, { count: condition.rounds });
            }

            if (condition.rounds_hint) {
                return condition.rounds_hint;
            }

            return t('player_panel.rounds.fallback');
        },
        [t]
    );

    const dispositionLabel = useCallback(
        (disposition: ConditionTimerSummaryEntry['token']['disposition']) =>
            t(`player_panel.disposition.${disposition}`, disposition),
        [t]
    );

    const effectsLabel = useCallback(
        (count: number) => t('player_panel.token.effects', undefined, { count }),
        [t]
    );

    const stalenessMs = useCallback((timestamp: string): number | null => {
        const parsed = Date.parse(timestamp);

        if (Number.isNaN(parsed)) {
            return null;
        }

        const diff = Date.now() - parsed;

        return diff < 0 ? 0 : diff;
    }, []);

    const {
        isOffline,
        pendingCount,
        syncing,
        acknowledge: queueAcknowledge,
        conflicts,
        resolveConflict,
        pendingItems,
    } = useOfflineAcknowledgementQueue(summary.group_id, summary, onSummaryUpdate);

    const conflictDetails = useMemo(() => {
        if (conflicts.length === 0) {
            return [];
        }

        const lookup = new Map<string, string>();

        for (const entry of entries) {
            for (const condition of entry.conditions) {
                lookup.set(`${entry.token.id}:${condition.key}`, `${entry.token.label} — ${condition.label}`);
            }
        }

        return conflicts.map((id) => ({ id, label: lookup.get(id) ?? id }));
    }, [conflicts, entries]);

    const acknowledgeCondition = async (tokenId: number, conditionKey: string) => {
        if (!summary.generated_at || !acknowledgementsEnabled) {
            return;
        }

        const composite = `${tokenId}:${conditionKey}`;

        setPendingAcknowledgements((current) => ({ ...current, [composite]: true }));

        try {
            const result = await queueAcknowledge(tokenId, conditionKey, summary.generated_at);

            if (result.queued) {
                setQueuedNotice(t('player_panel.queued_notice'));
            } else {
                setQueuedNotice(null);
            }
        } catch (error) {
            console.error('Unable to record acknowledgement', error);
        } finally {
            setPendingAcknowledgements((current) => {
                const next = { ...current };
                delete next[composite];
                return next;
            });
        }
    };

    useEffect(() => {
        const stale = stalenessMs(summary.generated_at);

        recordAnalyticsEventSync({
            key: 'timer_summary.viewed',
            groupId: summary.group_id,
            payload: {
                group_id: summary.group_id,
                user_role: viewerRole ?? 'member',
                source,
                entries_count: entries.length,
                staleness_ms: stale ?? null,
            },
        });
    }, [entries.length, source, stalenessMs, summary.generated_at, summary.group_id, viewerRole]);

    const pendingCopy = pendingCount > 0 ? t('player_panel.offline.pending', undefined, { count: pendingCount }) : null;

    return (
        <section
            className={cn(
                'rounded-xl border border-zinc-800 bg-zinc-950/60 p-6 shadow-inner shadow-black/40',
                className,
            )}
        >
            <header className="mb-4 flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                <div className="space-y-1">
                    <h2 className="text-lg font-semibold text-zinc-100">{t('player_panel.title')}</h2>
                    <p className="text-xs text-zinc-500">{t('player_panel.description')}</p>
                </div>
                {onDismiss && (
                    <button
                        type="button"
                        onClick={onDismiss}
                        className="self-start rounded-md border border-zinc-700 px-3 py-1 text-xs font-medium text-zinc-300 transition hover:border-zinc-500 hover:text-zinc-100"
                    >
                        {t('player_panel.hide')}
                    </button>
                )}
            </header>

            {(isOffline || pendingCount > 0 || syncing || queuedNotice) && (
                <div
                    className="mb-4 rounded-lg border border-amber-500/30 bg-amber-500/10 p-3 text-xs text-amber-200"
                    role="status"
                    aria-live="polite"
                >
                    <div className="flex items-start gap-2">
                        <WifiOff className="mt-0.5 h-4 w-4" aria-hidden />
                        <div className="space-y-1">
                            <p className="font-medium">{t('player_panel.offline.title')}</p>
                            <p>
                                {isOffline
                                    ? t('player_panel.offline.offline')
                                    : syncing
                                      ? t('player_panel.offline.syncing')
                                      : t('player_panel.offline.queued')}
                            </p>
                            {(pendingCopy || queuedNotice) && (
                                <p className="text-[11px] uppercase tracking-wide text-amber-300">
                                    {pendingCopy}
                                    {queuedNotice ? ` • ${queuedNotice}` : ''}
                                </p>
                            )}
                        </div>
                    </div>
                </div>
            )}

            {conflicts.length > 0 && (
                <div className="mb-4 rounded-lg border border-rose-500/30 bg-rose-500/10 p-3 text-xs text-rose-200">
                    <div className="flex items-start gap-2">
                        <AlertTriangle className="mt-0.5 h-4 w-4" aria-hidden />
                        <div className="space-y-1">
                            <p className="font-semibold">{t('player_panel.conflict.title')}</p>
                            <p>{t('player_panel.conflict.description')}</p>
                            <ul className="space-y-1">
                                {conflictDetails.map(({ id, label }) => (
                                    <li key={id} className="flex items-center justify-between gap-3">
                                        <span>{label}</span>
                                        <button
                                            type="button"
                                            className="rounded border border-rose-400/40 px-2 py-0.5 text-[11px] uppercase tracking-wide text-rose-100 hover:border-rose-300"
                                            onClick={() => resolveConflict(id)}
                                        >
                                            {t('player_panel.conflict.dismiss')}
                                        </button>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    </div>
                </div>
            )}

            {entries.length === 0 ? (
                <p className="text-sm text-zinc-500">{t('player_panel.empty')}</p>
            ) : (
                <div className="space-y-4">
                    {entries.map((entry) => (
                        <article
                            key={`${entry.token.id}-${entry.conditions.map((condition) => condition.key).join('.')}`}
                            className="rounded-lg border border-zinc-800/60 bg-zinc-950/70 p-4"
                        >
                            <div className="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p className="text-base font-semibold text-zinc-100">{entry.token.label}</p>
                                    <p className="text-xs text-zinc-500">
                                        {entry.map.title ?? t('player_panel.token.unmapped')} • {dispositionLabel(entry.token.disposition)}
                                        {entry.token.visibility === 'obscured' && t('player_panel.token.obscured_suffix')}
                                    </p>
                                </div>
                                <span className="text-xs uppercase tracking-wide text-zinc-500">
                                    {effectsLabel(entry.conditions.length)}
                                </span>
                            </div>

                            <ul className="mt-3 space-y-3">
                                {entry.conditions.map((condition) => {
                                    const compositeKey = `${entry.token.id}:${condition.key}`;
                                    const isAcknowledged = Boolean(condition.acknowledged_by_viewer);
                                    const isPendingImmediate = Boolean(pendingAcknowledgements[compositeKey]);
                                    const isQueued = pendingItems.some(
                                        (item) =>
                                            item.tokenId === entry.token.id && item.conditionKey === condition.key,
                                    );
                                    const isPending = isPendingImmediate || isQueued;
                                    const canAcknowledge = acknowledgementsEnabled && !isAcknowledged && !isPending;

                                    const aggregateCount =
                                        typeof condition.acknowledged_count === 'number'
                                            ? condition.acknowledged_count
                                            : null;

                                    return (
                                        <li
                                            key={condition.key}
                                            className="rounded-lg border border-zinc-800/40 bg-zinc-950/80 p-3"
                                        >
                                            <div className="flex flex-wrap items-center justify-between gap-2">
                                                <div className="flex items-center gap-2">
                                                    <span
                                                        className={cn(
                                                            'rounded-full border px-2 py-0.5 text-xs uppercase tracking-wide',
                                                            urgencyStyles[condition.urgency],
                                                        )}
                                                    >
                                                        {condition.label}
                                                    </span>
                                                </div>
                                                <span className="text-xs text-zinc-400">{roundsDisplay(condition)}</span>
                                            </div>
                                            <p className="mt-2 text-sm text-zinc-300">{condition.summary}</p>
                                            <div className="mt-3 flex flex-wrap items-center justify-between gap-2">
                                                <button
                                                    type="button"
                                                    onClick={() => acknowledgeCondition(entry.token.id, condition.key)}
                                                    disabled={!canAcknowledge}
                                                    className={cn(
                                                        'inline-flex items-center gap-2 rounded-md border px-3 py-1 text-xs font-semibold transition',
                                                        isAcknowledged
                                                            ? 'border-emerald-500/40 bg-emerald-500/10 text-emerald-200'
                                                            : 'border-zinc-700 text-zinc-300 hover:border-emerald-500/50 hover:text-emerald-200',
                                                        isPending && 'opacity-70',
                                                    )}
                                                >
                                                    {isAcknowledged ? (
                                                        <CheckCircle2 className="h-4 w-4" />
                                                    ) : (
                                                        <Circle className="h-4 w-4" />
                                                    )}
                                                    {isAcknowledged
                                                        ? t('player_panel.acknowledge_button.reviewed')
                                                        : isQueued
                                                          ? t('player_panel.acknowledge_button.sync_when_online')
                                                          : isPending
                                                            ? t('player_panel.acknowledge_button.marking')
                                                            : t('player_panel.acknowledge_button.mark')}
                                                </button>
                                                {viewerIsFacilitator && aggregateCount !== null && (
                                                    <span className="inline-flex items-center gap-1 text-xs text-zinc-400">
                                                        <Users2 className="h-3.5 w-3.5" />
                                                        {t('player_panel.acknowledgements', undefined, {
                                                            count: aggregateCount,
                                                        })}
                                                    </span>
                                                )}
                                            </div>
                                            {condition.timeline && condition.timeline.length > 0 && (
                                                <div className="mt-3 space-y-2">
                                                    <div className="flex items-center gap-2 text-[11px] uppercase tracking-wide text-zinc-500">
                                                        <History className="h-3 w-3" aria-hidden />
                                                        <span>{t('player_panel.timeline.title')}</span>
                                                    </div>
                                                    <ol className="space-y-2">
                                                        {condition.timeline.map((event) => (
                                                            <li key={event.id} className="flex items-start gap-2">
                                                                <span className="mt-1 h-2 w-2 flex-shrink-0 rounded-full bg-zinc-700" aria-hidden />
                                                                <div className="space-y-1">
                                                                    <p className="text-xs text-zinc-300">{event.summary}</p>
                                                                    {event.detail?.summary && (
                                                                        <p className="text-[11px] text-zinc-500">{event.detail.summary}</p>
                                                                    )}
                                                                    <p className="text-[11px] text-zinc-600">
                                                                        {formatRelativeTimestamp(event.recorded_at)}
                                                                    </p>
                                                                </div>
                                                            </li>
                                                        ))}
                                                    </ol>
                                                </div>
                                            )}
                                        </li>
                                    );
                                })}
                            </ul>
                        </article>
                    ))}
                </div>
            )}

            <footer className="mt-6 flex flex-wrap items-center justify-between gap-3 text-xs text-zinc-500">
                <span>{t('player_panel.footer.updated', undefined, { timestamp: formatTimestamp(summary.generated_at) })}</span>
                {shareUrl && (
                    <Link
                        href={shareUrl}
                        target="_blank"
                        rel="noreferrer"
                        className="text-amber-300 underline decoration-dotted underline-offset-4 hover:text-amber-200"
                    >
                        {t('player_panel.footer.open_share')}
                    </Link>
                )}
            </footer>
        </section>
    );
}

export default PlayerConditionTimerSummaryPanel;
