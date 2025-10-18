import { Head, Link, router } from '@inertiajs/react';
import dayjs from 'dayjs';
import relativeTime from 'dayjs/plugin/relativeTime';
import utc from 'dayjs/plugin/utc';
import { FormEvent, useMemo, useState } from 'react';

import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

dayjs.extend(utc);
dayjs.extend(relativeTime);

import type { BugReportSummary } from '@/types/bugReports';

type BugReportListItem = Pick<
    BugReportSummary,
    'id' | 'reference' | 'summary' | 'priority' | 'status' | 'group' | 'assignee'
> & {
    updated_at?: string | null;
};

type BugReportIndexPageProps = {
    filters: {
        status?: string | null;
        priority?: string | null;
        search?: string | null;
        timeframe?: string | null;
    };
    reports: {
        data: BugReportListItem[];
        links: { url: string | null; label: string; active: boolean }[];
    };
    counts: {
        status: Record<string, number>;
        priority: Record<string, number>;
    };
    analytics: {
        volume: {
            series: { day: string; count: number }[];
            current_total: number;
            previous_total: number;
            delta_percentage: number | null;
        };
        resolution: {
            average_hours: number | null;
            median_hours: number | null;
            p90_hours: number | null;
        };
        categories: {
            top: { tag: string; count: number }[];
            total: number;
        };
    };
};

const priorityTone: Record<string, string> = {
    low: 'bg-emerald-500/10 text-emerald-200 border-emerald-500/40',
    normal: 'bg-sky-500/10 text-sky-200 border-sky-500/40',
    high: 'bg-amber-500/10 text-amber-200 border-amber-500/40',
    critical: 'bg-rose-500/10 text-rose-100 border-rose-500/40',
};

const statusTone: Record<string, string> = {
    open: 'text-emerald-200',
    in_progress: 'text-sky-200',
    resolved: 'text-amber-200',
    closed: 'text-zinc-300',
};

export default function AdminBugReportIndexPage({ filters, reports, counts, analytics }: BugReportIndexPageProps) {
    const [form, setForm] = useState({
        status: filters.status ?? '',
        priority: filters.priority ?? '',
        search: filters.search ?? '',
        timeframe: filters.timeframe ?? '',
    });

    const submitFilters = (event?: FormEvent<HTMLFormElement>) => {
        event?.preventDefault();

        router.get(
            route('admin.bug-reports.index'),
            {
                status: form.status || undefined,
                priority: form.priority || undefined,
                search: form.search || undefined,
                timeframe: form.timeframe || undefined,
            },
            {
                preserveState: true,
                replace: true,
            },
        );
    };

    const hasPagination = useMemo(
        () => reports.links.some((link) => link.url !== null),
        [reports.links],
    );

    return (
        <AppLayout>
            <Head title="Bug triage" />
            <div className="mx-auto flex max-w-6xl flex-col gap-6 p-6">
                <header className="space-y-2">
                    <h1 className="text-2xl font-semibold text-zinc-100">Bug triage dashboard</h1>
                    <p className="text-sm text-zinc-400">
                        Review incoming launch issues, update statuses, and monitor priority trends during the release window.
                    </p>
                    <div className="flex flex-wrap gap-3 text-xs">
                        {Object.entries(counts.priority).map(([priority, count]) => (
                            <span
                                key={priority}
                                className={cn('rounded-full border px-3 py-1 font-semibold uppercase tracking-wide', priorityTone[priority] ?? priorityTone.normal)}
                            >
                                {priority}: {count}
                            </span>
                        ))}
                        {Object.entries(counts.status).map(([status, count]) => (
                            <span key={status} className="rounded-full bg-zinc-900 px-3 py-1 font-semibold uppercase tracking-wide text-zinc-300">
                                {status.replace('_', ' ')}: {count}
                            </span>
                        ))}
                    </div>
                </header>

                <section className="grid gap-4 rounded-xl border border-zinc-800 bg-zinc-950/70 p-4 md:grid-cols-3">
                    <article className="flex flex-col gap-3 rounded-lg border border-zinc-800/60 bg-zinc-900/70 p-4">
                        <div className="flex items-center justify-between text-xs uppercase tracking-wide text-zinc-500">
                            <span>Volume (7d)</span>
                            <span className={cn('font-semibold', analytics.volume.delta_percentage && analytics.volume.delta_percentage > 0 ? 'text-emerald-300' : analytics.volume.delta_percentage && analytics.volume.delta_percentage < 0 ? 'text-rose-300' : 'text-zinc-400')}>
                                {formatDelta(analytics.volume.delta_percentage)}
                            </span>
                        </div>
                        <p className="text-3xl font-semibold text-zinc-100">{analytics.volume.current_total}</p>
                        <p className="text-xs text-zinc-400">Prev 7d: {analytics.volume.previous_total}</p>
                        <ul className="grid grid-cols-7 gap-1 text-[11px] text-zinc-500">
                            {analytics.volume.series.map((entry) => (
                                <li key={entry.day} className="flex flex-col items-center gap-1">
                                    <span>{dayjs(entry.day).format('D')}</span>
                                    <span className="min-h-[24px] rounded bg-zinc-800 px-1 font-medium text-zinc-200">{entry.count}</span>
                                </li>
                            ))}
                        </ul>
                    </article>
                    <article className="flex flex-col gap-3 rounded-lg border border-zinc-800/60 bg-zinc-900/70 p-4">
                        <div className="text-xs uppercase tracking-wide text-zinc-500">Resolution time (30d)</div>
                        <dl className="grid gap-3 text-sm text-zinc-200">
                            <div>
                                <dt className="text-xs uppercase tracking-wide text-zinc-500">Average</dt>
                                <dd>{formatHours(analytics.resolution.average_hours)}</dd>
                            </div>
                            <div>
                                <dt className="text-xs uppercase tracking-wide text-zinc-500">Median</dt>
                                <dd>{formatHours(analytics.resolution.median_hours)}</dd>
                            </div>
                            <div>
                                <dt className="text-xs uppercase tracking-wide text-zinc-500">P90</dt>
                                <dd>{formatHours(analytics.resolution.p90_hours)}</dd>
                            </div>
                        </dl>
                        <p className="text-xs text-zinc-500">Measured from report creation until resolution or closure.</p>
                    </article>
                    <article className="flex flex-col gap-3 rounded-lg border border-zinc-800/60 bg-zinc-900/70 p-4">
                        <div className="text-xs uppercase tracking-wide text-zinc-500">Top tags (30d)</div>
                        <p className="text-3xl font-semibold text-zinc-100">{analytics.categories.total}</p>
                        <ul className="space-y-2 text-sm text-zinc-300">
                            {analytics.categories.top.length === 0 ? (
                                <li className="text-xs text-zinc-500">No tagged bugs yet.</li>
                            ) : (
                                analytics.categories.top.map((entry) => (
                                    <li key={entry.tag} className="flex items-center justify-between">
                                        <span className="uppercase tracking-wide text-zinc-400">{entry.tag}</span>
                                        <span className="font-semibold text-zinc-100">{entry.count}</span>
                                    </li>
                                ))
                            )}
                        </ul>
                    </article>
                </section>

                <section className="rounded-xl border border-zinc-800 bg-zinc-950/70 p-4">
                    <form className="grid gap-4 md:grid-cols-4" onSubmit={submitFilters}>
                        <div className="space-y-2">
                            <label className="text-xs uppercase tracking-wide text-zinc-500">Status</label>
                            <select
                                value={form.status}
                                onChange={(event) => setForm((current) => ({ ...current, status: event.target.value }))}
                                className="w-full rounded-lg border border-zinc-700 bg-zinc-900 px-3 py-2 text-sm text-zinc-100"
                            >
                                <option value="">All</option>
                                <option value="open">Open</option>
                                <option value="in_progress">In progress</option>
                                <option value="resolved">Resolved</option>
                                <option value="closed">Closed</option>
                            </select>
                        </div>
                        <div className="space-y-2">
                            <label className="text-xs uppercase tracking-wide text-zinc-500">Priority</label>
                            <select
                                value={form.priority}
                                onChange={(event) => setForm((current) => ({ ...current, priority: event.target.value }))}
                                className="w-full rounded-lg border border-zinc-700 bg-zinc-900 px-3 py-2 text-sm text-zinc-100"
                            >
                                <option value="">All</option>
                                <option value="low">Low</option>
                                <option value="normal">Normal</option>
                                <option value="high">High</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>
                        <div className="space-y-2">
                            <label className="text-xs uppercase tracking-wide text-zinc-500">Updated</label>
                            <select
                                value={form.timeframe}
                                onChange={(event) =>
                                    setForm((current) => ({ ...current, timeframe: event.target.value }))
                                }
                                className="w-full rounded-lg border border-zinc-700 bg-zinc-900 px-3 py-2 text-sm text-zinc-100"
                            >
                                <option value="">Any time</option>
                                <option value="24h">Last 24 hours</option>
                                <option value="7d">Last 7 days</option>
                                <option value="30d">Last 30 days</option>
                            </select>
                        </div>
                        <div className="space-y-2 md:col-span-2">
                            <label className="text-xs uppercase tracking-wide text-zinc-500">Search</label>
                            <Input
                                value={form.search}
                                onChange={(event) => setForm((current) => ({ ...current, search: event.target.value }))}
                                placeholder="Reference or summary"
                            />
                        </div>
                        <div className="md:col-span-4 flex items-center justify-end gap-3">
                            <Button type="submit">Filter</Button>
                            <Button
                                type="button"
                                variant="ghost"
                                onClick={() => {
                                    setForm({ status: '', priority: '', search: '', timeframe: '' });
                                    router.get(route('admin.bug-reports.index'), {}, { replace: true });
                                }}
                            >
                                Reset
                            </Button>
                        </div>
                    </form>
                </section>

                <section className="rounded-xl border border-zinc-800 bg-zinc-950/70">
                    <table className="min-w-full divide-y divide-zinc-800 text-sm text-zinc-300">
                        <thead className="bg-zinc-900/80 text-xs uppercase tracking-wide text-zinc-500">
                            <tr>
                                <th className="px-4 py-3 text-left">Reference</th>
                                <th className="px-4 py-3 text-left">Summary</th>
                                <th className="px-4 py-3 text-left">Group</th>
                                <th className="px-4 py-3 text-left">Assignee</th>
                                <th className="px-4 py-3 text-left">Updated</th>
                                <th className="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-zinc-800">
                            {reports.data.map((report) => (
                                <tr key={report.id} className="hover:bg-zinc-900/40">
                                    <td className="px-4 py-3">
                                        <div className="flex flex-col">
                                            <span className="font-mono text-amber-200">{report.reference}</span>
                                            <span className={cn('mt-1 inline-flex w-fit rounded border px-2 py-0.5 text-[11px] uppercase tracking-wide', priorityTone[report.priority] ?? priorityTone.normal)}>
                                                {report.priority}
                                            </span>
                                        </div>
                                    </td>
                                    <td className="px-4 py-3">
                                        <div className="flex flex-col gap-1">
                                            <span className="text-zinc-100">{report.summary}</span>
                                            <span className={cn('text-xs uppercase tracking-wide', statusTone[report.status] ?? 'text-zinc-400')}>
                                                {report.status.replace('_', ' ')}
                                            </span>
                                        </div>
                                    </td>
                                    <td className="px-4 py-3 text-xs text-zinc-400">{report.group?.name ?? '—'}</td>
                                    <td className="px-4 py-3 text-xs text-zinc-400">{report.assignee?.name ?? 'Unassigned'}</td>
                                    <td className="px-4 py-3 text-xs text-zinc-400">
                                        {report.updated_at ? dayjs(report.updated_at).fromNow() : '—'}
                                    </td>
                                    <td className="px-4 py-3 text-right text-xs">
                                        <Link
                                            href={route('admin.bug-reports.show', report.id)}
                                            className="rounded bg-brand-500/20 px-3 py-1 font-semibold text-brand-200 hover:bg-brand-500/40"
                                        >
                                            Review
                                        </Link>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                    {reports.data.length === 0 && (
                        <p className="p-6 text-center text-sm text-zinc-500">No bug reports match the current filters.</p>
                    )}
                    {reports.data.length > 0 && hasPagination && (
                        <nav className="flex items-center justify-between border-t border-zinc-800 bg-zinc-950/80 px-4 py-3 text-xs text-zinc-400">
                            <div>
                                Showing {reports.data.length} report{reports.data.length === 1 ? '' : 's'} on this page
                            </div>
                            <ul className="flex items-center gap-2">
                                {reports.links.map((link, index) => {
                                    const label = link.label.replace('&laquo;', '«').replace('&raquo;', '»');
                                    const isDisabled = link.url === null;

                                    if (index === 0 || index === reports.links.length - 1) {
                                        return (
                                            <li key={`${index}-${label}`}>
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    disabled={isDisabled}
                                                    onClick={() => link.url && router.visit(link.url, { preserveState: true })}
                                                >
                                                    {label}
                                                </Button>
                                            </li>
                                        );
                                    }

                                    return (
                                        <li key={`${index}-${label}`}>
                                            <Button
                                                type="button"
                                                variant={link.active ? 'default' : 'ghost'}
                                                size="sm"
                                                className={cn('min-w-[2.25rem]', link.active ? '' : 'text-zinc-400 hover:text-zinc-200')}
                                                disabled={isDisabled}
                                                onClick={() => link.url && router.visit(link.url, { preserveState: true })}
                                            >
                                                {label}
                                            </Button>
                                        </li>
                                    );
                                })}
                            </ul>
                        </nav>
                    )}
                </section>
            </div>
        </AppLayout>
    );
}

function formatDelta(delta: number | null) {
    if (delta === null) {
        return '—';
    }

    const sign = delta > 0 ? '+' : '';

    return `${sign}${delta}%`;
}

function formatHours(value: number | null) {
    if (value === null) {
        return '—';
    }

    return `${value.toFixed(1)}h`;
}
