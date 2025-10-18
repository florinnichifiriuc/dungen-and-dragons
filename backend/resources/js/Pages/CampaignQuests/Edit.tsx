import { FormEventHandler } from 'react';

import { Head, Link, useForm } from '@inertiajs/react';

import AppLayout from '@/Layouts/AppLayout';
import { AiIdeaPanel, type AiIdeaResult } from '@/components/AiIdeaPanel';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { InputError } from '@/components/InputError';

import { questPresets } from './questPresets';

type RegionOption = {
    id: number;
    name: string;
};

type QuestPayload = {
    id: number;
    title: string;
    summary: string;
    details: string | null;
    status: string;
    priority: string;
    region_id: number | null;
    target_turn_number: number | null;
    starts_at: string | null;
    completed_at: string | null;
    archived_at: string | null;
};

type CampaignQuestEditProps = {
    campaign: { id: number; title: string };
    quest: QuestPayload;
    available_statuses: string[];
    available_priorities: string[];
    regions: RegionOption[];
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

export default function CampaignQuestEdit({ campaign, quest, available_statuses, available_priorities, regions }: CampaignQuestEditProps) {
    const { data, setData, put, processing, delete: destroy, errors } = useForm({
        title: quest.title,
        summary: quest.summary ?? '',
        details: quest.details ?? '',
        status: quest.status,
        priority: quest.priority,
        region_id: quest.region_id ? String(quest.region_id) : '',
        target_turn_number: quest.target_turn_number ? String(quest.target_turn_number) : '',
        starts_at: quest.starts_at ?? '',
        completed_at: quest.completed_at ?? '',
        archived_at: quest.archived_at ?? '',
    });

    const submit: FormEventHandler<HTMLFormElement> = (event) => {
        event.preventDefault();

        put(route('campaigns.quests.update', [campaign.id, quest.id]));
    };

    const handleDelete = () => {
        destroy(route('campaigns.quests.destroy', [campaign.id, quest.id]));
    };

    const applyQuestFields = (fields: Record<string, unknown>, result?: AiIdeaResult) => {
        if (typeof fields.title === 'string' && fields.title.trim() !== '') {
            setData('title', fields.title.trim());
        }

        if (typeof fields.summary === 'string' && fields.summary.trim() !== '') {
            setData('summary', fields.summary.trim());
        } else if (result?.summary && result.summary.trim() !== '' && data.summary.trim() === '') {
            setData('summary', result.summary.trim());
        }

        let nextDetails = data.details;
        const description = typeof fields.description === 'string' ? fields.description.trim() : '';

        if (description !== '') {
            nextDetails = description;
        }

        const objectives: string[] = Array.isArray(fields.objectives)
            ? fields.objectives
                  .map((objective) => (typeof objective === 'string' ? objective.trim() : ''))
                  .filter((objective) => objective !== '')
            : typeof fields.objectives === 'string'
              ? fields.objectives
                    .split(/[\n\r]+/)
                    .map((line) => line.replace(/^[-•]\s*/, '').trim())
                    .filter((line) => line !== '')
              : [];

        if (objectives.length > 0) {
            const formatted = objectives.map((objective) => `- ${objective}`).join('\n');

            if (description !== '') {
                nextDetails = `${description}\n\nObjectives:\n${formatted}`;
            } else if (nextDetails.trim() === '') {
                nextDetails = formatted;
            } else if (!nextDetails.includes('Objectives:')) {
                nextDetails = `${nextDetails.trim()}\n\nObjectives:\n${formatted}`;
            }
        }

        if (nextDetails.trim() !== '') {
            setData('details', nextDetails.trim());
        } else if (result?.summary && result.summary.trim() !== '') {
            setData('details', result.summary.trim());
        }
    };

    return (
        <AppLayout>
            <Head title={`${campaign.title} · Edit quest`} />

            <div className="mx-auto grid max-w-5xl gap-6 lg:grid-cols-[minmax(0,2.2fr),minmax(0,1fr)]">
                <div className="space-y-6">
                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-3xl font-semibold text-zinc-100">Edit quest</h1>
                            <p className="mt-1 text-sm text-zinc-400">Update the details and cadence for this narrative thread.</p>
                        </div>

                        <div className="flex items-center gap-3">
                            <Button
                                type="button"
                                variant="outline"
                                className="border-rose-700/60 text-rose-200 hover:bg-rose-900/40"
                                onClick={handleDelete}
                            >
                                Delete quest
                            </Button>
                            <Button asChild variant="outline" className="border-zinc-700 text-zinc-200 hover:bg-zinc-800/80">
                                <Link href={route('campaigns.quests.show', [campaign.id, quest.id])}>Back to quest</Link>
                            </Button>
                        </div>
                    </div>

                    <form onSubmit={submit} className="space-y-6">
                <section className="rounded-xl border border-zinc-800 bg-zinc-950/60 p-6 shadow-inner shadow-black/40">
                    <h2 className="text-lg font-semibold text-zinc-100">Quest details</h2>

                    <div className="mt-4 grid gap-5 md:grid-cols-2">
                        <div>
                            <Label htmlFor="title" className="text-xs uppercase tracking-wide text-zinc-500">
                                Title
                            </Label>
                            <Input
                                id="title"
                                value={data.title}
                                onChange={(event) => setData('title', event.target.value)}
                                className="mt-1 bg-zinc-900/60"
                            />
                            <InputError message={errors.title} className="mt-2" />
                        </div>

                        <div>
                            <Label htmlFor="region_id" className="text-xs uppercase tracking-wide text-zinc-500">
                                Region
                            </Label>
                            <select
                                id="region_id"
                                value={data.region_id}
                                onChange={(event) => setData('region_id', event.target.value)}
                                className="mt-1 w-full rounded-lg border border-zinc-800 bg-zinc-900/60 px-3 py-2 text-sm text-zinc-200 focus:border-amber-500 focus:outline-none"
                            >
                                <option value="">Unassigned</option>
                                {regions.map((region) => (
                                    <option key={region.id} value={String(region.id)}>
                                        {region.name}
                                    </option>
                                ))}
                            </select>
                            <InputError message={errors.region_id} className="mt-2" />
                        </div>
                    </div>

                    <div className="mt-4">
                        <Label htmlFor="summary" className="text-xs uppercase tracking-wide text-zinc-500">
                            Summary
                        </Label>
                        <Textarea
                            id="summary"
                            value={data.summary}
                            onChange={(event) => setData('summary', event.target.value)}
                            className="mt-1 h-32 bg-zinc-900/60"
                        />
                        <InputError message={errors.summary} className="mt-2" />
                    </div>

                    <div className="mt-4">
                        <Label htmlFor="details" className="text-xs uppercase tracking-wide text-zinc-500">
                            Details
                        </Label>
                        <Textarea
                            id="details"
                            value={data.details}
                            onChange={(event) => setData('details', event.target.value)}
                            className="mt-1 h-40 bg-zinc-900/60"
                        />
                        <InputError message={errors.details} className="mt-2" />
                    </div>
                </section>

                <section className="rounded-xl border border-zinc-800 bg-zinc-950/60 p-6 shadow-inner shadow-black/40">
                    <h2 className="text-lg font-semibold text-zinc-100">Cadence & priority</h2>

                    <div className="mt-4 grid gap-5 md:grid-cols-2">
                        <div>
                            <Label htmlFor="status" className="text-xs uppercase tracking-wide text-zinc-500">
                                Status
                            </Label>
                            <select
                                id="status"
                                value={data.status}
                                onChange={(event) => setData('status', event.target.value)}
                                className="mt-1 w-full rounded-lg border border-zinc-800 bg-zinc-900/60 px-3 py-2 text-sm text-zinc-200 focus:border-amber-500 focus:outline-none"
                            >
                                {available_statuses.map((status) => (
                                    <option key={status} value={status}>
                                        {statusLabels[status] ?? status}
                                    </option>
                                ))}
                            </select>
                            <InputError message={errors.status} className="mt-2" />
                        </div>

                        <div>
                            <Label htmlFor="priority" className="text-xs uppercase tracking-wide text-zinc-500">
                                Priority
                            </Label>
                            <select
                                id="priority"
                                value={data.priority}
                                onChange={(event) => setData('priority', event.target.value)}
                                className="mt-1 w-full rounded-lg border border-zinc-800 bg-zinc-900/60 px-3 py-2 text-sm text-zinc-200 focus:border-amber-500 focus:outline-none"
                            >
                                {available_priorities.map((priority) => (
                                    <option key={priority} value={priority}>
                                        {priorityLabels[priority] ?? priority}
                                    </option>
                                ))}
                            </select>
                            <InputError message={errors.priority} className="mt-2" />
                        </div>

                        <div>
                            <Label htmlFor="target_turn_number" className="text-xs uppercase tracking-wide text-zinc-500">
                                Target turn
                            </Label>
                            <Input
                                id="target_turn_number"
                                type="number"
                                min="1"
                                value={data.target_turn_number}
                                onChange={(event) => setData('target_turn_number', event.target.value)}
                                className="mt-1 bg-zinc-900/60"
                            />
                            <InputError message={errors.target_turn_number} className="mt-2" />
                        </div>

                        <div>
                            <Label htmlFor="starts_at" className="text-xs uppercase tracking-wide text-zinc-500">
                                Starts at
                            </Label>
                            <Input
                                id="starts_at"
                                type="datetime-local"
                                value={data.starts_at}
                                onChange={(event) => setData('starts_at', event.target.value)}
                                className="mt-1 bg-zinc-900/60"
                            />
                            <InputError message={errors.starts_at} className="mt-2" />
                        </div>

                        <div>
                            <Label htmlFor="completed_at" className="text-xs uppercase tracking-wide text-zinc-500">
                                Completed at
                            </Label>
                            <Input
                                id="completed_at"
                                type="datetime-local"
                                value={data.completed_at}
                                onChange={(event) => setData('completed_at', event.target.value)}
                                className="mt-1 bg-zinc-900/60"
                            />
                            <InputError message={errors.completed_at} className="mt-2" />
                        </div>

                        <div>
                            <Label htmlFor="archived_at" className="text-xs uppercase tracking-wide text-zinc-500">
                                Archived at
                            </Label>
                            <Input
                                id="archived_at"
                                type="datetime-local"
                                value={data.archived_at}
                                onChange={(event) => setData('archived_at', event.target.value)}
                                className="mt-1 bg-zinc-900/60"
                            />
                            <InputError message={errors.archived_at} className="mt-2" />
                        </div>
                    </div>
                </section>

                        <div className="flex items-center justify-end gap-3">
                            <Button
                                type="button"
                                variant="outline"
                                className="border-zinc-700 text-zinc-200 hover:bg-zinc-800/80"
                                onClick={() => window.history.back()}
                            >
                                Cancel
                            </Button>
                            <Button type="submit" disabled={processing} className="bg-emerald-500/20 text-emerald-100">
                                Save changes
                            </Button>
                        </div>
                    </form>
                </div>

                <aside className="space-y-4">
                    <AiIdeaPanel
                        endpoint={route('campaigns.ai.quests', campaign.id)}
                        title="Consult the quest mentor"
                        description="Outline twists, goals, or pacing adjustments and let the mentor suggest refined copy and objectives."
                        submitLabel="Suggest updates"
                        applyLabel="Apply idea"
                        onApply={applyQuestFields}
                    />

                    <section className="rounded-xl border border-indigo-500/30 bg-indigo-950/30 p-4 shadow-inner shadow-indigo-900/30">
                        <h3 className="text-sm font-semibold text-indigo-100">Quest presets</h3>
                        <p className="mt-1 text-xs text-indigo-200/80">Swap in an alternate quest concept if the story pivots.</p>
                        <ul className="mt-3 space-y-3">
                            {questPresets.map((preset) => (
                                <li key={preset.id} className="rounded-lg border border-indigo-500/30 bg-indigo-900/30 p-3">
                                    <p className="text-sm font-semibold text-indigo-100">{preset.title}</p>
                                    <p className="mt-1 text-xs text-indigo-200/80">{preset.summary}</p>
                                    <Button
                                        type="button"
                                        size="sm"
                                        className="mt-3 bg-indigo-500/30 text-indigo-100 hover:bg-indigo-500/40"
                                        onClick={() =>
                                            applyQuestFields({
                                                title: preset.title,
                                                summary: preset.summary,
                                                description: preset.details,
                                                objectives: preset.objectives,
                                            })
                                        }
                                    >
                                        Use this spark
                                    </Button>
                                </li>
                            ))}
                        </ul>
                    </section>
                </aside>
            </div>
        </AppLayout>
    );
}
