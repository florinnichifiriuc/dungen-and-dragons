import { ChangeEvent, useMemo, useState } from 'react';

import { Head, Link, useForm } from '@inertiajs/react';

import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { InputError } from '@/components/InputError';
import AiIdeaPanel, { AiIdeaResult } from '@/components/ai/AiIdeaPanel';

type TileTemplateCreateProps = {
    group: { id: number; name: string };
    worlds: { id: number; name: string }[];
};

type TileTemplateForm = {
    name: string;
    key: string;
    terrain_type: string;
    movement_cost: number | string;
    defense_bonus: number | string;
    world_id: number | '';
    image_path: string;
    edge_profile: string;
    image_upload: File | null;
};

export default function TileTemplateCreate({ group, worlds }: TileTemplateCreateProps) {
    const form = useForm<TileTemplateForm>({
        name: '',
        key: '',
        terrain_type: '',
        movement_cost: 1,
        defense_bonus: 0,
        world_id: '',
        image_path: '',
        edge_profile: '',
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
                            <InputError message={form.errors.name} />
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
                                <InputError message={form.errors.key} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="terrain_type">Terrain type</Label>
                                <Input
                                    id="terrain_type"
                                    value={form.data.terrain_type}
                                    onChange={(event) => form.setData('terrain_type', event.target.value)}
                                    placeholder="grassland"
                                />
                                <InputError message={form.errors.terrain_type} />
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
                                <InputError message={form.errors.movement_cost} />
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
                                <InputError message={form.errors.defense_bonus} />
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
                                <InputError message={form.errors.world_id} />
                            </div>
                        </div>

                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="image_upload">Upload tile art (optional)</Label>
                                <Input id="image_upload" type="file" accept="image/png,image/jpeg" onChange={handleFileChange} />
                                <InputError message={form.errors.image_upload as string} />
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
                                <InputError message={form.errors.image_path} />
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
                            <InputError message={form.errors.edge_profile} />
                            <p className="text-xs text-zinc-500">Describe how this tile links to neighbors: road, river, wall, portal…</p>
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

                <AiIdeaPanel
                    domain="tile_template"
                    endpoint={route('groups.ai.tile-templates', group.id)}
                        title="AI tile architect"
                        description="Seed terrain ideas, connection hints, and art prompts without leaving the editor."
                        context={{
                            group_name: group.name,
                            world_name: selectedWorldName,
                            terrain_type: form.data.terrain_type,
                            movement_cost: form.data.movement_cost,
                            defense_bonus: form.data.defense_bonus,
                            description: form.data.edge_profile,
                        }}
                        actions={[
                            {
                                label: 'Apply terrain stats',
                                onApply: (result: AiIdeaResult) => {
                                    const { structured } = result;
                                    if (structured) {
                                        if (typeof structured.terrain_type === 'string') {
                                            form.setData('terrain_type', structured.terrain_type);
                                        }
                                        if (typeof structured.movement_cost === 'number') {
                                            form.setData('movement_cost', structured.movement_cost);
                                        }
                                        if (typeof structured.defense_bonus === 'number') {
                                            form.setData('defense_bonus', structured.defense_bonus);
                                        }
                                        if (structured.edge_profile) {
                                            form.setData('edge_profile', JSON.stringify(structured.edge_profile, null, 2));
                                        }
                                        if (typeof structured.description === 'string' && !form.data.image_path) {
                                            form.setData('key', structured.description.slice(0, 32).toLowerCase().replace(/[^a-z0-9-]+/g, '-'));
                                        }
                                    }
                                },
                            },
                        ]}
                    />
                </div>
            </div>
        </AppLayout>
    );
}
