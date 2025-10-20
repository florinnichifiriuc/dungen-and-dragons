import { ChangeEvent, useMemo, useState } from 'react';

import { Head, Link, useForm } from '@inertiajs/react';

import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { InputError } from '@/components/InputError';
import AiIdeaPanel, { AiIdeaResult } from '@/components/ai/AiIdeaPanel';

type TileTemplateEditProps = {
    group: { id: number; name: string };
    worlds: { id: number; name: string }[];
    template: {
        id: number;
        name: string;
        key: string | null;
        terrain_type: string;
        movement_cost: number;
        defense_bonus: number;
        image_path: string | null;
        edge_profile: Record<string, unknown> | null;
        world_id: number | null;
    };
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

export default function TileTemplateEdit({ group, worlds, template }: TileTemplateEditProps) {
    const form = useForm<TileTemplateForm>({
        name: template.name,
        key: template.key ?? '',
        terrain_type: template.terrain_type,
        movement_cost: template.movement_cost,
        defense_bonus: template.defense_bonus,
        world_id: template.world_id ?? '',
        image_path: template.image_path ?? '',
        edge_profile: template.edge_profile ? JSON.stringify(template.edge_profile, null, 2) : '',
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

    const existingImage = useMemo(() => {
        if (preview) {
            return preview;
        }

        if (form.data.image_path) {
            return form.data.image_path.startsWith('http')
                ? form.data.image_path
                : `/storage/${form.data.image_path}`;
        }

        return null;
    }, [form.data.image_path, preview]);

    const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        form.put(route('groups.tile-templates.update', [group.id, template.id]), {
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
            <Head title={`Edit ${template.name} · ${group.name}`} />

            <div className="mx-auto max-w-5xl">
                <div className="mb-6 flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold text-zinc-100">Edit tile template</h1>
                        <p className="text-sm text-zinc-400">Adjust stats, world scope, or map art for this reusable tile.</p>
                    </div>
                    <Button asChild variant="outline" className="border-zinc-700 text-sm">
                        <Link href={route('groups.show', group.id)}>Back to group</Link>
                    </Button>
                </div>

                <div className="grid gap-8 lg:grid-cols-[1.8fr_1fr]">
                    <form onSubmit={handleSubmit} className="space-y-6">
                        <div className="space-y-2">
                            <Label htmlFor="name">Name</Label>
                            <Input id="name" value={form.data.name} onChange={(event) => form.setData('name', event.target.value)} />
                            <InputError message={form.errors.name} />
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="key">Key (optional)</Label>
                                <Input
                                    id="key"
                                    value={form.data.key}
                                    onChange={(event) => form.setData('key', event.target.value)}
                                />
                                <InputError message={form.errors.key} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="terrain_type">Terrain type</Label>
                                <Input
                                    id="terrain_type"
                                    value={form.data.terrain_type}
                                    onChange={(event) => form.setData('terrain_type', event.target.value)}
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
                                <p className="text-xs text-zinc-500">Drop in a new PNG/JPG up to 5MB to replace the current art.</p>
                                {existingImage && (
                                    <div className="mt-3 overflow-hidden rounded-lg border border-zinc-700/60 bg-zinc-900/60 p-2">
                                        <img src={existingImage} alt="Tile preview" className="mx-auto h-40 w-40 object-cover" />
                                    </div>
                                )}
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="image_path">Existing asset path (optional)</Label>
                                <Input
                                    id="image_path"
                                    value={form.data.image_path}
                                    onChange={(event) => form.setData('image_path', event.target.value)}
                                />
                                <InputError message={form.errors.image_path} />
                                <p className="text-xs text-zinc-500">Leave blank if you plan to use the uploaded file above.</p>
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="edge_profile">Edge profile JSON (optional)</Label>
                            <Textarea
                                id="edge_profile"
                                value={form.data.edge_profile}
                                onChange={(event) => form.setData('edge_profile', event.target.value)}
                            />
                            <InputError message={form.errors.edge_profile} />
                        </div>

                        <div className="flex items-center gap-3">
                            <Button type="submit" disabled={form.processing}>
                                Update template
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
                                Reset changes
                            </Button>
                        </div>
                    </form>

                <AiIdeaPanel
                    domain="tile_template"
                    endpoint={route('groups.ai.tile-templates', group.id)}
                        title="Revise with AI"
                        description="Let the assistant tune stats and edges for this tile while you focus on the map."
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
                                label: 'Apply suggestions',
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
