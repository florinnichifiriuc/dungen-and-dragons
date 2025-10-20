import { FormEvent, useState } from 'react';

import { Head, Link, useForm } from '@inertiajs/react';

import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { InputError } from '@/components/InputError';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AiIdeaPanel, { AiIdeaResult } from '@/components/ai/AiIdeaPanel';

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

type CampaignEntityCreateProps = {
    campaign: CampaignSummary;
    available_types: string[];
    visibility_options: string[];
    available_tags: TagOption[];
    group: GroupSummary;
    members: MemberSummary[];
};

type StatEntry = {
    label: string;
    value: string;
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

const buildInitialStats = (): StatEntry[] => [{ label: '', value: '' }];

export default function CampaignEntityCreate({
    campaign,
    available_types,
    visibility_options,
    available_tags,
    group,
    members,
}: CampaignEntityCreateProps) {
    const [tagDraft, setTagDraft] = useState('');

    const { data, setData, post, processing, errors } = useForm({
        entity_type: available_types[0] ?? 'character',
        name: '',
        alias: '',
        pronunciation: '',
        visibility: visibility_options.includes('players')
            ? 'players'
            : visibility_options[0] ?? 'gm',
        group_id: group?.id ? String(group.id) : '',
        owner_id: '',
        ai_controlled: false,
        initiative_default: '',
        description: '',
        stats: buildInitialStats(),
        tags: [] as string[],
    });

    const submit: FormEvent<HTMLFormElement> = (event) => {
        event.preventDefault();

        post(route('campaigns.entities.store', campaign.id));
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
        setData('stats', next.length > 0 ? next : buildInitialStats());
    };

    return (
        <AppLayout>
            <Head title={`Add lore entry · ${campaign.title}`} />

            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-3xl font-semibold text-zinc-100">Add lore entry</h1>
                    <p className="mt-1 text-sm text-zinc-400">
                        Capture the legends, allies, and curiosities adventurers may encounter.
                    </p>
                </div>
                <Button asChild variant="outline" className="border-zinc-700 text-sm text-zinc-300">
                    <Link href={route('campaigns.entities.index', campaign.id)}>Back to codex</Link>
                </Button>
            </div>

            <div className="mt-6 grid gap-8 xl:grid-cols-[2fr_1fr]">
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
                                    className="mt-1 w-full rounded-md border border-zinc-700 bg-zinc-900/60 px-3 py-2 text-sm text-zinc-100 focus:border-indigo-500 focus:outline-none"
                                >
                                    {available_types.map((value) => (
                                        <option key={value} value={value}>
                                            {typeLabels[value] ?? value}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.entity_type} className="mt-2" />
                            </div>
                        </div>
                    </section>

                    <section className="rounded-xl border border-zinc-800 bg-zinc-950/60 p-6 shadow-inner shadow-black/30">
                        <h2 className="text-lg font-semibold text-zinc-100">Visibility & assignment</h2>
                        <p className="text-sm text-zinc-500">Determine who can see and maintain this entry.</p>

                        <div className="mt-4 grid gap-4 md:grid-cols-2">
                            <div>
                                <Label htmlFor="visibility">Visibility</Label>
                                <select
                                    id="visibility"
                                    value={data.visibility}
                                    onChange={(event) => setData('visibility', event.target.value)}
                                    className="mt-1 w-full rounded-md border border-zinc-700 bg-zinc-900/60 px-3 py-2 text-sm text-zinc-100 focus:border-indigo-500 focus:outline-none"
                                >
                                    {visibility_options.map((value) => (
                                        <option key={value} value={value}>
                                            {visibilityLabels[value] ?? value}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.visibility} className="mt-2" />
                            </div>

                            <div>
                                <Label htmlFor="group_id">Group (optional)</Label>
                                <select
                                    id="group_id"
                                    value={data.group_id}
                                    onChange={(event) => setData('group_id', event.target.value)}
                                    className="mt-1 w-full rounded-md border border-zinc-700 bg-zinc-900/60 px-3 py-2 text-sm text-zinc-100 focus:border-indigo-500 focus:outline-none"
                                >
                                    <option value="">Unassigned</option>
                                    <option value={String(group.id)}>{group.name}</option>
                                </select>
                                <InputError message={errors.group_id} className="mt-2" />
                            </div>

                            <div>
                                <Label htmlFor="owner_id">Owner (optional)</Label>
                                <select
                                    id="owner_id"
                                    value={data.owner_id}
                                    onChange={(event) => setData('owner_id', event.target.value)}
                                    className="mt-1 w-full rounded-md border border-zinc-700 bg-zinc-900/60 px-3 py-2 text-sm text-zinc-100 focus:border-indigo-500 focus:outline-none"
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
                        </div>

                        <div className="mt-4 flex items-center gap-3">
                            <Checkbox
                                id="ai_controlled"
                                checked={data.ai_controlled}
                                onCheckedChange={(checked) => setData('ai_controlled', checked === true)}
                            />
                            <Label htmlFor="ai_controlled" className="text-sm text-zinc-300">
                                Allow AI to improvise updates
                            </Label>
                        </div>
                    </section>

                    <section className="rounded-xl border border-zinc-800 bg-zinc-950/60 p-6 shadow-inner shadow-black/30">
                        <h2 className="text-lg font-semibold text-zinc-100">Lore & stats</h2>
                        <p className="text-sm text-zinc-500">
                            Describe the entity and track quick-reference stats or aspects.
                        </p>

                        <div className="mt-4 space-y-4">
                            <div>
                                <Label htmlFor="description">Description</Label>
                                <Textarea
                                    id="description"
                                    value={data.description}
                                    onChange={(event) => setData('description', event.target.value)}
                                    className="mt-1 h-32 border-zinc-700 bg-zinc-900/60 text-sm text-zinc-100"
                                />
                                <InputError message={errors.description} className="mt-2" />
                            </div>

                            <div className="space-y-3">
                                <div className="flex items-center justify-between">
                                    <Label>Stats</Label>
                                    <Button type="button" variant="outline" size="sm" onClick={addStatRow}>
                                        Add stat
                                    </Button>
                                </div>

                                <div className="space-y-2">
                                    {data.stats.map((stat, index) => (
                                        <div key={`stat-${index}`} className="grid gap-2 sm:grid-cols-[1fr_1fr_auto]">
                                            <Input
                                                value={stat.label}
                                                onChange={(event) => updateStat(index, 'label', event.target.value)}
                                                placeholder="Attribute"
                                                className="border-zinc-700 bg-zinc-900/60"
                                            />
                                            <Input
                                                value={stat.value}
                                                onChange={(event) => updateStat(index, 'value', event.target.value)}
                                                placeholder="Value"
                                                className="border-zinc-700 bg-zinc-900/60"
                                            />
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                onClick={() => removeStatRow(index)}
                                                className="justify-self-end text-sm text-rose-300 hover:text-rose-200"
                                            >
                                                Remove
                                            </Button>
                                        </div>
                                    ))}
                                </div>
                                <InputError message={errors.stats} className="mt-2" />
                            </div>

                            <div>
                                <Label className="block">Tags</Label>
                                <div className="mt-2 flex flex-wrap gap-2">
                                    {data.tags.map((tag) => (
                                        <span
                                            key={tag}
                                            className="inline-flex items-center gap-1 rounded-full border border-zinc-700 bg-zinc-900/60 px-3 py-1 text-xs text-zinc-200"
                                        >
                                            {tag}
                                            <button
                                                type="button"
                                                className="text-rose-300 hover:text-rose-200"
                                                onClick={() => toggleTag(tag)}
                                            >
                                                ×
                                            </button>
                                        </span>
                                    ))}
                                </div>
                                <div className="mt-3 flex gap-2">
                                    <Input
                                        value={tagDraft}
                                        onChange={(event) => setTagDraft(event.target.value)}
                                        placeholder="Add custom tag"
                                        className="border-zinc-700 bg-zinc-900/60"
                                    />
                                    <Button type="button" variant="outline" onClick={addTagDraft}>
                                        Add tag
                                    </Button>
                                </div>

                                <div className="mt-4 flex flex-wrap gap-2">
                                    {available_tags.map((tag) => (
                                        <button
                                            key={tag.id}
                                            type="button"
                                            onClick={() => toggleTag(tag.label)}
                                            className={`rounded-full border px-3 py-1 text-xs ${
                                                data.tags.includes(tag.label)
                                                    ? 'border-indigo-500 bg-indigo-500/10 text-indigo-200'
                                                    : 'border-zinc-700 bg-zinc-900/60 text-zinc-300 hover:border-indigo-500 hover:text-indigo-200'
                                            }`}
                                        >
                                            {tag.label}
                                        </button>
                                    ))}
                                </div>
                                <InputError message={errors.tags} className="mt-2" />
                            </div>
                        </div>
                    </section>

                    <section className="flex items-center justify-between">
                        <Button type="submit" disabled={processing}>
                            Save lore entry
                        </Button>
                        <Button asChild variant="ghost">
                            <Link href={route('campaigns.entities.index', campaign.id)}>Cancel</Link>
                        </Button>
                    </section>
                </form>

                <AiIdeaPanel
                    domain="lore"
                    endpoint={route('campaigns.ai.lore', campaign.id)}
                    title="Summon lore sparks"
                    description="Turn a name or tag into vivid codex copy, spoiler-safe secrets, and art prompts."
                    placeholder="Forgotten guardian of the Bramble Pass"
                    context={{
                        campaign: campaign.title,
                        name: data.name,
                        type: data.entity_type,
                        tags: data.tags,
                        summary: data.description,
                    }}
                    actions={[
                        {
                            label: 'Use as description',
                            onApply: (result: AiIdeaResult) => {
                                const summary = typeof result.structured?.summary === 'string' ? result.structured.summary : '';
                                const secrets = Array.isArray(result.structured?.secrets)
                                    ? result.structured.secrets.filter((entry): entry is string => typeof entry === 'string')
                                    : [];
                                const combined = [summary, secrets.length ? '' : null, ...secrets.map((secret) => `• ${secret}`)]
                                    .filter((value): value is string => Boolean(value && value !== ''))
                                    .join('\n');

                                setData('description', combined !== '' ? combined : result.text);
                            },
                        },
                        {
                            label: 'Adopt tag suggestions',
                            onApply: (result: AiIdeaResult) => {
                                const tags = Array.isArray(result.structured?.tags)
                                    ? result.structured.tags.filter((tag): tag is string => typeof tag === 'string')
                                    : [];
                                if (tags.length > 0) {
                                    const merged = Array.from(new Set([...data.tags, ...tags]));
                                    setData('tags', merged);
                                }
                            },
                        },
                    ]}
                />
            </div>
        </AppLayout>
    );
}
