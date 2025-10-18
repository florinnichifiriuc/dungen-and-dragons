import { FormEvent, useState } from 'react';

import { Head, Link, router, useForm } from '@inertiajs/react';

import AppLayout from '@/Layouts/AppLayout';
import { AiIdeaPanel, type AiIdeaResult } from '@/components/AiIdeaPanel';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { InputError } from '@/components/InputError';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

import { lorePresets } from './lorePresets';

type CampaignSummary = {
    id: number;
    title: string;
};

type TagOption = {
    id: number;
    label: string;
    slug: string;
};

type GroupSummary = {
    id: number;
    name: string;
};

type MemberSummary = {
    id: number;
    name: string;
};

type StatEntry = {
    label: string;
    value: string;
};

type EntityPayload = {
    id: number;
    entity_type: string;
    name: string;
    alias: string | null;
    pronunciation: string | null;
    visibility: string;
    group_id: number | null;
    owner_id: number | null;
    ai_controlled: boolean;
    initiative_default: number | null;
    description: string | null;
    stats: StatEntry[] | null;
    tags: string[];
};

type CampaignEntityEditProps = {
    campaign: CampaignSummary;
    entity: EntityPayload;
    available_types: string[];
    visibility_options: string[];
    available_tags: TagOption[];
    group: GroupSummary;
    members: MemberSummary[];
};

const typeLabels: Record<string, string> = {
    character: 'Character',
    npc: 'NPC',
    monster: 'Monster',
    item: 'Relic / Item',
    location: 'Location',
};

const visibilityLabels: Record<string, string> = {
    gm: 'GM secret',
    players: 'Shared with party',
    public: 'Public lore',
};

const ensureStats = (stats: StatEntry[] | null | undefined): StatEntry[] => {
    if (!stats || stats.length === 0) {
        return [{ label: '', value: '' }];
    }

    return stats.map((entry) => ({
        label: entry.label ?? '',
        value: entry.value ?? '',
    }));
};

export default function CampaignEntityEdit({
    campaign,
    entity,
    available_types,
    visibility_options,
    available_tags,
    group,
    members,
}: CampaignEntityEditProps) {
    const [tagDraft, setTagDraft] = useState('');

    const { data, setData, put, processing, errors } = useForm({
        entity_type: entity.entity_type,
        name: entity.name,
        alias: entity.alias ?? '',
        pronunciation: entity.pronunciation ?? '',
        visibility: entity.visibility,
        group_id: entity.group_id ? String(entity.group_id) : '',
        owner_id: entity.owner_id ? String(entity.owner_id) : '',
        ai_controlled: entity.ai_controlled,
        initiative_default: entity.initiative_default?.toString() ?? '',
        description: entity.description ?? '',
        stats: ensureStats(entity.stats),
        tags: entity.tags ?? [],
    });

    const submit: FormEvent<HTMLFormElement> = (event) => {
        event.preventDefault();

        put(route('campaigns.entities.update', [campaign.id, entity.id]));
    };

    const toggleTag = (label: string) => {
        setData(
            'tags',
            data.tags.includes(label)
                ? data.tags.filter((tag) => tag !== label)
                : [...data.tags, label]
        );
    };

    const addTagDraft = () => {
        const value = tagDraft.trim();
        if (value.length === 0) {
            return;
        }

        if (!data.tags.includes(value)) {
            setData('tags', [...data.tags, value]);
        }

        setTagDraft('');
    };

    const applyLoreFields = (fields: Record<string, unknown>, result?: AiIdeaResult) => {
        if (typeof fields.name === 'string' && fields.name.trim() !== '') {
            setData('name', fields.name.trim());
        }

        if (typeof fields.alias === 'string') {
            setData('alias', fields.alias.trim());
        }

        if (typeof fields.pronunciation === 'string') {
            setData('pronunciation', fields.pronunciation.trim());
        }

        if (typeof fields.entity_type === 'string') {
            const type = fields.entity_type.trim();
            if (available_types.includes(type)) {
                setData('entity_type', type);
            }
        }

        if (typeof fields.visibility === 'string') {
            const visibility = fields.visibility.trim();
            if (visibility_options.includes(visibility)) {
                setData('visibility', visibility);
            }
        }

        if (typeof fields.ai_controlled === 'boolean') {
            setData('ai_controlled', fields.ai_controlled);
        }

        const tags: string[] = Array.isArray(fields.tags)
            ? fields.tags
                  .map((tag) => (typeof tag === 'string' ? tag.trim() : ''))
                  .filter((tag) => tag !== '')
            : typeof fields.tags === 'string'
              ? fields.tags
                    .split(/[,#\n]+/)
                    .map((tag) => tag.trim())
                    .filter((tag) => tag !== '')
              : [];

        if (tags.length > 0) {
            setData('tags', Array.from(new Set(tags)));
        }

        if (typeof fields.description === 'string' && fields.description.trim() !== '') {
            setData('description', fields.description.trim());
        } else if (typeof fields.summary === 'string' && fields.summary.trim() !== '') {
            setData('description', fields.summary.trim());
        } else if (result?.summary && result.summary.trim() !== '') {
            setData('description', result.summary.trim());
        }
    };

    const updateStat = (index: number, field: keyof StatEntry, value: string) => {
        const stats = data.stats.slice();
        stats[index] = { ...stats[index], [field]: value };
        setData('stats', stats);
    };

    const addStatRow = () => {
        setData('stats', [...data.stats, { label: '', value: '' }]);
    };

    const removeStatRow = (index: number) => {
        const next = data.stats.slice();
        next.splice(index, 1);
        setData('stats', next.length > 0 ? next : [{ label: '', value: '' }]);
    };

    const destroy = () => {
        if (window.confirm('Archive this lore entry? It can be restored from the database later.')) {
            router.delete(route('campaigns.entities.destroy', [campaign.id, entity.id]));
        }
    };

    return (
        <AppLayout>
            <Head title={`Edit lore · ${entity.name}`} />

            <div className="mx-auto grid max-w-6xl gap-6 lg:grid-cols-[minmax(0,2.5fr),minmax(0,1fr)]">
                <div className="space-y-6">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h1 className="text-3xl font-semibold text-zinc-100">Edit lore entry</h1>
                            <p className="mt-1 text-sm text-zinc-400">
                                Update details as the story evolves. Keep tags and stats aligned with the latest chronicles.
                            </p>
                        </div>
                        <div className="flex items-center gap-3">
                            <Button asChild variant="outline" className="border-zinc-700 text-sm text-zinc-300">
                                <Link href={route('campaigns.entities.show', [campaign.id, entity.id])}>View entry</Link>
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={destroy}
                                className="border-rose-700/60 text-sm text-rose-200 hover:bg-rose-900/40"
                            >
                                Archive
                            </Button>
                        </div>
                    </div>

                    <form onSubmit={submit} className="space-y-8">
                <section className="rounded-xl border border-zinc-800 bg-zinc-950/60 p-6 shadow-inner shadow-black/30">
                    <h2 className="text-lg font-semibold text-zinc-100">Essentials</h2>
                    <p className="text-sm text-zinc-500">
                        Provide a name and optional epithets so storytellers can reference this entity during play.
                    </p>

                    <div className="mt-4 grid gap-4 md:grid-cols-2">
                        <div>
                            <Label htmlFor="name">Name</Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(event) => setData('name', event.target.value)}
                                className="mt-1 border-zinc-700 bg-zinc-900/60 text-zinc-100"
                                required
                            />
                            <InputError message={errors.name} className="mt-2" />
                        </div>

                        <div>
                            <Label htmlFor="alias">Alias / epithet</Label>
                            <Input
                                id="alias"
                                value={data.alias}
                                onChange={(event) => setData('alias', event.target.value)}
                                placeholder="e.g., The Whispered Blade"
                                className="mt-1 border-zinc-700 bg-zinc-900/60 text-zinc-100"
                            />
                            <InputError message={errors.alias} className="mt-2" />
                        </div>

                        <div>
                            <Label htmlFor="pronunciation">Pronunciation</Label>
                            <Input
                                id="pronunciation"
                                value={data.pronunciation}
                                onChange={(event) => setData('pronunciation', event.target.value)}
                                placeholder="Optional pronunciation guide"
                                className="mt-1 border-zinc-700 bg-zinc-900/60 text-zinc-100"
                            />
                            <InputError message={errors.pronunciation} className="mt-2" />
                        </div>

                        <div>
                            <Label htmlFor="entity_type">Entity type</Label>
                            <select
                                id="entity_type"
                                value={data.entity_type}
                                onChange={(event) => setData('entity_type', event.target.value)}
                                className="mt-1 w-full rounded-md border border-zinc-700 bg-zinc-900/60 px-3 py-2 text-sm text-zinc-100"
                            >
                                {available_types.map((type) => (
                                    <option key={type} value={type}>
                                        {typeLabels[type] ?? type}
                                    </option>
                                ))}
                            </select>
                            <InputError message={errors.entity_type} className="mt-2" />
                        </div>
                    </div>
                </section>

                <section className="rounded-xl border border-zinc-800 bg-zinc-950/60 p-6 shadow-inner shadow-black/30">
                    <h2 className="text-lg font-semibold text-zinc-100">Lore & stewardship</h2>
                    <p className="text-sm text-zinc-500">
                        Note who tends this entry and how visible it is to the party.
                    </p>

                    <div className="mt-4 grid gap-4 md:grid-cols-2">
                        <div>
                            <Label htmlFor="visibility">Visibility</Label>
                            <select
                                id="visibility"
                                value={data.visibility}
                                onChange={(event) => setData('visibility', event.target.value)}
                                className="mt-1 w-full rounded-md border border-zinc-700 bg-zinc-900/60 px-3 py-2 text-sm text-zinc-100"
                            >
                                {visibility_options.map((visibility) => (
                                    <option key={visibility} value={visibility}>
                                        {visibilityLabels[visibility] ?? visibility}
                                    </option>
                                ))}
                            </select>
                            <InputError message={errors.visibility} className="mt-2" />
                        </div>

                        <div>
                            <Label htmlFor="owner_id">Steward</Label>
                            <select
                                id="owner_id"
                                value={data.owner_id}
                                onChange={(event) => setData('owner_id', event.target.value)}
                                className="mt-1 w-full rounded-md border border-zinc-700 bg-zinc-900/60 px-3 py-2 text-sm text-zinc-100"
                            >
                                <option value="">Unassigned</option>
                                {members.map((member) => (
                                    <option key={member.id} value={member.id}>
                                        {member.name}
                                    </option>
                                ))}
                            </select>
                            <InputError message={errors.owner_id} className="mt-2" />
                        </div>

                        <div>
                            <Label htmlFor="group_id">Group alignment</Label>
                            <select
                                id="group_id"
                                value={data.group_id}
                                onChange={(event) => setData('group_id', event.target.value)}
                                className="mt-1 w-full rounded-md border border-zinc-700 bg-zinc-900/60 px-3 py-2 text-sm text-zinc-100"
                            >
                                <option value="">No dedicated group</option>
                                <option value={group.id}>{group.name}</option>
                            </select>
                            <InputError message={errors.group_id} className="mt-2" />
                        </div>

                        <div>
                            <Label htmlFor="initiative_default">Initiative default</Label>
                            <Input
                                id="initiative_default"
                                type="number"
                                min={0}
                                max={40}
                                value={data.initiative_default}
                                onChange={(event) => setData('initiative_default', event.target.value)}
                                placeholder="Optional initiative baseline"
                                className="mt-1 border-zinc-700 bg-zinc-900/60 text-zinc-100"
                            />
                            <InputError message={errors.initiative_default} className="mt-2" />
                        </div>
                    </div>

                    <div className="mt-4 flex items-center gap-2">
                        <Checkbox
                            id="ai_controlled"
                            checked={data.ai_controlled}
                            onCheckedChange={(checked) => setData('ai_controlled', checked === true)}
                        />
                        <Label htmlFor="ai_controlled" className="text-sm text-zinc-300">
                            AI stewarded lore entry
                        </Label>
                        <InputError message={errors.ai_controlled} className="ml-2" />
                    </div>

                    <div className="mt-4">
                        <Label htmlFor="description">Lore summary</Label>
                        <Textarea
                            id="description"
                            value={data.description}
                            onChange={(event) => setData('description', event.target.value)}
                            placeholder="Describe their history, motives, or significance. Markdown supported."
                            className="mt-1 min-h-[160px] border-zinc-700 bg-zinc-900/60 text-sm text-zinc-100"
                        />
                        <InputError message={errors.description} className="mt-2" />
                    </div>
                </section>

                <section className="rounded-xl border border-zinc-800 bg-zinc-950/60 p-6 shadow-inner shadow-black/30">
                    <h2 className="text-lg font-semibold text-zinc-100">Stat blocks</h2>
                    <p className="text-sm text-zinc-500">
                        Track notable traits or mechanics (AC, HP, key abilities) for quick reference.
                    </p>

                    <div className="mt-4 space-y-4">
                        {data.stats.map((stat, index) => (
                            <div key={index} className="flex flex-col gap-2 rounded-lg border border-zinc-800/80 p-4 md:flex-row">
                                <div className="md:w-1/3">
                                    <Label>Label</Label>
                                    <Input
                                        value={stat.label}
                                        onChange={(event) => updateStat(index, 'label', event.target.value)}
                                        placeholder="e.g., Armor Class"
                                        className="mt-1 border-zinc-700 bg-zinc-900/60 text-zinc-100"
                                    />
                                </div>
                                <div className="md:w-1/3">
                                    <Label>Value</Label>
                                    <Input
                                        value={stat.value}
                                        onChange={(event) => updateStat(index, 'value', event.target.value)}
                                        placeholder="e.g., 16 (chain mail)"
                                        className="mt-1 border-zinc-700 bg-zinc-900/60 text-zinc-100"
                                    />
                                </div>
                                <div className="flex items-end justify-end md:w-1/3">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() => removeStatRow(index)}
                                        className="border-rose-700/60 text-sm text-rose-200 hover:bg-rose-900/40"
                                    >
                                        Remove
                                    </Button>
                                </div>
                            </div>
                        ))}

                        <Button
                            type="button"
                            onClick={addStatRow}
                            className="w-full bg-indigo-500/20 text-indigo-200 hover:bg-indigo-500/30"
                        >
                            Add another stat
                        </Button>
                        <InputError message={errors.stats} />
                    </div>
                </section>

                <section className="rounded-xl border border-zinc-800 bg-zinc-950/60 p-6 shadow-inner shadow-black/30">
                    <h2 className="text-lg font-semibold text-zinc-100">Tags & themes</h2>
                    <p className="text-sm text-zinc-500">
                        Tag entries for quicker discovery during play (factions, arc names, mood cues).
                    </p>

                    <div className="mt-4 flex flex-wrap gap-2">
                        {data.tags.length === 0 ? (
                            <span className="text-xs text-zinc-500">No tags selected yet.</span>
                        ) : (
                            data.tags.map((tag) => (
                                <span
                                    key={tag}
                                    className="flex items-center gap-2 rounded-full bg-amber-500/10 px-3 py-1 text-xs text-amber-100"
                                >
                                    {tag}
                                    <button
                                        type="button"
                                        onClick={() => toggleTag(tag)}
                                        className="rounded-full bg-amber-500/40 px-2 text-[10px] uppercase tracking-wide"
                                    >
                                        Remove
                                    </button>
                                </span>
                            ))
                        )}
                    </div>

                    <div className="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
                        <div className="sm:flex-1">
                            <Label htmlFor="tagDraft">Add a new tag</Label>
                            <Input
                                id="tagDraft"
                                value={tagDraft}
                                onChange={(event) => setTagDraft(event.target.value)}
                                placeholder="e.g., Shadow Court"
                                className="mt-1 border-zinc-700 bg-zinc-900/60 text-zinc-100"
                            />
                        </div>
                        <Button
                            type="button"
                            onClick={addTagDraft}
                            className="sm:w-auto bg-emerald-500/20 text-emerald-200 hover:bg-emerald-500/30"
                        >
                            Add tag
                        </Button>
                    </div>

                    {available_tags.length > 0 && (
                        <div className="mt-4 space-y-2">
                            <p className="text-xs uppercase tracking-wide text-zinc-500">Popular tags</p>
                            <div className="flex flex-wrap gap-2">
                                {available_tags.map((tag) => (
                                    <button
                                        type="button"
                                        key={tag.slug}
                                        onClick={() => toggleTag(tag.label)}
                                        className={`rounded-full px-3 py-1 text-xs font-semibold transition ${
                                            data.tags.includes(tag.label)
                                                ? 'bg-emerald-500/30 text-emerald-100'
                                                : 'bg-zinc-800/80 text-zinc-300 hover:bg-zinc-700'
                                        }`}
                                    >
                                        {tag.label}
                                    </button>
                                ))}
                            </div>
                        </div>
                    )}

                    <InputError message={errors.tags} className="mt-2" />
                </section>

                    <div className="flex items-center justify-end gap-3">
                        <Button asChild variant="outline" className="border-zinc-700 text-sm text-zinc-300">
                            <Link href={route('campaigns.entities.show', [campaign.id, entity.id])}>Cancel</Link>
                        </Button>
                        <Button type="submit" disabled={processing} className="bg-amber-500/30 text-amber-100">
                            Update lore entry
                        </Button>
                    </div>
                </form>
                </div>

                <aside className="space-y-4">
                    <AiIdeaPanel
                        endpoint={route('campaigns.ai.lore', campaign.id)}
                        title="Consult the lore steward"
                        description="Describe new plot beats, relationships, or mysteries. The steward proposes refreshed names, aliases, tags, and summaries to weave into this entry."
                        submitLabel="Suggest revisions"
                        applyLabel="Apply suggestion"
                        onApply={applyLoreFields}
                    />

                    <section className="rounded-xl border border-amber-500/30 bg-amber-900/20 p-4 shadow-inner shadow-amber-900/30">
                        <h3 className="text-sm font-semibold text-amber-100">Quick lore seeds</h3>
                        <p className="mt-1 text-xs text-amber-200/80">Swap in a preset concept to overhaul this entry in one click.</p>
                        <ul className="mt-3 space-y-3">
                            {lorePresets.map((preset) => (
                                <li key={preset.id} className="rounded-lg border border-amber-500/20 bg-amber-950/20 p-3">
                                    <p className="text-sm font-semibold text-amber-100">{preset.title}</p>
                                    <p className="mt-1 text-xs text-amber-200/80">{preset.summary}</p>
                                    <Button
                                        type="button"
                                        size="sm"
                                        className="mt-3 bg-amber-500/30 text-amber-100 hover:bg-amber-500/40"
                                        onClick={() => applyLoreFields(preset.fields)}
                                    >
                                        Use this seed
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
