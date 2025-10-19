import { FormEventHandler, useMemo } from 'react';

import { Head, Link, useForm } from '@inertiajs/react';

import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { InputError } from '@/components/InputError';
import AiIdeaPanel, { AiIdeaResult } from '@/components/ai/AiIdeaPanel';

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

type StructuredQuest = {
    title?: string;
    summary?: string;
    status?: string;
    objectives?: string[];
    complications?: string[];
    rewards?: string[];
};

const isStructuredQuest = (value: unknown): value is StructuredQuest =>
    typeof value === 'object' && value !== null;

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

    const regionLookup = useMemo(() => Object.fromEntries(regions.map((region) => [String(region.id), region.name])), [regions]);

    const submit: FormEventHandler<HTMLFormElement> = (event) => {
        event.preventDefault();

        put(route('campaigns.quests.update', [campaign.id, quest.id]));
    };

    const handleDelete = () => {
        destroy(route('campaigns.quests.destroy', [campaign.id, quest.id]));
    };

    const matchStatus = (value?: string): string | null => {
        if (!value) {
            return null;
        }

        const lower = value.toLowerCase();
        const direct = available_statuses.find((status) => status.toLowerCase() === lower);
        if (direct) {
            return direct;
        }

        const byLabel = available_statuses.find((status) => (statusLabels[status] ?? status).toLowerCase() === lower);
        return byLabel ?? null;
    };

    return (
        <AppLayout>
            <Head title={`${campaign.title} · Edit quest`} />

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

            <div className="mt-6 grid gap-8 xl:grid-cols-[2fr_1fr]">
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
                                    Starts at (UTC)
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
                                    Completed at (UTC)
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
                        </div>
                    </section>

                    <div className="flex items-center justify-between">
                        <Button type="submit" disabled={processing}>
                            Save changes
                        </Button>
                        <Button asChild variant="ghost">
                            <Link href={route('campaigns.quests.show', [campaign.id, quest.id])}>Cancel</Link>
                        </Button>
                    </div>
                </form>

                <AiIdeaPanel
                    domain="quest"
                    title="Rework this quest"
                    description="Ask the AI to tighten objectives, add twists, or suggest art prompts as the campaign evolves."
                    placeholder={`Update complications for ${quest.title}`}
                    context={{
                        campaign_title: campaign.title,
                        title: data.title,
                        region: regionLookup[data.region_id ?? ''],
                        status: data.status,
                        summary: data.summary,
                    }}
                    actions={[
                        {
                            label: 'Refresh content',
                            onApply: (result: AiIdeaResult) => {
                                const questData = isStructuredQuest(result.structured) ? result.structured : null;
                                const detailsSections: string[] = [];

                                if (questData && typeof questData.summary === 'string' && questData.summary !== '') {
                                    setData('summary', questData.summary);
                                } else if (typeof result.text === 'string' && result.text !== '') {
                                    setData('summary', result.text);
                                }

                                if (questData && typeof questData.title === 'string' && questData.title !== '') {
                                    setData('title', questData.title);
                                }

                                if (questData && Array.isArray(questData.objectives)) {
                                    const items = questData.objectives.filter((item): item is string => typeof item === 'string' && item !== '');
                                    if (items.length > 0) {
                                        detailsSections.push('Objectives:', ...items.map((item) => `• ${item}`));
                                    }
                                }

                                if (questData && Array.isArray(questData.complications)) {
                                    const items = questData.complications.filter((item): item is string => typeof item === 'string' && item !== '');
                                    if (items.length > 0) {
                                        detailsSections.push('', 'Complications:', ...items.map((item) => `• ${item}`));
                                    }
                                }

                                if (questData && Array.isArray(questData.rewards)) {
                                    const items = questData.rewards.filter((item): item is string => typeof item === 'string' && item !== '');
                                    if (items.length > 0) {
                                        detailsSections.push('', 'Rewards:', ...items.map((item) => `• ${item}`));
                                    }
                                }

                                if (detailsSections.length > 0) {
                                    setData('details', detailsSections.join('\n'));
                                } else if (typeof result.text === 'string' && result.text !== '') {
                                    setData('details', result.text);
                                }

                                const statusKey = questData ? matchStatus(questData.status) : null;
                                if (statusKey) {
                                    setData('status', statusKey);
                                }
                            },
                        },
                    ]}
                />
            </div>
        </AppLayout>
    );
}
