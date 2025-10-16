import { Link } from '@inertiajs/react';
import { AlertTriangle, ListChecks, WifiOff } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

import { cn } from '@/lib/utils';
import { recordAnalyticsEventSync } from '@/lib/analytics';

import type { ConditionTimerSummaryResource } from './PlayerConditionTimerSummaryPanel';

type MobileConditionTimerRecapWidgetProps = {
    summary: ConditionTimerSummaryResource;
    shareUrl?: string;
    className?: string;
    source: string;
    viewerRole?: string | null;
    onDismiss?: () => void;
};

type RecapEntry = {
    id: string;
    tokenLabel: string;
    mapTitle: string;
    urgency: 'calm' | 'warning' | 'critical';
    summary: string;
    roundsLabel: string;
};

const urgencyRank: Record<RecapEntry['urgency'], number> = {
    critical: 0,
    warning: 1,
    calm: 2,
};

const urgencyBadgeStyles: Record<RecapEntry['urgency'], string> = {
    critical: 'bg-rose-500/15 text-rose-200 border border-rose-500/40',
    warning: 'bg-amber-500/15 text-amber-200 border border-amber-500/40',
    calm: 'bg-emerald-500/15 text-emerald-200 border border-emerald-500/40',
};

const roundsLabel = (rounds: number | null, hint: string | null): string => {
    if (rounds !== null) {
        return `${rounds} round${rounds === 1 ? '' : 's'} left`;
    }

    if (hint) {
        return hint;
    }

    return 'Lingering effect';
};

const relativeUpdatedCopy = (timestamp: string | null | undefined): string => {
    if (!timestamp) {
        return 'Updated moments ago';
    }

    const parsed = Date.parse(timestamp);

    if (Number.isNaN(parsed)) {
        return 'Updated moments ago';
    }

    const diffMilliseconds = Date.now() - parsed;
    const diffSeconds = Math.round(diffMilliseconds / 1000);
    const absoluteSeconds = Math.abs(diffSeconds);
    const formatter = new Intl.RelativeTimeFormat('en-US', { numeric: 'auto' });

    if (absoluteSeconds < 60) {
        return `Updated ${formatter.format(-diffSeconds, 'second')}`;
    }

    const diffMinutes = Math.round(diffSeconds / 60);

    if (Math.abs(diffMinutes) < 60) {
        return `Updated ${formatter.format(-diffMinutes, 'minute')}`;
    }

    const diffHours = Math.round(diffMinutes / 60);

    if (Math.abs(diffHours) < 24) {
        return `Updated ${formatter.format(-diffHours, 'hour')}`;
    }

    const diffDays = Math.round(diffHours / 24);

    return `Updated ${formatter.format(-diffDays, 'day')}`;
};

export function MobileConditionTimerRecapWidget({
    summary,
    shareUrl,
    className,
    source,
    viewerRole,
    onDismiss,
}: MobileConditionTimerRecapWidgetProps) {
    const [isOffline, setIsOffline] = useState<boolean>(() => {
        if (typeof navigator === 'undefined') {
            return false;
        }

        return !navigator.onLine;
    });

    useEffect(() => {
        if (typeof window === 'undefined') {
            return;
        }

        const handleOnline = () => setIsOffline(false);
        const handleOffline = () => setIsOffline(true);

        window.addEventListener('online', handleOnline);
        window.addEventListener('offline', handleOffline);

        return () => {
            window.removeEventListener('online', handleOnline);
            window.removeEventListener('offline', handleOffline);
        };
    }, []);

    const highlightedEntries = useMemo<RecapEntry[]>(() => {
        const entries = summary?.entries ?? [];

        const flattened: RecapEntry[] = entries.flatMap((entry) =>
            entry.conditions.map((condition) => ({
                id: `${entry.token.id}:${condition.key}`,
                tokenLabel: entry.token.label,
                mapTitle: entry.map.title ?? 'Unmapped battleground',
                urgency: condition.urgency,
                summary: condition.summary,
                roundsLabel: roundsLabel(condition.rounds, condition.rounds_hint),
            })),
        );

        flattened.sort((a, b) => {
            const urgencyComparison = urgencyRank[a.urgency] - urgencyRank[b.urgency];

            if (urgencyComparison !== 0) {
                return urgencyComparison;
            }

            return a.tokenLabel.localeCompare(b.tokenLabel);
        });

        return flattened.slice(0, 3);
    }, [summary]);

    const hasEntries = highlightedEntries.length > 0;

    useEffect(() => {
        const parsed = summary.generated_at ? Date.parse(summary.generated_at) : NaN;
        const stale = Number.isNaN(parsed) ? null : Math.max(0, Date.now() - parsed);

        recordAnalyticsEventSync({
            key: 'timer_summary.viewed',
            groupId: summary.group_id,
            payload: {
                group_id: summary.group_id,
                user_role: viewerRole ?? 'member',
                source,
                entries_count: summary.entries?.length ?? 0,
                staleness_ms: stale ?? null,
            },
        });
    }, [source, summary.entries, summary.generated_at, summary.group_id, viewerRole]);

    return (
        <section
            className={cn(
                'rounded-xl border border-zinc-800/70 bg-zinc-950/70 p-4 shadow-inner shadow-black/30',
                'flex flex-col gap-3 text-sm text-zinc-200',
                className,
            )}
            aria-label="Mobile condition timer recap"
        >
            <header className="flex items-center justify-between gap-3">
                <div className="flex items-center gap-2 text-zinc-100">
                    <ListChecks className="h-5 w-5 text-amber-300" aria-hidden />
                    <span className="text-base font-semibold">Condition recap</span>
                </div>
                <div className="flex items-center gap-2">
                    {isOffline && (
                        <span className="flex items-center gap-1 text-xs text-amber-300" title="Offline mode">
                            <WifiOff className="h-4 w-4" aria-hidden /> Offline
                        </span>
                    )}
                    {onDismiss && (
                        <button
                            type="button"
                            onClick={onDismiss}
                            className="rounded-md border border-zinc-700 px-2 py-0.5 text-[11px] font-medium text-zinc-300 transition hover:border-zinc-500 hover:text-zinc-100"
                        >
                            Hide
                        </button>
                    )}
                </div>
            </header>

            {hasEntries ? (
                <ul className="space-y-3">
                    {highlightedEntries.map((entry) => (
                        <li
                            key={entry.id}
                            className="rounded-lg border border-zinc-800/60 bg-zinc-950/90 p-3"
                        >
                            <div className="flex items-center justify-between gap-2">
                                <span
                                    className={cn(
                                        'rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide',
                                        urgencyBadgeStyles[entry.urgency],
                                    )}
                                >
                                    {entry.urgency === 'critical'
                                        ? 'Critical'
                                        : entry.urgency === 'warning'
                                        ? 'Warning'
                                        : 'Calm'}
                                </span>
                                <span className="text-[11px] text-zinc-400">{entry.roundsLabel}</span>
                            </div>
                            <div className="mt-2 text-xs text-zinc-400">
                                {entry.mapTitle} • {entry.tokenLabel}
                            </div>
                            <p className="mt-2 text-sm text-zinc-200 line-clamp-3">{entry.summary}</p>
                        </li>
                    ))}
                </ul>
            ) : (
                <div className="flex items-center gap-2 rounded-lg border border-emerald-500/40 bg-emerald-500/10 p-3 text-emerald-100">
                    <AlertTriangle className="h-4 w-4" aria-hidden />
                    <span>No visible conditions right now. Enjoy the calm!</span>
                </div>
            )}

            <footer className="flex flex-wrap items-center justify-between gap-3 text-xs text-zinc-500">
                <span>{relativeUpdatedCopy(summary?.generated_at)}</span>
                {shareUrl && (
                    <Link
                        href={shareUrl}
                        className="text-amber-300 underline decoration-dotted underline-offset-4 hover:text-amber-200"
                    >
                        Open full summary
                    </Link>
                )}
            </footer>
        </section>
    );
}

export default MobileConditionTimerRecapWidget;
