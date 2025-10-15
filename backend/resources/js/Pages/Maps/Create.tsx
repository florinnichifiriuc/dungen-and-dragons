import { Head, Link, useForm } from '@inertiajs/react';

import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

type MapCreateProps = {
    group: { id: number; name: string };
    regions: { id: number; name: string }[];
    defaults: {
        base_layer: string;
        orientation: string;
    };
};

type MapForm = {
    title: string;
    base_layer: string;
    orientation: string;
    width: number | '';
    height: number | '';
    region_id: number | '';
    gm_only: boolean;
    fog_data: string;
};

export default function MapCreate({ group, regions, defaults }: MapCreateProps) {
    const form = useForm<MapForm>({
        title: '',
        base_layer: defaults.base_layer,
        orientation: defaults.orientation,
        width: '',
        height: '',
        region_id: '',
        gm_only: false,
        fog_data: '',
    });

    const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        form.post(route('groups.maps.store', group.id));
    };

    return (
        <AppLayout>
            <Head title={`Create map · ${group.name}`} />

            <div className="mx-auto max-w-3xl">
                <div className="mb-6 flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold text-zinc-100">Create map</h1>
                        <p className="text-sm text-zinc-400">Launch a board for one of {group.name}&apos;s regions.</p>
                    </div>
                    <Button asChild variant="outline" className="border-zinc-700 text-sm">
                        <Link href={route('groups.show', group.id)}>Back to group</Link>
                    </Button>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    <div className="space-y-2">
                        <Label htmlFor="title">Title</Label>
                        <Input
                            id="title"
                            value={form.data.title}
                            onChange={(event) => form.setData('title', event.target.value)}
                            placeholder="Shimmering Expanse"
                        />
                        {form.errors.title && <p className="text-sm text-rose-400">{form.errors.title}</p>}
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="base_layer">Base layer</Label>
                            <select
                                id="base_layer"
                                value={form.data.base_layer}
                                onChange={(event) => form.setData('base_layer', event.target.value)}
                                className="w-full rounded-md border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-zinc-100 focus:border-indigo-500 focus:outline-none focus:ring-0"
                            >
                                <option value="hex">Hex grid</option>
                                <option value="square">Square grid</option>
                                <option value="image">Image backdrop</option>
                            </select>
                            {form.errors.base_layer && <p className="text-sm text-rose-400">{form.errors.base_layer}</p>}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="orientation">Orientation</Label>
                            <select
                                id="orientation"
                                value={form.data.orientation}
                                onChange={(event) => form.setData('orientation', event.target.value)}
                                className="w-full rounded-md border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-zinc-100 focus:border-indigo-500 focus:outline-none focus:ring-0"
                            >
                                <option value="pointy">Pointy-top hex</option>
                                <option value="flat">Flat-top hex</option>
                            </select>
                            {form.errors.orientation && <p className="text-sm text-rose-400">{form.errors.orientation}</p>}
                        </div>
                    </div>

                    <div className="grid gap-4 sm:grid-cols-3">
                        <div className="space-y-2">
                            <Label htmlFor="width">Width (optional)</Label>
                            <Input
                                id="width"
                                type="number"
                                min={1}
                                max={200}
                                value={form.data.width}
                                onChange={(event) => form.setData('width', event.target.value === '' ? '' : Number(event.target.value))}
                            />
                            {form.errors.width && <p className="text-sm text-rose-400">{form.errors.width}</p>}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="height">Height (optional)</Label>
                            <Input
                                id="height"
                                type="number"
                                min={1}
                                max={200}
                                value={form.data.height}
                                onChange={(event) => form.setData('height', event.target.value === '' ? '' : Number(event.target.value))}
                            />
                            {form.errors.height && <p className="text-sm text-rose-400">{form.errors.height}</p>}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="region_id">Region (optional)</Label>
                            <select
                                id="region_id"
                                value={form.data.region_id}
                                onChange={(event) =>
                                    form.setData('region_id', event.target.value === '' ? '' : Number(event.target.value))
                                }
                                className="w-full rounded-md border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-zinc-100 focus:border-indigo-500 focus:outline-none focus:ring-0"
                            >
                                <option value="">Unassigned</option>
                                {regions.map((region) => (
                                    <option key={region.id} value={region.id}>
                                        {region.name}
                                    </option>
                                ))}
                            </select>
                            {form.errors.region_id && <p className="text-sm text-rose-400">{form.errors.region_id}</p>}
                        </div>
                    </div>

                    <div className="flex items-center gap-2">
                        <Checkbox
                            id="gm_only"
                            checked={form.data.gm_only}
                            onChange={(event) => form.setData('gm_only', event.target.checked)}
                        />
                        <Label htmlFor="gm_only" className="text-sm text-zinc-300">
                            GM-only map (players need invites)
                        </Label>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="fog_data">Fog configuration JSON (optional)</Label>
                        <Textarea
                            id="fog_data"
                            value={form.data.fog_data}
                            onChange={(event) => form.setData('fog_data', event.target.value)}
                            placeholder='{"revealed": [[0,0]]}'
                        />
                        {form.errors.fog_data && <p className="text-sm text-rose-400">{form.errors.fog_data}</p>}
                    </div>

                    <div className="flex items-center gap-3">
                        <Button type="submit" disabled={form.processing}>
                            Save map
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
