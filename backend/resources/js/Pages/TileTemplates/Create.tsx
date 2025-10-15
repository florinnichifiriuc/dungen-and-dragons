import { Head, Link, useForm } from '@inertiajs/react';

import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

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
    });

    const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        form.post(route('groups.tile-templates.store', group.id));
    };

    return (
        <AppLayout>
            <Head title={`New tile template · ${group.name}`} />

            <div className="mx-auto max-w-3xl">
                <div className="mb-6 flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold text-zinc-100">New tile template</h1>
                        <p className="text-sm text-zinc-400">Define reusable terrain pieces for {group.name}&apos;s maps.</p>
                    </div>
                    <Button asChild variant="outline" className="border-zinc-700 text-sm">
                        <Link href={route('groups.show', group.id)}>Back to group</Link>
                    </Button>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    <div className="space-y-2">
                        <Label htmlFor="name">Name</Label>
                        <Input
                            id="name"
                            value={form.data.name}
                            onChange={(event) => form.setData('name', event.target.value)}
                            placeholder="Luminous grassland"
                        />
                        {form.errors.name && <p className="text-sm text-rose-400">{form.errors.name}</p>}
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
                            {form.errors.key && <p className="text-sm text-rose-400">{form.errors.key}</p>}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="terrain_type">Terrain type</Label>
                            <Input
                                id="terrain_type"
                                value={form.data.terrain_type}
                                onChange={(event) => form.setData('terrain_type', event.target.value)}
                                placeholder="grassland"
                            />
                            {form.errors.terrain_type && <p className="text-sm text-rose-400">{form.errors.terrain_type}</p>}
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
                            placeholder="tiles/grassland.png"
                        />
                        {form.errors.image_path && <p className="text-sm text-rose-400">{form.errors.image_path}</p>}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="edge_profile">Edge profile JSON (optional)</Label>
                        <Textarea
                            id="edge_profile"
                            value={form.data.edge_profile}
                            onChange={(event) => form.setData('edge_profile', event.target.value)}
                            placeholder='{"north":"road","south":"road"}'
                        />
                        {form.errors.edge_profile && <p className="text-sm text-rose-400">{form.errors.edge_profile}</p>}
                    </div>

                    <div className="flex items-center gap-3">
                        <Button type="submit" disabled={form.processing}>
                            Save template
                        </Button>
                        <Button
                            type="button"
                            variant="ghost"
                            disabled={form.processing}
                            onClick={() => form.reset()}
                        >
                            Reset
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
