import { ChangeEvent, useMemo, useState } from 'react';

import { Head, Link, useForm } from '@inertiajs/react';

import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { InputError } from '@/components/InputError';
import AiCompanionDrawer from '@/components/ai/AiCompanionDrawer';

type TileTemplateCreateProps = {
    group: { id: number; name: string };
    worlds: { id: number; name: string }[];
};

type TileTemplateForm = {
    name: string;
    key: string;
    terrain_type: string;
    category: string;
    movement_cost: number | string;
    defense_bonus: number | string;
    world_id: number | '';
    image_path: string;
    thumbnail_path: string;
    edge_profile: string;
    terrain_traits: string;
    encounter_tags: string;
    ai_metadata: string;
    image_upload: File | null;
};

export default function TileTemplateCreate({ group, worlds }: TileTemplateCreateProps) {
    const form = useForm<TileTemplateForm>({
        name: '',
        key: '',
        terrain_type: '',
        category: '',
        movement_cost: 1,
        defense_bonus: 0,
        world_id: '',
        image_path: '',
        thumbnail_path: '',
        edge_profile: '',
        terrain_traits: '[]',
        encounter_tags: '[]',
        ai_metadata: '',
        image_upload: null,
    });

    const [preview, setPreview] = useState<string | null>(null);

    const selectedWorldName = useMemo(() => {
        if (form.data.world_id === '') {
            return null;
        }

        const found = worlds.find((world) => world.id === form.data.world_id);
        return found ? found.name : null;
    }, [form.data.world_id, worlds]);

    const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        form.post(route('groups.tile-templates.store', group.id), {
            forceFormData: true,
        });
    };

    const handleFileChange = (event: ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0] ?? null;
        form.setData('image_upload', file);

        if (preview) {
            URL.revokeObjectURL(preview);
        }

        if (file) {
            setPreview(URL.createObjectURL(file));
        } else {
            setPreview(null);
        }
    };

    const handleCompanionApply = (result: { text: string; structured?: Record<string, unknown> | null }) => {
        const structured = result.structured ?? {};
        const fields = (structured.fields ?? {}) as Record<string, unknown>;

        if (typeof fields.name === 'string' && form.data.name.trim() === '') {
            form.setData('name', fields.name);
        }

        if (typeof structured.terrain_type === 'string') {
            form.setData('terrain_type', structured.terrain_type);
        } else if (typeof fields.terrain_type === 'string') {
            form.setData('terrain_type', fields.terrain_type);
        }

        if (typeof structured.movement_cost === 'number') {
            form.setData('movement_cost', structured.movement_cost);
        } else if (typeof fields.movement_cost === 'number') {
            form.setData('movement_cost', fields.movement_cost);
        }

        if (typeof structured.defense_bonus === 'number') {
            form.setData('defense_bonus', structured.defense_bonus);
        } else if (typeof fields.defense_bonus === 'number') {
            form.setData('defense_bonus', fields.defense_bonus);
        }

        if (structured.edge_profile || fields.edge_profile) {
            const profile = structured.edge_profile ?? fields.edge_profile;
            if (typeof profile === 'string') {
                form.setData('edge_profile', profile);
            } else if (profile) {
                form.setData('edge_profile', JSON.stringify(profile, null, 2));
            }
        }

        if (structured.terrain_traits || fields.terrain_traits) {
            const traits = structured.terrain_traits ?? fields.terrain_traits;
            if (typeof traits === 'string') {
                form.setData('terrain_traits', traits);
            } else if (traits) {
                form.setData('terrain_traits', JSON.stringify(traits, null, 2));
            }
        }

        if (structured.encounter_tags || fields.encounter_tags) {
            const tags = structured.encounter_tags ?? fields.encounter_tags;
            if (typeof tags === 'string') {
                form.setData('encounter_tags', tags);
            } else if (tags) {
                form.setData('encounter_tags', JSON.stringify(tags, null, 2));
            }
        }

        if (structured.category || fields.category) {
            const category = structured.category ?? fields.category;
            if (typeof category === 'string') {
                form.setData('category', category);
            }
        }

        if (structured.thumbnail_path || fields.thumbnail_path) {
            const thumbnail = structured.thumbnail_path ?? fields.thumbnail_path;
            if (typeof thumbnail === 'string') {
                form.setData('thumbnail_path', thumbnail);
            }
        }

        if (structured.ai_metadata || fields.ai_metadata) {
            const metadata = structured.ai_metadata ?? fields.ai_metadata;
            if (typeof metadata === 'string') {
                form.setData('ai_metadata', metadata);
            } else if (metadata) {
                form.setData('ai_metadata', JSON.stringify(metadata, null, 2));
            }
        }

        if (typeof structured.description === 'string' && !form.data.key) {
            form.setData('key', structured.description.slice(0, 32).toLowerCase().replace(/[^a-z0-9-]+/g, '-'));
        }

        if (typeof structured.summary === 'string' && !form.data.image_path) {
            form.setData('image_path', structured.summary.toLowerCase().replace(/\s+/g, '-') + '.png');
        }
    };

    return (
        <AppLayout>
            <Head title={`New tile template · ${group.name}`} />

            <div className="mx-auto max-w-5xl">
                <div className="mb-6 flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold text-zinc-100">New tile template</h1>
                        <p className="text-sm text-zinc-400">Define reusable terrain pieces for {group.name}&apos;s maps.</p>
                    </div>
                    <Button asChild variant="outline" className="border-zinc-700 text-sm">
                        <Link href={route('groups.show', group.id)}>Back to group</Link>
                    </Button>
                </div>

                <div className="grid gap-8 lg:grid-cols-[1.8fr_1fr]">
                    <form onSubmit={handleSubmit} className="space-y-6">
                        <div className="space-y-2">
                            <Label htmlFor="name">Name</Label>
                            <Input
                                id="name"
                                value={form.data.name}
                                onChange={(event) => form.setData('name', event.target.value)}
                                placeholder="Luminous grassland"
                            />
                            <InputError message={form.errors?.name} />
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="key">Key (optional)</Label>
                                <Input
                                    id="key"
                                    value={form.data.key}
                                    onChange={(event) => form.setData('key', event.target.value)}
                                    placeholder="grass-basic"
                                />
                                <InputError message={form.errors?.key} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="terrain_type">Terrain type</Label>
                                <Input
                                    id="terrain_type"
                                    value={form.data.terrain_type}
                                    onChange={(event) => form.setData('terrain_type', event.target.value)}
                                    placeholder="grassland"
                                />
                                <InputError message={form.errors?.terrain_type} />
                            </div>
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="category">Category (optional)</Label>
                                <Input
                                    id="category"
                                    value={form.data.category}
                                    onChange={(event) => form.setData('category', event.target.value)}
                                    placeholder="wilderness"
                                />
                                <InputError message={form.errors?.category as string} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="thumbnail_path">Thumbnail path (optional)</Label>
                                <Input
                                    id="thumbnail_path"
                                    value={form.data.thumbnail_path}
                                    onChange={(event) => form.setData('thumbnail_path', event.target.value)}
                                    placeholder="thumbnails/grassland.png"
                                />
                                <InputError message={form.errors?.thumbnail_path as string} />
                                <p className="text-xs text-zinc-500">Use for lightweight previews in selection menus.</p>
                            </div>
                        </div>

                        <div className="grid gap-4 sm:grid-cols-3">
                            <div className="space-y-2">
                                <Label htmlFor="movement_cost">Movement cost</Label>
                                <Input
                                    id="movement_cost"
                                    type="number"
                                    min={0}
                                    max={20}
                                    value={form.data.movement_cost}
                                    onChange={(event) => form.setData('movement_cost', Number(event.target.value))}
                                />
                                <InputError message={form.errors?.movement_cost} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="defense_bonus">Defense bonus</Label>
                                <Input
                                    id="defense_bonus"
                                    type="number"
                                    min={0}
                                    max={20}
                                    value={form.data.defense_bonus}
                                    onChange={(event) => form.setData('defense_bonus', Number(event.target.value))}
                                />
                                <InputError message={form.errors?.defense_bonus} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="world_id">World scope</Label>
                                <select
                                    id="world_id"
                                    value={form.data.world_id}
                                    onChange={(event) =>
                                        form.setData('world_id', event.target.value === '' ? '' : Number(event.target.value))
                                    }
                                    className="w-full rounded-md border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-zinc-100 focus:border-indigo-500 focus:outline-none focus:ring-0"
                                >
                                    <option value="">Shared across worlds</option>
                                    {worlds.map((world) => (
                                        <option key={world.id} value={world.id}>
                                            {world.name}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={form.errors?.world_id} />
                            </div>
                        </div>

                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="image_upload">Upload tile art (optional)</Label>
                                <Input id="image_upload" type="file" accept="image/png,image/jpeg" onChange={handleFileChange} />
                                <InputError message={form.errors?.image_upload as string} />
                                <p className="text-xs text-zinc-500">PNG or JPG up to 5MB. We will store it under the group&apos;s public tiles.</p>
                                {preview && (
                                    <div className="mt-3 overflow-hidden rounded-lg border border-zinc-700/60 bg-zinc-900/60 p-2">
                                        <img src={preview} alt="Tile preview" className="mx-auto h-40 w-40 object-cover" />
                                    </div>
                                )}
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="image_path">Existing asset path (optional)</Label>
                                <Input
                                    id="image_path"
                                    value={form.data.image_path}
                                    onChange={(event) => form.setData('image_path', event.target.value)}
                                    placeholder="tiles/grassland.png"
                                />
                                <InputError message={form.errors?.image_path} />
                                <p className="text-xs text-zinc-500">If you host art elsewhere, drop in a relative or CDN path.</p>
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="edge_profile">Edge profile JSON (optional)</Label>
                            <Textarea
                                id="edge_profile"
                                value={form.data.edge_profile}
                                onChange={(event) => form.setData('edge_profile', event.target.value)}
                                placeholder='{"north":"road","south":"road"}'
                            />
                            <InputError message={form.errors?.edge_profile} />
                            <p className="text-xs text-zinc-500">Describe how this tile links to neighbors: road, river, wall, portal…</p>
                        </div>

                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="terrain_traits">Terrain traits (JSON or comma list)</Label>
                                <Textarea
                                    id="terrain_traits"
                                    value={form.data.terrain_traits}
                                    onChange={(event) => form.setData('terrain_traits', event.target.value)}
                                    placeholder='["difficult","magical"]'
                                />
                                <InputError message={form.errors?.terrain_traits as string} />
                                <p className="text-xs text-zinc-500">Comma lists are converted into JSON arrays when saved.</p>
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="encounter_tags">Encounter tags (JSON or comma list)</Label>
                                <Textarea
                                    id="encounter_tags"
                                    value={form.data.encounter_tags}
                                    onChange={(event) => form.setData('encounter_tags', event.target.value)}
                                    placeholder='["fae","exploration"]'
                                />
                                <InputError message={form.errors?.encounter_tags as string} />
                                <p className="text-xs text-zinc-500">Highlight which encounters shine here—travel, social, combat, puzzles.</p>
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="ai_metadata">AI metadata (optional)</Label>
                            <Textarea
                                id="ai_metadata"
                                value={form.data.ai_metadata}
                                onChange={(event) => form.setData('ai_metadata', event.target.value)}
                                placeholder='{"art_prompt":"glowing forest tile"}'
                            />
                            <InputError message={form.errors?.ai_metadata as string} />
                            <p className="text-xs text-zinc-500">Store optional metadata like art prompts or automation hints.</p>
                        </div>

                        <div className="flex items-center gap-3">
                            <Button type="submit" disabled={form.processing}>
                                Save template
                            </Button>
                            <Button
                                type="button"
                                variant="ghost"
                                disabled={form.processing}
                                onClick={() => {
                                    form.reset();
                                    setPreview(null);
                                }}
                            >
                                Reset
                            </Button>
                        </div>
                    </form>

                    <AiCompanionDrawer
                        domain="tile_template"
                        title="Tile architect companion"
                        description="Design terrain traits, edge profiles, and art prompts with conversational guidance."
                        context={{
                            group_name: group.name,
                            world_name: selectedWorldName,
                            terrain_type: form.data.terrain_type,
                            movement_cost: form.data.movement_cost,
                            defense_bonus: form.data.defense_bonus,
                            description: form.data.edge_profile,
                        }}
                        presets={[
                            { label: 'Hazardous terrain', prompt: 'Draft a dangerous terrain tile with movement penalties and encounter hooks.' },
                            { label: 'Traversal aid', prompt: 'Design a supportive tile that helps players cross difficult areas.' },
                            { label: 'A1111 prompt', prompt: 'Suggest art direction and edge notes for rendering this tile.' },
                        ]}
                        onApply={handleCompanionApply}
                    />
                </div>
            </div>
        </AppLayout>
    );
}
