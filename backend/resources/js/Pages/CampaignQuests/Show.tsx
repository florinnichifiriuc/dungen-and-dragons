import { FormEventHandler } from 'react';

import { Head, Link, router, useForm } from '@inertiajs/react';

import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { InputError } from '@/components/InputError';

type CampaignSummary = {
    id: number;
    title: string;
};

type QuestDetails = {
    id: number;
    title: string;
    summary: string | null;
    details: string | null;
    status: string;
    priority: string;
    target_turn_number: number | null;
    starts_at: string | null;
    completed_at: string | null;
    archived_at: string | null;
    region: { id: number; name: string } | null;
    creator: { id: number; name: string } | null;
};

type QuestUpdate = {
    id: number;
    summary: string;
    details: string | null;
    recorded_at: string | null;
    created_at: string | null;
    author: { id: number; name: string } | null;
    can_delete: boolean;
};

type Permissions = {
    can_update: boolean;
    can_delete: boolean;
    can_log_update: boolean;
};

type CampaignQuestShowProps = {
    campaign: CampaignSummary;
    quest: QuestDetails;
    updates: QuestUpdate[];
    permissions: Permissions;
};

const statusLabels: Record<string, string> = {
    planned: 'Planned',
    active: 'Active',
    completed: 'Completed',
    failed: 'Failed',
};

const priorityLabels: Record<string, string> = {
    critical: 'Critical',
    high: 'High',
    standard: 'Standard',
    low: 'Low',
};

const statusStyles: Record<string, string> = {
    planned: 'bg-sky-500/10 text-sky-200 border-sky-500/40',
    active: 'bg-emerald-500/10 text-emerald-200 border-emerald-500/40',
    completed: 'bg-indigo-500/10 text-indigo-200 border-indigo-500/40',
    failed: 'bg-rose-500/10 text-rose-200 border-rose-500/40',
};

const priorityStyles: Record<string, string> = {
    critical: 'bg-rose-500/20 text-rose-100',
    high: 'bg-amber-500/20 text-amber-100',
    standard: 'bg-zinc-700/40 text-zinc-200',
    low: 'bg-zinc-800 text-zinc-300',
};

export default function CampaignQuestShow({
    campaign,
    quest,
    updates,
    permissions,
}: CampaignQuestShowProps) {
    const { data, setData, post, processing, errors, reset } = useForm({
        summary: '',
        details: '',
        recorded_at: '',
    });

    const submit: FormEventHandler<HTMLFormElement> = (event) => {
        event.preventDefault();

        post(route('campaigns.quests.updates.store', [campaign.id, quest.id]), {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    };

    const deleteUpdate = (updateId: number) => {
        router.delete(route('campaigns.quests.updates.destroy', [campaign.id, quest.id, updateId]), {
            preserveScroll: true,
        });
    };

    return (
        <AppLayout>
            <Head title={`${campaign.title} · ${quest.title}`} />

            <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 className="text-3xl font-semibold text-zinc-100">{quest.title}</h1>
                    <p className="mt-1 text-sm text-zinc-400">
                        {quest.summary || 'No summary yet—capture stakes and foreshadowing so the party stays aligned.'}
                    </p>
                </div>

                <div className="flex items-center gap-3">
                    <Button asChild variant="outline" className="border-zinc-700 text-zinc-200 hover:bg-zinc-800/80">
                        <Link href={route('campaigns.quests.index', campaign.id)}>Quest log</Link>
                    </Button>
                    {permissions.can_update && (
                        <Button asChild className="bg-amber-500/20 text-amber-100 hover:bg-amber-500/30">
                            <Link href={route('campaigns.quests.edit', [campaign.id, quest.id])}>Edit quest</Link>
                        </Button>
                    )}
                </div>
            </div>

            <section className="mt-6 rounded-xl border border-zinc-800 bg-zinc-950/60 p-6 shadow-inner shadow-black/40">
                <header className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex flex-wrap items-center gap-2">
                        <span
                            className={`rounded-full border px-3 py-1 text-xs font-semibold uppercase tracking-wide ${
                                statusStyles[quest.status] ?? 'bg-zinc-800 text-zinc-200'
                            }`}
                        >
                            {statusLabels[quest.status] ?? quest.status}
                        </span>
                        <span
                            className={`rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-wide ${
                                priorityStyles[quest.priority] ?? 'bg-zinc-800 text-zinc-200'
                            }`}
                        >
                            {priorityLabels[quest.priority] ?? quest.priority}
                        </span>
                        {quest.region && (
                            <span className="rounded-full bg-indigo-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-indigo-200">
                                {quest.region.name}
                            </span>
                        )}
                    </div>

                    <dl className="grid gap-4 text-xs uppercase tracking-wide text-zinc-500 sm:grid-cols-3">
                        <div>
                            <dt className="text-zinc-500">Target turn</dt>
                            <dd className="text-sm text-zinc-200">
                                {quest.target_turn_number ? `Turn ${quest.target_turn_number}` : 'Unset'}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-zinc-500">Starts</dt>
                            <dd className="text-sm text-zinc-200">
                                {quest.starts_at ? new Date(quest.starts_at).toLocaleString() : 'Flexible'}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-zinc-500">Completed</dt>
                            <dd className="text-sm text-zinc-200">
                                {quest.completed_at ? new Date(quest.completed_at).toLocaleString() : 'Pending'}
                            </dd>
                        </div>
                    </dl>
                </header>

                <div className="mt-6 grid gap-6 lg:grid-cols-3">
                    <div className="lg:col-span-2 space-y-4">
                        <article className="rounded-lg border border-zinc-800 bg-zinc-900/50 p-5">
                            <h2 className="text-lg font-semibold text-zinc-100">Synopsis</h2>
                            <p className="mt-2 whitespace-pre-wrap text-sm leading-relaxed text-zinc-300">
                                {quest.details || 'No extended notes yet. Document clues, factions, and consequences as they emerge.'}
                            </p>
                        </article>

                        <article className="rounded-lg border border-zinc-800 bg-zinc-900/50 p-5">
                            <h2 className="text-lg font-semibold text-zinc-100">Progress log</h2>

                            {updates.length === 0 ? (
                                <p className="mt-3 rounded-lg border border-dashed border-zinc-800 bg-zinc-950/60 p-4 text-sm text-zinc-400">
                                    No updates yet. Encourage the party to jot discoveries, setbacks, and NPC reactions after each turn.
                                </p>
                            ) : (
                                <ul className="mt-3 space-y-4">
                                    {updates.map((update) => (
                                        <li key={update.id} className="rounded-lg border border-zinc-800 bg-zinc-950/60 p-4">
                                            <div className="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                                <div>
                                                    <p className="font-medium text-zinc-100">{update.summary}</p>
                                                    {update.details && (
                                                        <p className="mt-1 whitespace-pre-wrap text-sm text-zinc-300">{update.details}</p>
                                                    )}
                                                    <p className="mt-2 text-xs uppercase tracking-wide text-zinc-500">
                                                        {update.author ? update.author.name : 'Unknown chronicler'} ·{' '}
                                                        {update.recorded_at
                                                            ? new Date(update.recorded_at).toLocaleString()
                                                            : 'Recently logged'}
                                                    </p>
                                                </div>

                                                {update.can_delete && (
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        className="border-rose-700/60 text-xs text-rose-200 hover:bg-rose-900/40"
                                                        onClick={() => deleteUpdate(update.id)}
                                                    >
                                                        Remove
                                                    </Button>
                                                )}
                                            </div>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </article>
                    </div>

                    <aside className="space-y-6">
                        <div className="rounded-lg border border-zinc-800 bg-zinc-900/50 p-5">
                            <h2 className="text-lg font-semibold text-zinc-100">Metadata</h2>
                            <dl className="mt-3 space-y-3 text-sm text-zinc-300">
                                <div>
                                    <dt className="text-xs uppercase tracking-wide text-zinc-500">Creator</dt>
                                    <dd>{quest.creator ? quest.creator.name : 'Unknown'}</dd>
                                </div>
                                <div>
                                    <dt className="text-xs uppercase tracking-wide text-zinc-500">Archived</dt>
                                    <dd>{quest.archived_at ? new Date(quest.archived_at).toLocaleString() : 'Active'}</dd>
                                </div>
                            </dl>
                        </div>

                        {permissions.can_log_update && (
                            <form
                                onSubmit={submit}
                                className="rounded-lg border border-zinc-800 bg-zinc-900/50 p-5 shadow-inner shadow-black/40"
                            >
                                <h2 className="text-lg font-semibold text-zinc-100">Log progress</h2>

                                <div className="mt-3">
                                    <Label htmlFor="update-summary" className="text-xs uppercase tracking-wide text-zinc-500">
                                        Summary
                                    </Label>
                                    <Input
                                        id="update-summary"
                                        value={data.summary}
                                        onChange={(event) => setData('summary', event.target.value)}
                                        className="mt-1 bg-zinc-950/60"
                                        placeholder="What changed?"
                                    />
                                    <InputError message={errors.summary} className="mt-2" />
                                </div>

                                <div className="mt-3">
                                    <Label htmlFor="update-details" className="text-xs uppercase tracking-wide text-zinc-500">
                                        Details
                                    </Label>
                                    <Textarea
                                        id="update-details"
                                        value={data.details}
                                        onChange={(event) => setData('details', event.target.value)}
                                        className="mt-1 h-28 bg-zinc-950/60"
                                        placeholder="Optional notes, NPC reactions, or next steps"
                                    />
                                    <InputError message={errors.details} className="mt-2" />
                                </div>

                                <div className="mt-3">
                                    <Label htmlFor="update-recorded-at" className="text-xs uppercase tracking-wide text-zinc-500">
                                        Recorded at
                                    </Label>
                                    <Input
                                        id="update-recorded-at"
                                        type="datetime-local"
                                        value={data.recorded_at}
                                        onChange={(event) => setData('recorded_at', event.target.value)}
                                        className="mt-1 bg-zinc-950/60"
                                    />
                                    <InputError message={errors.recorded_at} className="mt-2" />
                                </div>

                                <Button
                                    type="submit"
                                    disabled={processing}
                                    className="mt-4 w-full bg-emerald-500/20 text-emerald-100 hover:bg-emerald-500/30"
                                >
                                    Log update
                                </Button>
                            </form>
                        )}
                    </aside>
                </div>
            </section>
        </AppLayout>
    );
}
