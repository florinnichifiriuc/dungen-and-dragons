import { Head, Link, useForm } from '@inertiajs/react';
import dayjs from 'dayjs';
import relativeTime from 'dayjs/plugin/relativeTime';
import utc from 'dayjs/plugin/utc';
import { FormEvent, useMemo } from 'react';

import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { InputError } from '@/components/InputError';
import { cn } from '@/lib/utils';

import type { BugReportUpdateEntry } from '@/types/bugReports';

dayjs.extend(utc);
dayjs.extend(relativeTime);

type SupportAdmin = { id: number; name: string; email?: string | null };

type AdminBugReport = {
    id: number;
    reference: string;
    summary: string;
    description: string;
    status: string;
    priority: 'low' | 'normal' | 'high' | 'critical';
    tags: string[];
    context_type: string;
    context_identifier?: string | null;
    environment?: Record<string, unknown> | null;
    ai_context?: (
        | {
              id: string | number;
              type: string;
              summary: string;
              created_at?: string | null;
              focus_match?: boolean | null;
          }
    )[];
    submitted_at?: string | null;
    submitter: { name?: string | null; email?: string | null };
    assignee?: { id: number; name: string; email?: string | null } | null;
    group?: { id: number; name: string } | null;
    updates: BugReportUpdateEntry[];
};

type AdminBugReportShowPageProps = {
    report: AdminBugReport;
    support_admins: SupportAdmin[];
};

type UpdateFormState = {
    status: string;
    priority: AdminBugReport['priority'];
    assigned_to: string;
    tags: string;
    note: string;
};

type CommentFormState = {
    body: string;
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

export default function AdminBugReportShowPage({ report, support_admins: supportAdmins }: AdminBugReportShowPageProps) {
    const updateForm = useForm<UpdateFormState>({
        status: report.status,
        priority: report.priority,
        assigned_to: report.assignee?.id ? String(report.assignee.id) : '',
        tags: report.tags.join(', '),
        note: '',
    });

    const commentForm = useForm<CommentFormState>({
        body: '',
    });

    const submitUpdate = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        updateForm.transform((data) => ({
            status: data.status || null,
            priority: data.priority || null,
            assigned_to: data.assigned_to ? Number(data.assigned_to) : null,
            tags: data.tags
                .split(',')
                .map((value) => value.trim())
                .filter((value) => value !== ''),
            note: data.note || null,
        }));

        updateForm.patch(route('admin.bug-reports.update', report.id), {
            preserveScroll: true,
            onFinish: () => {
                updateForm.setTransform((data) => data);
                updateForm.setData('note', '');
            },
        });
    };

    const submitComment = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        commentForm.post(route('admin.bug-reports.comment', report.id), {
            preserveScroll: true,
            onSuccess: () => commentForm.reset(),
        });
    };

    const statusOptions = useMemo(
        () => [
            { value: 'open', label: 'Open' },
            { value: 'in_progress', label: 'In progress' },
            { value: 'resolved', label: 'Resolved' },
            { value: 'closed', label: 'Closed' },
        ],
        [],
    );

    const priorityOptions = useMemo(
        () => [
            { value: 'low', label: 'Low' },
            { value: 'normal', label: 'Normal' },
            { value: 'high', label: 'High' },
            { value: 'critical', label: 'Critical' },
        ],
        [],
    );

    const submittedAt = report.submitted_at ? dayjs(report.submitted_at).format('YYYY-MM-DD HH:mm') : null;
    const tagError =
        updateForm.errors.tags ??
        (updateForm.errors as Record<string, string>)['tags.0'] ??
        (updateForm.errors as Record<string, string>)['tags.*'] ??
        null;

    return (
        <AppLayout>
            <Head title={`Bug ${report.reference}`} />
            <div className="mx-auto flex max-w-6xl flex-col gap-6 p-6">
                <header className="flex flex-col gap-3 rounded-xl border border-zinc-800 bg-zinc-950/70 p-6">
                    <div className="flex flex-wrap items-center gap-3 text-xs text-zinc-400">
                        <span className="rounded bg-zinc-900 px-2 py-1 font-mono text-amber-200">{report.reference}</span>
                        <span className={cn('rounded border px-2 py-1 font-semibold uppercase tracking-wide', priorityTone[report.priority] ?? priorityTone.normal)}>
                            {report.priority}
                        </span>
                        <span className={cn('rounded bg-zinc-900 px-2 py-1 font-semibold uppercase tracking-wide', statusTone[report.status] ?? 'text-zinc-200')}>
                            {report.status.replace('_', ' ')}
                        </span>
                        {report.group && (
                            <Link
                                href={route('groups.condition-timers.player-summary', report.group.id)}
                                className="rounded bg-brand-500/20 px-2 py-1 font-semibold text-brand-200 hover:bg-brand-500/40"
                            >
                                {report.group.name}
                            </Link>
                        )}
                    </div>
                    <div>
                        <h1 className="text-3xl font-semibold text-zinc-100">{report.summary}</h1>
                        <p className="mt-2 whitespace-pre-line text-sm text-zinc-400">{report.description}</p>
                    </div>
                    <dl className="grid gap-4 text-xs text-zinc-400 md:grid-cols-2">
                        <div className="space-y-1">
                            <dt className="font-semibold uppercase tracking-wide text-zinc-500">Submitted</dt>
                            <dd>
                                {submittedAt ? (
                                    <span>
                                        {submittedAt} by {report.submitter.name ?? 'guest'}
                                    </span>
                                ) : (
                                    'Unknown'
                                )}
                            </dd>
                            {report.submitter.email && <dd>Contact: {report.submitter.email}</dd>}
                        </div>
                        <div className="space-y-1">
                            <dt className="font-semibold uppercase tracking-wide text-zinc-500">Context</dt>
                            <dd>
                                {report.context_type}
                                {report.context_identifier && ` • ${report.context_identifier}`}
                            </dd>
                            {report.assignee && <dd>Assigned to: {report.assignee.name}</dd>}
                        </div>
                    </dl>
                    {report.tags.length > 0 && (
                        <div className="flex flex-wrap gap-2">
                            {report.tags.map((tag) => (
                                <span key={tag} className="rounded-full bg-zinc-900 px-3 py-1 text-xs uppercase tracking-wide text-zinc-300">
                                    {tag}
                                </span>
                            ))}
                        </div>
                    )}
                </header>

                <div className="grid gap-6 lg:grid-cols-[2fr,1fr]">
                    <section className="space-y-4 rounded-xl border border-zinc-800 bg-zinc-950/70 p-6">
                        <h2 className="text-sm font-semibold uppercase tracking-wide text-zinc-300">Triage controls</h2>
                        <form className="space-y-4" onSubmit={submitUpdate}>
                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="status">Status</Label>
                                    <select
                                        id="status"
                                        name="status"
                                        value={updateForm.data.status}
                                        onChange={(event) => updateForm.setData('status', event.target.value)}
                                        className="w-full rounded-lg border border-zinc-700 bg-zinc-900 px-3 py-2 text-sm text-zinc-100 focus:border-brand-400 focus:outline-none"
                                    >
                                        {statusOptions.map((option) => (
                                            <option key={option.value} value={option.value}>
                                                {option.label}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={updateForm.errors.status} className="mt-1" />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="priority">Priority</Label>
                                    <select
                                        id="priority"
                                        name="priority"
                                        value={updateForm.data.priority}
                                        onChange={(event) => updateForm.setData('priority', event.target.value as UpdateFormState['priority'])}
                                        className="w-full rounded-lg border border-zinc-700 bg-zinc-900 px-3 py-2 text-sm text-zinc-100 focus:border-brand-400 focus:outline-none"
                                    >
                                        {priorityOptions.map((option) => (
                                            <option key={option.value} value={option.value}>
                                                {option.label}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={updateForm.errors.priority} className="mt-1" />
                                </div>
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="assigned_to">Assign to</Label>
                                <select
                                    id="assigned_to"
                                    name="assigned_to"
                                    value={updateForm.data.assigned_to}
                                    onChange={(event) => updateForm.setData('assigned_to', event.target.value)}
                                    className="w-full rounded-lg border border-zinc-700 bg-zinc-900 px-3 py-2 text-sm text-zinc-100 focus:border-brand-400 focus:outline-none"
                                >
                                    <option value="">Unassigned</option>
                                    {supportAdmins.map((admin) => (
                                        <option key={admin.id} value={admin.id}>
                                            {admin.name}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={updateForm.errors.assigned_to} className="mt-1" />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="tags">Tags</Label>
                                <Input
                                    id="tags"
                                    name="tags"
                                    value={updateForm.data.tags}
                                    onChange={(event) => updateForm.setData('tags', event.target.value)}
                                    placeholder="comma,separated,tags"
                                />
                                <InputError message={tagError ?? undefined} className="mt-1" />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="note">Status note</Label>
                                <Textarea
                                    id="note"
                                    name="note"
                                    value={updateForm.data.note}
                                    onChange={(event) => updateForm.setData('note', event.target.value)}
                                    placeholder="Internal note about this update"
                                    rows={3}
                                />
                                <InputError message={updateForm.errors.note} className="mt-1" />
                            </div>
                            <div className="flex items-center justify-end gap-3">
                                <Button type="submit" disabled={updateForm.processing}>
                                    {updateForm.processing ? 'Updating…' : 'Save changes'}
                                </Button>
                            </div>
                        </form>
                    </section>
                    <section className="space-y-4 rounded-xl border border-zinc-800 bg-zinc-950/70 p-6">
                        <h2 className="text-sm font-semibold uppercase tracking-wide text-zinc-300">Add comment</h2>
                        <form className="space-y-3" onSubmit={submitComment}>
                            <div className="space-y-2">
                                <Label htmlFor="comment-body">Comment</Label>
                                <Textarea
                                    id="comment-body"
                                    name="body"
                                    value={commentForm.data.body}
                                    onChange={(event) => commentForm.setData('body', event.target.value)}
                                    rows={4}
                                    placeholder="Share triage notes, next steps, or outreach details"
                                />
                                <InputError message={commentForm.errors.body} className="mt-1" />
                            </div>
                            <div className="flex items-center justify-end gap-3">
                                <Button type="submit" disabled={commentForm.processing}>
                                    {commentForm.processing ? 'Recording…' : 'Record comment'}
                                </Button>
                            </div>
                        </form>
                        <div className="rounded-lg border border-zinc-800 bg-zinc-950/80 p-4 text-xs text-zinc-500">
                            <p>Export a CSV snapshot for launch review:</p>
                            <Button
                                asChild
                                variant="outline"
                                size="sm"
                                className="mt-3 border-brand-500/50 bg-transparent text-brand-200 hover:bg-brand-500/10"
                            >
                                <Link href={route('admin.bug-reports.export')}>Download CSV</Link>
                            </Button>
                        </div>
                    </section>
                </div>

                {report.environment && (
                    <section className="rounded-xl border border-zinc-800 bg-zinc-950/60 p-6">
                        <h2 className="text-sm font-semibold uppercase tracking-wide text-zinc-300">Environment</h2>
                        <dl className="mt-3 grid gap-3 text-xs text-zinc-400 md:grid-cols-2">
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
                    <section className="rounded-xl border border-zinc-800 bg-zinc-950/60 p-6">
                        <h2 className="text-sm font-semibold uppercase tracking-wide text-zinc-300">Recent AI interactions</h2>
                        <ul className="mt-4 space-y-3 text-xs text-zinc-400">
                            {report.ai_context.map((entry) => (
                                <li key={entry.id} className="rounded-lg border border-zinc-800/70 bg-zinc-900/70 p-3">
                                    <div className="flex items-center justify-between text-[11px] uppercase tracking-wide text-zinc-500">
                                        <span>{entry.type}</span>
                                        {entry.created_at && <span>{dayjs(entry.created_at).format('MMM D, HH:mm')}</span>}
                                    </div>
                                    <p className="mt-2 text-sm text-zinc-300">{entry.summary}</p>
                                    {entry.focus_match !== undefined && entry.focus_match !== null && (
                                        <p className="mt-2 text-[11px] text-zinc-500">
                                            Focus match: {entry.focus_match ? 'yes' : 'no'}
                                        </p>
                                    )}
                                </li>
                            ))}
                        </ul>
                    </section>
                )}

                <section className="space-y-4 rounded-xl border border-zinc-800 bg-zinc-950/60 p-6">
                    <h2 className="text-sm font-semibold uppercase tracking-wide text-zinc-300">Activity</h2>
                    {report.updates.length === 0 ? (
                        <p className="text-xs text-zinc-500">No activity recorded yet.</p>
                    ) : (
                        <ol className="space-y-3">
                            {report.updates.map((update) => (
                                <li key={update.id} className="rounded-lg border border-zinc-800/70 bg-zinc-900/70 p-3">
                                    <div className="flex items-center justify-between text-[11px] uppercase tracking-wide text-zinc-500">
                                        <span>{update.type.replace('_', ' ')}</span>
                                        {update.created_at && <span>{dayjs(update.created_at).fromNow()}</span>}
                                    </div>
                                    <p className="mt-1 text-xs text-zinc-400">{update.actor ? `By ${update.actor.name}` : 'System'}</p>
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
        return <p className="mt-2 whitespace-pre-line text-sm text-zinc-200">{update.payload.body as string}</p>;
    }

    return (
        <pre className="mt-2 overflow-auto rounded bg-zinc-950/80 p-3 text-[11px] text-zinc-400">
            {JSON.stringify(update.payload, null, 2)}
        </pre>
    );
}
