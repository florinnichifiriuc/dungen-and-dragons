import { Head } from '@inertiajs/react';
import dayjs from 'dayjs';
import { useState } from 'react';

import AppLayout from '@/Layouts/AppLayout';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';

import type { BugReportSummary, BugReportUpdateEntry } from '@/types/bugReports';

type BugReportShowPageProps = {
    report: BugReportSummary;
    can: {
        update: boolean;
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

export default function BugReportShowPage({ report }: BugReportShowPageProps) {
    const [copied, setCopied] = useState(false);

    const copyReference = async () => {
        try {
            await navigator.clipboard.writeText(report.reference);
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        } catch (error) {
            setCopied(false);
        }
    };

    return (
        <AppLayout>
            <Head title={`Bug report ${report.reference}`} />
            <div className="mx-auto flex max-w-5xl flex-col gap-6 p-6">
                <header className="space-y-3">
                    <div className="flex flex-wrap items-center gap-2 text-xs text-zinc-400">
                        <span className="rounded bg-zinc-800 px-2 py-1 font-mono text-amber-200">{report.reference}</span>
                        <span className={cn('rounded border px-2 py-1 font-semibold uppercase tracking-wide', priorityTone[report.priority] ?? priorityTone.normal)}>
                            {report.priority}
                        </span>
                        <span className={cn('rounded bg-zinc-800 px-2 py-1 font-semibold uppercase tracking-wide', statusTone[report.status] ?? 'text-zinc-200')}>
                            {report.status.replace('_', ' ')}
                        </span>
                    </div>
                    <h1 className="text-3xl font-semibold text-zinc-100">{report.summary}</h1>
                    <p className="text-sm text-zinc-400 whitespace-pre-line">{report.description}</p>
                    <div className="grid gap-3 text-xs text-zinc-500 md:grid-cols-2">
                        <div className="space-y-1">
                            <p>
                                Submitted {report.submitted.at ? dayjs(report.submitted.at).format('YYYY-MM-DD HH:mm') : 'recently'} by {report.submitted.name ?? 'guest'}
                            </p>
                            {report.submitted.email && <p>Contact: {report.submitted.email}</p>}
                            {report.group && <p>Group: {report.group.name}</p>}
                        </div>
                        <div className="space-y-1">
                            <p>Context: {report.context_type}</p>
                            {report.context_identifier && <p>Identifier: {report.context_identifier}</p>}
                            {report.assignee && <p>Assigned to: {report.assignee.name}</p>}
                        </div>
                    </div>
                    {report.tags.length > 0 && (
                        <div className="flex flex-wrap gap-2">
                            {report.tags.map((tag) => (
                                <span key={tag} className="rounded-full bg-zinc-800 px-3 py-1 text-xs uppercase tracking-wide text-zinc-300">
                                    {tag}
                                </span>
                            ))}
                        </div>
                    )}
                    <div className="flex flex-col gap-2 rounded-lg border border-zinc-800/70 bg-zinc-900/60 p-4 text-xs text-zinc-300">
                        <div className="flex flex-wrap items-center justify-between gap-2">
                            <span>
                                Reference <span className="font-mono text-amber-200">{report.reference}</span>
                            </span>
                            <Button size="sm" variant="secondary" onClick={copyReference} className="text-xs">
                                {copied ? 'Copied!' : 'Copy reference'}
                            </Button>
                        </div>
                        <p>
                            Save this reference to follow progress. We’ll email updates to {report.submitted.email ?? 'your provided contact'} and you can revisit this page for status changes and comments.
                        </p>
                    </div>
                </header>

                {report.environment && (
                    <section className="rounded-xl border border-zinc-800 bg-zinc-950/60 p-4">
                        <h2 className="text-sm font-semibold text-zinc-200">Environment</h2>
                        <dl className="grid gap-2 pt-2 text-xs text-zinc-400 md:grid-cols-2">
                            {Object.entries(report.environment).map(([key, value]) => (
                                <div key={key}>
                                    <dt className="font-semibold uppercase tracking-wide text-zinc-500">{key.replace('_', ' ')}</dt>
                                    <dd>{Array.isArray(value) ? value.join(', ') : String(value)}</dd>
                                </div>
                            ))}
                        </dl>
                    </section>
                )}

                {report.ai_context && report.ai_context.length > 0 && (
                    <section className="rounded-xl border border-zinc-800 bg-zinc-950/60 p-4">
                        <h2 className="text-sm font-semibold text-zinc-200">Recent AI interactions</h2>
                        <ul className="mt-3 space-y-3 text-xs text-zinc-400">
                            {report.ai_context.map((entry) => (
                                <li key={entry.id} className="rounded-lg border border-zinc-800/80 bg-zinc-900/70 p-3">
                                    <div className="flex items-center justify-between text-[11px] text-zinc-500">
                                        <span>{entry.type}</span>
                                        {entry.created_at && <span>{dayjs(entry.created_at).format('MMM D, HH:mm')}</span>}
                                    </div>
                                    <p className="mt-2 text-sm text-zinc-300">{entry.summary}</p>
                                </li>
                            ))}
                        </ul>
                    </section>
                )}

                <section className="space-y-4 rounded-xl border border-zinc-800 bg-zinc-950/60 p-4">
                    <h2 className="text-sm font-semibold text-zinc-200">Activity</h2>
                    {report.updates.length === 0 ? (
                        <p className="text-xs text-zinc-500">No activity recorded yet.</p>
                    ) : (
                        <ol className="space-y-3">
                            {report.updates.map((update) => (
                                <li key={update.id} className="rounded-lg border border-zinc-800/70 bg-zinc-900/70 p-3">
                                    <div className="flex items-center justify-between text-[11px] uppercase tracking-wide text-zinc-500">
                                        <span>{update.type.replace('_', ' ')}</span>
                                        {update.created_at && <span>{dayjs(update.created_at).format('MMM D, HH:mm')}</span>}
                                    </div>
                                    <p className="mt-1 text-xs text-zinc-400">
                                        {update.actor ? `By ${update.actor.name}` : 'System'}
                                    </p>
                                    {renderUpdatePayload(update)}
                                </li>
                            ))}
                        </ol>
                    )}
                </section>
            </div>
        </AppLayout>
    );
}

function renderUpdatePayload(update: BugReportUpdateEntry) {
    if (!update.payload) {
        return null;
    }

    if (update.type === 'comment') {
        return <p className="mt-2 whitespace-pre-line text-sm text-zinc-200">{update.payload.body}</p>;
    }

    return (
        <pre className="mt-2 overflow-auto rounded bg-zinc-950/80 p-3 text-[11px] text-zinc-400">
            {JSON.stringify(update.payload, null, 2)}
        </pre>
    );
}
