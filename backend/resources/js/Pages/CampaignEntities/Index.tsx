import { FormEvent } from 'react';

import { Head, Link, useForm } from '@inertiajs/react';

import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type CampaignSummary = {
    id: number;
    title: string;
    group: { id: number; name: string };
};

type EntityTag = {
    id: number;
    label: string;
    slug: string;
    color?: string | null;
};

type EntityListItem = {
    id: number;
    name: string;
    entity_type: string;
    alias: string | null;
    visibility: string;
    ai_controlled: boolean;
    tags: EntityTag[];
    owner: { id: number; name: string } | null;
};

type FilterState = {
    search: string;
    type: string;
    tag: string;
};

type CampaignEntityIndexProps = {
    campaign: CampaignSummary;
    entities: EntityListItem[];
    filters: FilterState;
    available_types: string[];
    available_tags: EntityTag[];
};

const typeLabels: Record<string, string> = {
    character: 'Character',
    npc: 'NPC',
    monster: 'Monster',
    item: 'Relic / Item',
    location: 'Location',
};

const visibilityLabels: Record<string, string> = {
    gm: 'GM Secret',
    players: 'Party Shared',
    public: 'Public Lore',
};

export default function CampaignEntityIndex({
    campaign,
    entities,
    filters,
    available_types,
    available_tags,
}: CampaignEntityIndexProps) {
    const { data, setData, get, processing } = useForm<FilterState>({
        search: filters.search ?? '',
        type: filters.type ?? '',
        tag: filters.tag ?? '',
    });

    const submitFilters = (event: FormEvent) => {
        event.preventDefault();

        get(route('campaigns.entities.index', campaign.id), {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const resetFilters = () => {
        setData({ search: '', type: '', tag: '' });

        get(route('campaigns.entities.index', campaign.id), {
            preserveScroll: true,
            replace: true,
        });
    };

    return (
        <AppLayout>
            <Head title={`${campaign.title} · Lore Codex`} />

            <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 className="text-3xl font-semibold text-zinc-100">Lore codex</h1>
                    <p className="mt-1 text-sm text-zinc-400">
                        Chronicle the legends, allies, rivals, and relics shaping this campaign.
                    </p>
                </div>

                <Button asChild className="bg-amber-500/20 text-amber-200 hover:bg-amber-500/30">
                    <Link href={route('campaigns.entities.create', campaign.id)}>Add lore entry</Link>
                </Button>
            </div>

            <form
                onSubmit={submitFilters}
                className="mt-6 grid gap-4 rounded-xl border border-zinc-800 bg-zinc-950/60 p-4 sm:grid-cols-4"
            >
                <div className="sm:col-span-2">
                    <Label htmlFor="search" className="text-xs uppercase tracking-wide text-zinc-500">
                        Search
                    </Label>
                    <Input
                        id="search"
                        name="search"
                        value={data.search}
                        onChange={(event) => setData('search', event.target.value)}
                        placeholder="Search by name, alias, or lore"
                        className="mt-1 border-zinc-700 bg-zinc-900/60 text-zinc-100"
                    />
                </div>

                <div>
                    <Label htmlFor="type" className="text-xs uppercase tracking-wide text-zinc-500">
                        Type
                    </Label>
                    <select
                        id="type"
                        name="type"
                        value={data.type}
                        onChange={(event) => setData('type', event.target.value)}
                        className="mt-1 w-full rounded-md border border-zinc-700 bg-zinc-900/60 px-3 py-2 text-sm text-zinc-100"
                    >
                        <option value="">All types</option>
                        {available_types.map((type) => (
                            <option key={type} value={type}>
                                {typeLabels[type] ?? type}
                            </option>
                        ))}
                    </select>
                </div>

                <div>
                    <Label htmlFor="tag" className="text-xs uppercase tracking-wide text-zinc-500">
                        Tag
                    </Label>
                    <select
                        id="tag"
                        name="tag"
                        value={data.tag}
                        onChange={(event) => setData('tag', event.target.value)}
                        className="mt-1 w-full rounded-md border border-zinc-700 bg-zinc-900/60 px-3 py-2 text-sm text-zinc-100"
                    >
                        <option value="">Any tag</option>
                        {available_tags.map((tag) => (
                            <option key={tag.slug} value={tag.slug}>
                                {tag.label}
                            </option>
                        ))}
                    </select>
                </div>

                <div className="flex items-end gap-2 sm:col-span-4">
                    <Button type="submit" disabled={processing} className="flex-1 bg-emerald-500/20 text-emerald-200">
                        Apply filters
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        onClick={resetFilters}
                        className="border-zinc-700 text-sm text-zinc-300"
                    >
                        Clear
                    </Button>
                </div>
            </form>

            <section className="mt-8 grid gap-4 lg:grid-cols-2 xl:grid-cols-3">
                {entities.length === 0 ? (
                    <p className="rounded-xl border border-dashed border-zinc-800 bg-zinc-950/40 p-6 text-sm text-zinc-400">
                        No lore entries found. Start chronicling this world’s legends with the “Add lore entry” button.
                    </p>
                ) : (
                    entities.map((entity) => (
                        <article
                            key={entity.id}
                            className="flex h-full flex-col justify-between rounded-xl border border-zinc-800 bg-zinc-950/60 p-5 shadow-inner shadow-black/20"
                        >
                            <div className="space-y-2">
                                <div className="flex items-center justify-between">
                                    <h2 className="text-xl font-semibold text-zinc-100">
                                        <Link
                                            href={route('campaigns.entities.show', [campaign.id, entity.id])}
                                            className="hover:text-amber-300"
                                        >
                                            {entity.name}
                                        </Link>
                                    </h2>
                                    <span className="rounded-full bg-indigo-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-indigo-300">
                                        {typeLabels[entity.entity_type] ?? entity.entity_type}
                                    </span>
                                </div>
                                {entity.alias && (
                                    <p className="text-sm italic text-zinc-400">“{entity.alias}”</p>
                                )}
                                <div className="flex flex-wrap items-center gap-2 text-xs text-zinc-400">
                                    <span className="rounded border border-zinc-700/80 px-2 py-0.5 font-semibold uppercase tracking-wide text-zinc-300">
                                        {visibilityLabels[entity.visibility] ?? entity.visibility}
                                    </span>
                                    {entity.ai_controlled && (
                                        <span className="rounded bg-emerald-500/10 px-2 py-0.5 font-semibold text-emerald-200">
                                            AI guided
                                        </span>
                                    )}
                                    {entity.owner && (
                                        <span className="rounded bg-zinc-800 px-2 py-0.5 text-zinc-300">
                                            Steward: {entity.owner.name}
                                        </span>
                                    )}
                                </div>
                            </div>

                            <footer className="mt-4 flex flex-wrap gap-2">
                                {entity.tags.length === 0 ? (
                                    <span className="text-xs text-zinc-500">No tags yet</span>
                                ) : (
                                    entity.tags.map((tag) => (
                                        <span
                                            key={tag.slug}
                                            className="rounded-full px-3 py-1 text-xs font-semibold"
                                            style={{
                                                backgroundColor: tag.color ?? 'rgba(63,63,70,0.5)',
                                                color: '#0f172a',
                                            }}
                                        >
                                            {tag.label}
                                        </span>
                                    ))
                                )}
                            </footer>
                        </article>
                    ))
                )}
            </section>
        </AppLayout>
    );
}
