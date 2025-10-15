import { FormEventHandler } from 'react';

import { Head, Link, useForm } from '@inertiajs/react';

import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { InputError } from '@/components/InputError';

type RegionOption = {
    id: number;
    name: string;
};

type CampaignQuestCreateProps = {
    campaign: { id: number; title: string };
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

export default function CampaignQuestCreate({ campaign, available_statuses, available_priorities, regions }: CampaignQuestCreateProps) {
    const { data, setData, post, processing, errors } = useForm({
        title: '',
        summary: '',
        details: '',
        status: available_statuses[0] ?? 'planned',
        priority: available_priorities[0] ?? 'standard',
        region_id: '',
        target_turn_number: '',
        starts_at: '',
        completed_at: '',
    });

    const submit: FormEventHandler<HTMLFormElement> = (event) => {
        event.preventDefault();

        post(route('campaigns.quests.store', campaign.id));
    };

    return (
        <AppLayout>
            <Head title={`${campaign.title} · Add quest`} />

            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-3xl font-semibold text-zinc-100">Add quest</h1>
                    <p className="mt-1 text-sm text-zinc-400">
                        Chronicle a new narrative thread for {campaign.title}.
                    </p>
                </div>

                <Button asChild variant="outline" className="border-zinc-700 text-zinc-200 hover:bg-zinc-800/80">
                    <Link href={route('campaigns.quests.index', campaign.id)}>Back to quest log</Link>
                </Button>
            </div>

            <form onSubmit={submit} className="mt-6 space-y-6">
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
                                placeholder="Quest name"
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
                            placeholder="High-level goal, stakes, or rumors"
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
                            placeholder="Clues, leads, rewards, NPC ties, or twists"
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
                                placeholder="Optional turn number"
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
                        Save quest
                    </Button>
                </div>
            </form>
        </AppLayout>
    );
}
