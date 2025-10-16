import { Link } from '@inertiajs/react';
import { useMemo } from 'react';

import { cn } from '@/lib/utils';

export type ConditionTimerSummaryCondition = {
    key: string;
    label: string;
    rounds: number | null;
    rounds_hint: string | null;
    urgency: 'calm' | 'warning' | 'critical';
    summary: string;
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

type PlayerConditionTimerSummaryPanelProps = {
    summary: ConditionTimerSummaryResource;
    shareUrl?: string;
    className?: string;
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

export function PlayerConditionTimerSummaryPanel({
    summary,
    shareUrl,
    className,
}: PlayerConditionTimerSummaryPanelProps) {
    const entries = useMemo(() => summary.entries ?? [], [summary.entries]);

    return (
        <section
            className={cn(
                'rounded-xl border border-zinc-800 bg-zinc-950/60 p-6 shadow-inner shadow-black/40',
                className,
            )}
        >
            <header className="mb-4 flex flex-col gap-1">
                <h2 className="text-lg font-semibold text-zinc-100">Active condition outlook</h2>
                <p className="text-xs text-zinc-500">
                    A player-safe glimpse at lingering effects and their urgency. Updates arrive in real time.
                </p>
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
                                {entry.conditions.map((condition) => (
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
                                    </li>
                                ))}
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
