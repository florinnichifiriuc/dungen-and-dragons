import { useMemo, useState } from 'react';

import { Head, Link, usePage } from '@inertiajs/react';

import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';

type FilterOption = {
    label: string;
    value: string | null;
};

type Filters = {
    urgency: string | null;
    faction: string | null;
    options: {
        urgency: FilterOption[];
        faction: FilterOption[];
    };
};

type ConditionInsight = {
    map: { id: number; title: string } | null;
    token: { id: number | null; label: string; disposition: string; visibility: string };
    condition: {
        key: string | null;
        label: string | null;
        urgency: string | null;
        summary: string | null;
        rounds: number | null;
        rounds_hint: string | null;
    };
    acknowledged_count: number;
    acknowledged_by_viewer: boolean;
};

type RepeatOffender = {
    token: {
        id: number | null;
        label: string;
        disposition: string;
        map: { id: number; title: string } | null;
    };
    adjustments_count: number;
    recent_conditions: { condition: string; count: number }[];
};

type AtRiskPlayer = {
    token: { id: number | null; label: string; disposition: string; visibility: string } | null;
    conditions: ConditionInsight['condition'][];
};

type Metrics = {
    total_active: number;
    acknowledged: number;
    unacknowledged: number;
    critical_unacknowledged: number;
    warning_unacknowledged: number;
    average_acknowledgement_minutes: number | null;
};

type InsightsPayload = {
    generated_at: string;
    filters: { urgency: string | null; faction: string | null };
    metrics: Metrics;
    conditions: ConditionInsight[];
    repeat_offenders: RepeatOffender[];
    at_risk_players: AtRiskPlayer[];
    exports: { markdown: string };
};

type CampaignSummary = { id: number; title: string };

type InsightsPageProps = {
    campaign: CampaignSummary;
    filters: Filters;
    insights: InsightsPayload;
};

const urgencyBadgeStyles: Record<string, string> = {
    critical: 'bg-rose-500/20 text-rose-200 border border-rose-500/40',
    warning: 'bg-amber-500/20 text-amber-100 border border-amber-500/40',
    calm: 'bg-emerald-500/10 text-emerald-200 border border-emerald-500/40',
};

const dispositionStyles: Record<string, string> = {
    ally: 'text-emerald-300',
    adversary: 'text-rose-300',
    neutral: 'text-sky-300',
    hazard: 'text-amber-200',
    unknown: 'text-zinc-300',
};

export default function Insights() {
    const { campaign, filters, insights } = usePage<InsightsPageProps>().props;
    const [copied, setCopied] = useState(false);

    const metricsCards = useMemo(
        () => [
            {
                label: 'Active timers tracked',
                value: insights.metrics.total_active ?? 0,
            },
            {
                label: 'Acknowledged timers',
                value: insights.metrics.acknowledged ?? 0,
            },
            {
                label: 'Unacknowledged timers',
                value: insights.metrics.unacknowledged ?? 0,
            },
            {
                label: 'Critical unacknowledged',
                value: insights.metrics.critical_unacknowledged ?? 0,
            },
            {
                label: 'Warning unacknowledged',
                value: insights.metrics.warning_unacknowledged ?? 0,
            },
            {
                label: 'Avg acknowledgement (14d)',
                value:
                    insights.metrics.average_acknowledgement_minutes !== null
                        ? `${Number(insights.metrics.average_acknowledgement_minutes).toFixed(2)} min`
                        : '—',
            },
        ],
        [insights.metrics],
    );

    const handleCopyMarkdown = async () => {
        try {
            await navigator.clipboard.writeText(insights.exports.markdown);
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        } catch (error) {
            console.error('Failed to copy facilitator insights markdown export', error);
        }
    };

    return (
        <AppLayout>
            <Head title={`${campaign.title} · Facilitator insights`} />
            <div className="space-y-6">
                <header className="space-y-2">
                    <h1 className="text-3xl font-semibold text-zinc-100">{campaign.title} — Facilitator insights</h1>
                    <p className="text-sm text-zinc-400">
                        Generated at {new Date(insights.generated_at).toLocaleString()} (UTC). Use the filters below to focus on the
                        timers needing the most attention.
                    </p>
                </header>

                <form
                    method="get"
                    className="flex flex-wrap items-end gap-4 rounded-xl border border-zinc-700/60 bg-zinc-900/60 p-4 text-sm text-zinc-300"
                >
                    <div className="flex flex-col gap-1">
                        <label htmlFor="urgency" className="text-xs uppercase tracking-wide text-zinc-400">
                            Urgency filter
                        </label>
                        <select
                            id="urgency"
                            name="urgency"
                            defaultValue={filters.urgency ?? ''}
                            className="rounded-md border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-zinc-100 focus:border-indigo-400 focus:outline-none"
                        >
                            {filters.options.urgency.map((option) => (
                                <option key={`${option.label}-${option.value ?? 'all'}`} value={option.value ?? ''}>
                                    {option.label}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="flex flex-col gap-1">
                        <label htmlFor="faction" className="text-xs uppercase tracking-wide text-zinc-400">
                            Disposition filter
                        </label>
                        <select
                            id="faction"
                            name="faction"
                            defaultValue={filters.faction ?? ''}
                            className="rounded-md border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-zinc-100 focus:border-indigo-400 focus:outline-none"
                        >
                            {filters.options.faction.map((option) => (
                                <option key={`${option.label}-${option.value ?? 'all'}`} value={option.value ?? ''}>
                                    {option.label}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="flex items-center gap-3">
                        <Button type="submit" variant="default">
                            Apply filters
                        </Button>
                        {(filters.urgency || filters.faction) && (
                            <Link
                                href={route('campaigns.insights.show', campaign.id)}
                                className="text-xs font-medium uppercase tracking-wide text-indigo-300 hover:text-indigo-200"
                            >
                                Clear
                            </Link>
                        )}
                    </div>
                </form>

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    {metricsCards.map((metric) => (
                        <div
                            key={metric.label}
                            className="rounded-xl border border-zinc-700/60 bg-zinc-900/60 p-4 shadow-sm shadow-black/20"
                        >
                            <p className="text-xs uppercase tracking-wide text-zinc-400">{metric.label}</p>
                            <p className="mt-2 text-2xl font-semibold text-zinc-100">{metric.value}</p>
                        </div>
                    ))}
                </section>

                <section className="grid gap-6 lg:grid-cols-2">
                    <div className="space-y-4 rounded-xl border border-zinc-700/60 bg-zinc-900/60 p-5">
                        <div className="flex items-center justify-between">
                            <h2 className="text-lg font-semibold text-zinc-100">At-risk allies</h2>
                            <span className="text-xs text-zinc-400">
                                {insights.at_risk_players.length} tracked
                            </span>
                        </div>
                        {insights.at_risk_players.length === 0 ? (
                            <p className="text-sm text-zinc-400">
                                All allied timers are acknowledged or stable within the selected filters.
                            </p>
                        ) : (
                            <ul className="space-y-3 text-sm text-zinc-200">
                                {insights.at_risk_players.map((entry, index) => (
                                    <li
                                        key={`risk-${entry.token?.id ?? `${entry.token?.label ?? 'ally'}-${index}`}`}
                                        className="space-y-1"
                                    >
                                        <p className="font-medium text-emerald-200">{entry.token?.label ?? 'Unknown ally'}</p>
                                        <ul className="ml-3 space-y-1 text-xs text-zinc-300">
                                            {entry.conditions.map((condition) => (
                                                <li key={`${entry.token?.id}-${condition.key}`}>{condition.label ?? condition.key}</li>
                                            ))}
                                        </ul>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>

                    <div className="space-y-4 rounded-xl border border-zinc-700/60 bg-zinc-900/60 p-5">
                        <div className="flex items-center justify-between">
                            <h2 className="text-lg font-semibold text-zinc-100">Repeat offenders (7d)</h2>
                            <span className="text-xs text-zinc-400">
                                {insights.repeat_offenders.length} tracked
                            </span>
                        </div>
                        {insights.repeat_offenders.length === 0 ? (
                            <p className="text-sm text-zinc-400">No tokens have required three or more adjustments this week.</p>
                        ) : (
                            <ul className="space-y-3 text-sm text-zinc-200">
                                {insights.repeat_offenders.map((offender, index) => (
                                    <li
                                        key={`offender-${offender.token.id ?? `${offender.token.label}-${index}`}`}
                                        className="space-y-1"
                                    >
                                        <div className="flex items-center justify-between gap-3">
                                            <p className="font-medium text-zinc-100">{offender.token.label}</p>
                                            <span className="text-xs text-zinc-400">
                                                {offender.adjustments_count} adjustments
                                            </span>
                                        </div>
                                        <p className="text-xs text-zinc-400">
                                            Map: {offender.token.map?.title ?? 'Unknown map'} —{' '}
                                            <span className={dispositionStyles[offender.token.disposition] ?? 'text-zinc-300'}>
                                                {offender.token.disposition}
                                            </span>
                                        </p>
                                        {offender.recent_conditions.length > 0 && (
                                            <ul className="ml-3 list-disc space-y-0.5 text-xs text-zinc-300">
                                                {offender.recent_conditions.map((condition) => (
                                                    <li key={`${offender.token.id}-${condition.condition}`}>
                                                        {condition.condition} ×{condition.count}
                                                    </li>
                                                ))}
                                            </ul>
                                        )}
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>
                </section>

                <section className="space-y-4">
                    <div className="flex items-center justify-between">
                        <h2 className="text-lg font-semibold text-zinc-100">Timer conditions</h2>
                        <span className="text-xs text-zinc-400">{insights.conditions.length} matching entries</span>
                    </div>
                    {insights.conditions.length === 0 ? (
                        <p className="rounded-lg border border-dashed border-zinc-700/60 bg-zinc-900/40 p-6 text-sm text-zinc-400">
                            No condition timers match the current filters.
                        </p>
                    ) : (
                        <div className="grid gap-4 md:grid-cols-2">
                            {insights.conditions.map((entry, index) => {
                                const urgency = entry.condition.urgency ?? 'calm';
                                const urgencyClass = urgencyBadgeStyles[urgency] ?? urgencyBadgeStyles.calm;
                                const dispositionClass = dispositionStyles[entry.token.disposition] ?? 'text-zinc-300';

                                return (
                                    <article
                                        key={`condition-${entry.token.id ?? entry.token.label ?? 'token'}-${
                                            entry.condition.key ?? index
                                        }`}
                                        className="flex flex-col gap-3 rounded-xl border border-zinc-700/60 bg-zinc-900/60 p-5"
                                    >
                                        <div className="flex items-start justify-between gap-3">
                                            <div>
                                                <p className="text-sm font-semibold text-zinc-100">{entry.token.label}</p>
                                                <p className={`text-xs ${dispositionClass}`}>
                                                    {entry.token.disposition} · {entry.token.visibility}
                                                </p>
                                                <p className="text-xs text-zinc-400">
                                                    {entry.map?.title ?? 'Unknown map'}
                                                </p>
                                            </div>
                                            <span className={`rounded-full px-3 py-1 text-xs font-medium uppercase ${urgencyClass}`}>
                                                {entry.condition.urgency}
                                            </span>
                                        </div>
                                        <div className="space-y-2 text-sm text-zinc-200">
                                            <p className="font-medium">{entry.condition.label ?? entry.condition.key}</p>
                                            {entry.condition.summary && <p className="text-xs text-zinc-300">{entry.condition.summary}</p>}
                                            <p className="text-xs text-zinc-400">
                                                Rounds:{' '}
                                                {entry.condition.rounds !== null
                                                    ? entry.condition.rounds
                                                    : entry.condition.rounds_hint ?? 'Unknown'}
                                            </p>
                                        </div>
                                        <div className="mt-auto flex items-center justify-between text-xs text-zinc-300">
                                            <p>
                                                Acknowledged: <span className="font-semibold">{entry.acknowledged_count}</span>
                                            </p>
                                            {entry.acknowledged_by_viewer ? (
                                                <span className="text-emerald-300">You acknowledged this timer</span>
                                            ) : (
                                                <span className="text-zinc-500">Awaiting your nudge</span>
                                            )}
                                        </div>
                                    </article>
                                );
                            })}
                        </div>
                    )}
                </section>

                <section className="space-y-3 rounded-xl border border-zinc-700/60 bg-zinc-900/60 p-5">
                    <div className="flex items-center justify-between">
                        <div>
                            <h2 className="text-lg font-semibold text-zinc-100">Export summary</h2>
                            <p className="text-xs text-zinc-400">Copy Markdown for standups or archival tools.</p>
                        </div>
                        <Button type="button" variant="outline" onClick={handleCopyMarkdown}>
                            {copied ? 'Copied!' : 'Copy Markdown'}
                        </Button>
                    </div>
                    <Textarea value={insights.exports.markdown} readOnly className="min-h-[200px]" />
                </section>
            </div>
        </AppLayout>
    );
}
