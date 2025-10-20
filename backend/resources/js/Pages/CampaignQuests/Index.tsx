import { FormEvent, useState } from 'react';

import { Head, Link, useForm } from '@inertiajs/react';

import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AiIdeaPanel from '@/components/ai/AiIdeaPanel';

type CampaignSummary = {
    id: number;
    title: string;
    group: { id: number; name: string };
};

type QuestUpdateSummary = {
    id: number;
    summary: string;
    recorded_at: string | null;
    author: { id: number; name: string } | null;
} | null;

type QuestListItem = {
    id: number;
    title: string;
    summary: string;
    status: string;
    priority: string;
    region: { id: number; name: string } | null;
    updated_at: string | null;
    latest_update: QuestUpdateSummary;
};

type FilterState = {
    search: string;
    status: string;
    priority: string;
    region: string;
    include_archived: boolean;
};

type RegionOption = {
    id: number;
    name: string;
};

type CampaignQuestIndexProps = {
    campaign: CampaignSummary;
    quests: QuestListItem[];
    filters: FilterState;
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

export default function CampaignQuestIndex({
    campaign,
    quests,
    filters,
    available_statuses,
    available_priorities,
    regions,
}: CampaignQuestIndexProps) {
    const { data, setData, get, processing } = useForm<FilterState>({
        search: filters.search ?? '',
        status: filters.status ?? '',
        priority: filters.priority ?? '',
        region: filters.region ?? '',
        include_archived: Boolean(filters.include_archived),
    });
    const [aiPromptSeed, setAiPromptSeed] = useState('Rescue a kidnapped diplomat before the moon ritual ends.');
    const [panelKey, setPanelKey] = useState(0);

    const quickPrompts = [
        'Investigate sabotage on the skyship docks',
        'Negotiate peace between rival clans',
        'Escort a caravan through cursed wetlands',
        'Race against a rival guild for an ancient map',
    ];

    const submitFilters = (event: FormEvent) => {
        event.preventDefault();

        get(route('campaigns.quests.index', campaign.id), {
            preserveScroll: true,
            replace: true,
        });
    };

    const resetFilters = () => {
        setData({ search: '', status: '', priority: '', region: '', include_archived: false });

        get(route('campaigns.quests.index', campaign.id), {
            preserveScroll: true,
            replace: true,
        });
    };

    const handlePromptChip = (prompt: string) => {
        setAiPromptSeed(prompt);
        setPanelKey((value) => value + 1);
    };

    return (
        <AppLayout>
            <Head title={`${campaign.title} · Quest Log`} />

            <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 className="text-3xl font-semibold text-zinc-100">Quest log</h1>
                    <p className="mt-1 text-sm text-zinc-400">
                        Track story arcs, spotlight their priority, and chronicle party updates in one ledger.
                    </p>
                </div>

                <Button asChild className="bg-amber-500/20 text-amber-100 hover:bg-amber-500/30">
                    <Link href={route('campaigns.quests.create', campaign.id)}>Add quest</Link>
                </Button>
            </div>

            <form
                onSubmit={submitFilters}
                className="mt-6 grid gap-4 rounded-xl border border-zinc-800 bg-zinc-950/60 p-4 sm:grid-cols-6"
            >
                <div className="sm:col-span-2">
                    <Label htmlFor="search" className="text-xs uppercase tracking-wide text-zinc-500">
                        Search
                    </Label>
                    <Input
                        id="search"
                        value={data.search}
                        onChange={(event) => setData('search', event.target.value)}
                        placeholder="Search by title or summary"
                        className="mt-1 bg-zinc-900/60"
                    />
                </div>

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
                        <option value="">All statuses</option>
                        {available_statuses.map((status) => (
                            <option key={status} value={status}>
                                {statusLabels[status] ?? status}
                            </option>
                        ))}
                    </select>
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
                        <option value="">All priorities</option>
                        {available_priorities.map((priority) => (
                            <option key={priority} value={priority}>
                                {priorityLabels[priority] ?? priority}
                            </option>
                        ))}
                    </select>
                </div>

                <div>
                    <Label htmlFor="region" className="text-xs uppercase tracking-wide text-zinc-500">
                        Region
                    </Label>
                    <select
                        id="region"
                        value={data.region}
                        onChange={(event) => setData('region', event.target.value)}
                        className="mt-1 w-full rounded-lg border border-zinc-800 bg-zinc-900/60 px-3 py-2 text-sm text-zinc-200 focus:border-amber-500 focus:outline-none"
                    >
                        <option value="">All regions</option>
                        {regions.map((region) => (
                            <option key={region.id} value={String(region.id)}>
                                {region.name}
                            </option>
                        ))}
                    </select>
                </div>

                <div className="flex items-center gap-2 pt-6">
                    <input
                        id="include_archived"
                        type="checkbox"
                        checked={data.include_archived}
                        onChange={(event) => setData('include_archived', event.target.checked)}
                        className="h-4 w-4 rounded border-zinc-700 bg-zinc-900 text-amber-500 focus:ring-amber-500"
                    />
                    <Label htmlFor="include_archived" className="text-sm text-zinc-300">
                        Include archived quests
                    </Label>
                </div>

                <div className="flex items-end justify-end gap-3 sm:col-span-2">
                    <Button
                        type="button"
                        variant="outline"
                        className="border-zinc-700 text-zinc-200 hover:bg-zinc-800/80"
                        onClick={resetFilters}
                    >
                        Reset
                    </Button>
                    <Button type="submit" disabled={processing} className="bg-emerald-500/20 text-emerald-100">
                        Apply
                    </Button>
                </div>

            </form>

            <section className="mt-8 grid gap-6 rounded-xl border border-rose-500/40 bg-rose-500/10 p-6 shadow-inner shadow-black/30 lg:grid-cols-[1.6fr_1fr]">
                <div className="space-y-3">
                    <h2 className="text-lg font-semibold text-rose-100">Spin up quests fast</h2>
                    <p className="text-sm text-rose-100/80">
                        The quest steward turns short prompts into objectives, complications, rewards, and Automatic1111 prompts ready for map tiles or key art.
                    </p>
                    <div className="flex flex-wrap gap-2">
                        {quickPrompts.map((prompt) => (
                            <button
                                key={prompt}
                                type="button"
                                onClick={() => handlePromptChip(prompt)}
                                className="rounded-full border border-rose-400/50 bg-rose-500/20 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-rose-50 hover:bg-rose-500/30"
                            >
                                {prompt}
                            </button>
                        ))}
                    </div>
                </div>
                <AiIdeaPanel
                    key={panelKey}
                    domain="quest"
                    endpoint={route('campaigns.ai.quests', campaign.id)}
                    title="Quest steward"
                    description="Share a mission seed; receive structured quest details and a 512×512 prompt for map renders."
                    defaultPrompt={aiPromptSeed}
                    context={{
                        campaign_title: campaign.title,
                        region_filter: data.region,
                        priority_filter: data.priority,
                    }}
                />
            </section>

            {quests.length === 0 ? (
                <div className="mt-6 rounded-xl border border-dashed border-zinc-800 bg-zinc-950/40 p-8 text-center text-sm text-zinc-400">
                    No quests logged yet. Chronicle your campaign threads to guide session prep and spotlight looming threats.
                </div>
            ) : (
                <div className="mt-6 space-y-4">
                    {quests.map((quest) => (
                        <article
                            key={quest.id}
                            className="rounded-xl border border-zinc-800 bg-zinc-950/60 p-5 shadow-inner shadow-black/40 transition hover:border-amber-500/40 hover:bg-zinc-900/60"
                        >
                            <header className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <h2 className="text-lg font-semibold text-zinc-100">{quest.title}</h2>
                                    <p className="mt-1 text-sm text-zinc-400">{quest.summary || 'No summary provided yet.'}</p>
                                </div>

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
                            </header>

                            {quest.latest_update && (
                                <div className="mt-4 rounded-lg border border-zinc-800 bg-zinc-900/40 p-4 text-sm text-zinc-300">
                                    <p className="font-medium text-zinc-100">Latest update</p>
                                    <p className="mt-1 text-zinc-300">{quest.latest_update.summary}</p>
                                    <p className="mt-2 text-xs uppercase tracking-wide text-zinc-500">
                                        {quest.latest_update.author ? quest.latest_update.author.name : 'Unknown chronicler'} ·{' '}
                                        {quest.latest_update.recorded_at
                                            ? new Date(quest.latest_update.recorded_at).toLocaleString()
                                            : 'Recently logged'}
                                    </p>
                                </div>
                            )}

                            <footer className="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <p className="text-xs uppercase tracking-wide text-zinc-500">
                                    Updated {quest.updated_at ? new Date(quest.updated_at).toLocaleString() : 'recently'}
                                </p>
                                <Button asChild variant="outline" className="border-amber-500/40 text-amber-100 hover:bg-amber-500/10">
                                    <Link href={route('campaigns.quests.show', [campaign.id, quest.id])}>Open quest</Link>
                                </Button>
                            </footer>
                        </article>
                    ))}
                </div>
            )}
        </AppLayout>
    );
}
