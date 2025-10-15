import { Head, Link } from '@inertiajs/react';

import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';

type CampaignSummary = {
    id: number;
    title: string;
};

type EntityTag = {
    id: number;
    label: string;
    slug: string;
    color?: string | null;
};

type RelatedRecord = {
    id: number;
    name: string;
} | null;

type StatEntry = {
    label: string;
    value: string | null;
};

type EntityPayload = {
    id: number;
    name: string;
    entity_type: string;
    alias: string | null;
    pronunciation: string | null;
    visibility: string;
    ai_controlled: boolean;
    initiative_default: number | null;
    description: string | null;
    stats: StatEntry[] | null;
    group: RelatedRecord;
    owner: RelatedRecord;
    tags: EntityTag[];
};

type CampaignEntityShowProps = {
    campaign: CampaignSummary;
    entity: EntityPayload;
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
    players: 'Shared with party',
    public: 'Public lore',
};

export default function CampaignEntityShow({ campaign, entity }: CampaignEntityShowProps) {
    return (
        <AppLayout>
            <Head title={`${entity.name} · ${campaign.title}`} />

            <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 className="text-3xl font-semibold text-zinc-100">{entity.name}</h1>
                    <p className="mt-1 text-sm text-zinc-400">
                        {typeLabels[entity.entity_type] ?? entity.entity_type} ·{' '}
                        {visibilityLabels[entity.visibility] ?? entity.visibility}
                    </p>
                    {entity.alias && (
                        <p className="mt-1 text-sm italic text-zinc-400">Known as “{entity.alias}”.</p>
                    )}
                    {entity.pronunciation && (
                        <p className="text-sm text-zinc-500">Pronunciation: {entity.pronunciation}</p>
                    )}
                </div>

                <div className="flex items-center gap-3">
                    <Button asChild variant="outline" className="border-zinc-700 text-sm text-zinc-300">
                        <Link href={route('campaigns.entities.index', campaign.id)}>Back to codex</Link>
                    </Button>
                    <Button asChild className="bg-amber-500/30 text-amber-100">
                        <Link href={route('campaigns.entities.edit', [campaign.id, entity.id])}>Edit entry</Link>
                    </Button>
                </div>
            </div>

            <section className="mt-6 grid gap-6 lg:grid-cols-3">
                <article className="lg:col-span-2 space-y-6 rounded-xl border border-zinc-800 bg-zinc-950/60 p-6 shadow-inner shadow-black/30">
                    <div className="flex flex-wrap items-center gap-3 text-xs uppercase tracking-wide text-zinc-400">
                        {entity.ai_controlled && (
                            <span className="rounded bg-emerald-500/10 px-3 py-1 text-emerald-200">AI stewarded</span>
                        )}
                        {entity.initiative_default !== null && (
                            <span className="rounded bg-indigo-500/10 px-3 py-1 text-indigo-200">
                                Initiative: {entity.initiative_default}
                            </span>
                        )}
                        {entity.owner && (
                            <span className="rounded bg-zinc-800 px-3 py-1 text-zinc-300">Steward {entity.owner.name}</span>
                        )}
                        {entity.group && (
                            <span className="rounded bg-zinc-800 px-3 py-1 text-zinc-300">Linked to {entity.group.name}</span>
                        )}
                    </div>

                    <div className="prose prose-invert max-w-none">
                        {entity.description ? (
                            <p className="whitespace-pre-wrap text-sm leading-relaxed text-zinc-100">{entity.description}</p>
                        ) : (
                            <p className="text-sm text-zinc-500">No lore recorded yet. Add a summary to brief storytellers.</p>
                        )}
                    </div>

                    <div>
                        <h2 className="text-sm font-semibold uppercase tracking-wide text-zinc-500">Tags</h2>
                        <div className="mt-2 flex flex-wrap gap-2">
                            {entity.tags.length === 0 ? (
                                <span className="text-xs text-zinc-500">No tags assigned.</span>
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
                        </div>
                    </div>
                </article>

                <aside className="space-y-4 rounded-xl border border-zinc-800 bg-zinc-950/60 p-6 shadow-inner shadow-black/30">
                    <header>
                        <h2 className="text-lg font-semibold text-zinc-100">Stat block</h2>
                        <p className="text-xs uppercase tracking-wide text-zinc-500">Quick reference for combat or roleplay</p>
                    </header>

                    <dl className="space-y-3">
                        {entity.stats && entity.stats.length > 0 ? (
                            entity.stats.map((stat, index) => (
                                <div key={index} className="rounded-lg border border-zinc-800/70 bg-zinc-900/60 px-4 py-3">
                                    <dt className="text-xs uppercase tracking-wide text-zinc-500">{stat.label}</dt>
                                    <dd className="text-sm text-zinc-100">{stat.value ?? '—'}</dd>
                                </div>
                            ))
                        ) : (
                            <p className="text-sm text-zinc-500">No stat entries yet. Add baseline numbers in the edit view.</p>
                        )}
                    </dl>
                </aside>
            </section>
        </AppLayout>
    );
}
