import { Link } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import { CheckCircle2, Circle, History, Users2 } from 'lucide-react';

import { cn } from '@/lib/utils';
import { recordAnalyticsEventSync } from '@/lib/analytics';
import {
    applyAcknowledgementToSummary,
    type ConditionAcknowledgementPayload,
} from '@/lib/conditionAcknowledgements';

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

const dispositionCopy: Record<ConditionTimerSummaryEntry['token']['disposition'], string> = {
    ally: 'Ally',
    neutral: 'Neutral force',
    hazard: 'Environmental hazard',
    adversary: 'Adversary',
    unknown: 'Veiled presence',
};

function formatTimestamp(timestamp: string): string {
    try {
        return new Intl.DateTimeFormat('en-US', {
            dateStyle: 'medium',
            timeStyle: 'short',
        }).format(new Date(timestamp));
    } catch (error) {
        return timestamp;
    }
}

function roundsDisplay(condition: ConditionTimerSummaryCondition): string {
    if (condition.rounds !== null) {
        const value = condition.rounds;
        return `${value} round${value === 1 ? '' : 's'} remaining`;
    }

    if (condition.rounds_hint) {
        return condition.rounds_hint;
    }

    return 'Lingering effect';
}

function stalenessMs(timestamp: string): number | null {
    const parsed = Date.parse(timestamp);

    if (Number.isNaN(parsed)) {
        return null;
    }

    const diff = Date.now() - parsed;

    return diff < 0 ? 0 : diff;
}

function formatRelativeTimestamp(timestamp: string): string {
    const parsed = Date.parse(timestamp);

    if (Number.isNaN(parsed)) {
        return timestamp;
    }

    const diffMilliseconds = Date.now() - parsed;
    const diffSeconds = Math.round(diffMilliseconds / 1000);
    const formatter = new Intl.RelativeTimeFormat('en-US', { numeric: 'auto' });

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
}

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
    const entries = useMemo(() => summary.entries ?? [], [summary.entries]);
    const [pendingAcknowledgements, setPendingAcknowledgements] = useState<Record<string, boolean>>({});
    const viewerIsFacilitator = viewerRole === 'owner' || viewerRole === 'dungeon-master';
    const acknowledgementsEnabled = allowAcknowledgements ?? true;

    const acknowledgeCondition = async (tokenId: number, conditionKey: string) => {
        if (!summary.generated_at || !acknowledgementsEnabled) {
            return;
        }

        const composite = `${tokenId}:${conditionKey}`;

        setPendingAcknowledgements((current) => ({ ...current, [composite]: true }));

        try {
            const csrf =
                (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content ?? undefined;

            const response = await fetch(
                route('groups.condition-timers.acknowledgements.store', summary.group_id),
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
                        summary_generated_at: summary.generated_at,
                    }),
                },
            );

            if (!response.ok) {
                throw new Error(`Failed with status ${response.status}`);
            }

            const payload = (await response.json()) as { acknowledgement?: ConditionAcknowledgementPayload };

            if (payload.acknowledgement && onSummaryUpdate) {
                onSummaryUpdate(applyAcknowledgementToSummary(summary, payload.acknowledgement));
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
    }, [entries.length, source, summary.generated_at, summary.group_id, viewerRole]);

    return (
        <section
            className={cn(
                'rounded-xl border border-zinc-800 bg-zinc-950/60 p-6 shadow-inner shadow-black/40',
                className,
            )}
        >
            <header className="mb-4 flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                <div className="space-y-1">
                    <h2 className="text-lg font-semibold text-zinc-100">Active condition outlook</h2>
                    <p className="text-xs text-zinc-500">
                        A player-safe glimpse at lingering effects and their urgency. Updates arrive in real time.
                    </p>
                </div>
                {onDismiss && (
                    <button
                        type="button"
                        onClick={onDismiss}
                        className="self-start rounded-md border border-zinc-700 px-3 py-1 text-xs font-medium text-zinc-300 transition hover:border-zinc-500 hover:text-zinc-100"
                    >
                        Hide summary
                    </button>
                )}
            </header>

            {entries.length === 0 ? (
                <p className="text-sm text-zinc-500">
                    All clear for now—no active timers are visible to the party.
                </p>
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
                                        {entry.map.title ?? 'Unmapped battleground'} • {dispositionCopy[entry.token.disposition]}
                                        {entry.token.visibility === 'obscured' && ' (obscured)'}
                                    </p>
                                </div>
                                <span className="text-xs uppercase tracking-wide text-zinc-500">
                                    {entry.conditions.length}{' '}
                                    {entry.conditions.length === 1 ? 'effect' : 'effects'}
                                </span>
                            </div>

                            <ul className="mt-3 space-y-3">
                                {entry.conditions.map((condition) => {
                                    const compositeKey = `${entry.token.id}:${condition.key}`;
                                    const isAcknowledged = Boolean(condition.acknowledged_by_viewer);
                                    const isPending = Boolean(pendingAcknowledgements[compositeKey]);
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
                                                    {isAcknowledged ? 'Reviewed' : isPending ? 'Marking…' : 'Mark reviewed'}
                                                </button>
                                                {viewerIsFacilitator && aggregateCount !== null && (
                                                    <span className="inline-flex items-center gap-1 text-xs text-zinc-400">
                                                        <Users2 className="h-3.5 w-3.5" />
                                                        {aggregateCount === 1
                                                            ? '1 acknowledgement'
                                                            : `${aggregateCount} acknowledgements`}
                                                    </span>
                                                )}
                                            </div>
                                            {condition.timeline && condition.timeline.length > 0 && (
                                                <div className="mt-3 space-y-2">
                                                    <div className="flex items-center gap-2 text-[11px] uppercase tracking-wide text-zinc-500">
                                                        <History className="h-3 w-3" aria-hidden />
                                                        <span>Adjustment chronicle</span>
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
                <span>Last updated {formatTimestamp(summary.generated_at)}</span>
                {shareUrl && (
                    <Link
                        href={shareUrl}
                        target="_blank"
                        rel="noreferrer"
                        className="text-amber-300 underline decoration-dotted underline-offset-4 hover:text-amber-200"
                    >
                        Open shareable view
                    </Link>
                )}
            </footer>
        </section>
    );
}

export default PlayerConditionTimerSummaryPanel;
