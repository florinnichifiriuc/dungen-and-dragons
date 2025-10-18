import { Head, Link, useForm } from '@inertiajs/react';

import AppLayout from '@/Layouts/AppLayout';
import { AiIdeaPanel } from '@/components/AiIdeaPanel';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

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
    key: string | null;
    terrain_type: string;
    movement_cost: number | string;
    defense_bonus: number | string;
    world_id: number | '';
    image_path: string;
    image_upload: File | null;
    edge_profile: string;
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
        image_upload: null,
        edge_profile: template.edge_profile ? JSON.stringify(template.edge_profile, null, 2) : '',
    });

    const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        form.post(route('groups.tile-templates.update', [group.id, template.id]), {
            forceFormData: true,
            method: 'put',
        });
    };

    const applyAiFields = (fields: Record<string, unknown>) => {
        if (typeof fields.name === 'string') {
            form.setData('name', fields.name);
        }

        if (typeof fields.terrain_type === 'string') {
            form.setData('terrain_type', fields.terrain_type);
        }

        if (typeof fields.movement_cost === 'number') {
            form.setData('movement_cost', fields.movement_cost);
        }

        if (typeof fields.defense_bonus === 'number') {
            form.setData('defense_bonus', fields.defense_bonus);
        }

        if (typeof fields.edge_profile === 'string') {
            form.setData('edge_profile', fields.edge_profile);
        }
    };

    return (
        <AppLayout>
            <Head title={`Edit ${template.name} · ${group.name}`} />

            <div className="mx-auto grid max-w-5xl gap-6 lg:grid-cols-[2fr,1fr]">
                <div className="mb-6 flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold text-zinc-100">Edit tile template</h1>
                        <p className="text-sm text-zinc-400">Tune how this tile behaves across {group.name}&apos;s boards.</p>
                    </div>
                    <Button asChild variant="outline" className="border-zinc-700 text-sm">
                        <Link href={route('groups.show', group.id)}>Back to group</Link>
                    </Button>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6 rounded-xl border border-zinc-800 bg-zinc-950/60 p-6">
                    <div className="space-y-2">
                        <Label htmlFor="name">Name</Label>
                        <Input
                            id="name"
                            value={form.data.name}
                            onChange={(event) => form.setData('name', event.target.value)}
                        />
                        {form.errors.name && <p className="text-sm text-rose-400">{form.errors.name}</p>}
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="key">Key (optional)</Label>
                            <Input
                                id="key"
                                value={form.data.key ?? ''}
                                onChange={(event) => form.setData('key', event.target.value)}
                            />
                            {form.errors.key && <p className="text-sm text-rose-400">{form.errors.key}</p>}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="terrain_type">Terrain type</Label>
                            <Input
                                id="terrain_type"
                                value={form.data.terrain_type}
                                onChange={(event) => form.setData('terrain_type', event.target.value)}
                                list="terrain-options"
                            />
                            {form.errors.terrain_type && <p className="text-sm text-rose-400">{form.errors.terrain_type}</p>}
                        </div>
                    </div>

                    <datalist id="terrain-options">
                        <option value="forest" />
                        <option value="mountain" />
                        <option value="river" />
                        <option value="swamp" />
                        <option value="ruins" />
                        <option value="road" />
                        <option value="village" />
                    </datalist>

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
                            {form.errors.movement_cost && <p className="text-sm text-rose-400">{form.errors.movement_cost}</p>}
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
                            {form.errors.defense_bonus && <p className="text-sm text-rose-400">{form.errors.defense_bonus}</p>}
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
                            {form.errors.world_id && <p className="text-sm text-rose-400">{form.errors.world_id}</p>}
                        </div>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="image_path">Image path (optional)</Label>
                        <Input
                            id="image_path"
                            value={form.data.image_path}
                            onChange={(event) => form.setData('image_path', event.target.value)}
                        />
                        {form.errors.image_path && <p className="text-sm text-rose-400">{form.errors.image_path}</p>}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="image_upload">Replace preview (optional)</Label>
                        <Input
                            id="image_upload"
                            type="file"
                            accept="image/png,image/jpeg,image/webp"
                            onChange={(event) => form.setData('image_upload', event.target.files?.[0] ?? null)}
                        />
                        {form.errors.image_upload && <p className="text-sm text-rose-400">{form.errors.image_upload}</p>}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="edge_profile">Edge profile JSON (optional)</Label>
                        <Textarea
                            id="edge_profile"
                            value={form.data.edge_profile}
                            onChange={(event) => form.setData('edge_profile', event.target.value)}
                            rows={4}
                        />
                        <p className="text-xs text-zinc-500">
                            Update how this tile connects to neighbors. Use JSON keys like <code>north</code>, <code>south</code>, <code>east</code>, and <code>west</code>.
                        </p>
                        {form.errors.edge_profile && <p className="text-sm text-rose-400">{form.errors.edge_profile}</p>}
                    </div>

                    <div className="flex items-center gap-3">
                        <Button type="submit" disabled={form.processing}>
                            Update template
                        </Button>
                        <Button
                            type="button"
                            variant="ghost"
                            disabled={form.processing}
                            onClick={() => form.reset()}
                        >
                            Reset changes
                        </Button>
                    </div>
                </form>

                <AiIdeaPanel
                    endpoint={route('groups.ai.tile-templates', group.id)}
                    title="Refresh with AI guidance"
                    description="Need to reposition this tile for a new region? Ask the mentor for fresh stats, edge notes, or art ideas and apply what fits."
                    submitLabel="Suggest refinements"
                    applyLabel="Apply to template"
                    onApply={applyAiFields}
                />
            </div>
        </AppLayout>
    );
}
