import { useEffect, useMemo, useRef } from 'react';

import { Head, Link, useForm } from '@inertiajs/react';

import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AiIdeaPanel, { AiIdeaResult } from '@/components/ai/AiIdeaPanel';

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

    const canvasRef = useRef<HTMLCanvasElement | null>(null);

    const orientationOptions = useMemo(() => {
        if (form.data.base_layer === 'hex') {
            return [
                { value: 'pointy', label: 'Pointy-top hex' },
                { value: 'flat', label: 'Flat-top hex' },
            ];
        }

        if (form.data.base_layer === 'square') {
            return [
                { value: 'orthogonal', label: 'Orthogonal grid' },
                { value: 'isometric', label: 'Isometric grid' },
            ];
        }

        return [{ value: 'freeform', label: 'Freeform canvas' }];
    }, [form.data.base_layer]);

    useEffect(() => {
        if (!orientationOptions.some((option) => option.value === form.data.orientation)) {
            form.setData('orientation', orientationOptions[0]?.value ?? 'freeform');
        }
    }, [form, form.data.orientation, orientationOptions]);

    useEffect(() => {
        const canvas = canvasRef.current;
        if (!canvas) {
            return;
        }

        const context = canvas.getContext('2d');
        if (!context) {
            return;
        }

        const width = canvas.width;
        const height = canvas.height;
        context.clearRect(0, 0, width, height);
        context.fillStyle = '#09090b';
        context.fillRect(0, 0, width, height);
        context.strokeStyle = 'rgba(148, 163, 184, 0.35)';
        context.lineWidth = 1;

        if (form.data.base_layer === 'hex') {
            const size = 20;
            const hexHeight = size * Math.sqrt(3);
            for (let row = 0; row < 6; row += 1) {
                for (let col = 0; col < 6; col += 1) {
                    const xOffset = form.data.orientation === 'flat' ? size * 1.5 : size;
                    const yOffset = form.data.orientation === 'flat' ? hexHeight / 2 : hexHeight * 0.75;
                    const x = size + col * xOffset + (row % 2 === 0 && form.data.orientation === 'pointy' ? 0 : size * 0.75);
                    const y = hexHeight + row * yOffset;
                    drawHex(context, x, y, size, form.data.orientation === 'flat');
                }
            }
        } else if (form.data.base_layer === 'square') {
            const size = 30;
            for (let row = 0; row < 6; row += 1) {
                for (let col = 0; col < 6; col += 1) {
                    const x = 20 + col * size + (form.data.orientation === 'isometric' ? row * (size / 2) : 0);
                    const y = 20 + row * size;
                    if (form.data.orientation === 'isometric') {
                        drawDiamond(context, x, y, size);
                    } else {
                        context.strokeRect(x, y, size, size);
                    }
                }
            }
        } else {
            context.strokeStyle = 'rgba(250, 204, 21, 0.4)';
            context.strokeRect(20, 20, width - 40, height - 40);
            context.font = '12px Inter, sans-serif';
            context.fillStyle = 'rgba(250, 204, 21, 0.7)';
            context.fillText('Drop image overlays or fog reveals after creating the map.', 26, height / 2);
        }
    }, [form.data.base_layer, form.data.orientation]);

    const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        form.post(route('groups.maps.store', group.id));
    };

    const applyAiPlan = (result: AiIdeaResult) => {
        const structuredFields = (result.structured?.fields ?? {}) as Record<string, unknown>;

        if (typeof structuredFields.width === 'number') {
            form.setData('width', structuredFields.width);
        }

        if (typeof structuredFields.height === 'number') {
            form.setData('height', structuredFields.height);
        }

        if (typeof structuredFields.orientation === 'string') {
            form.setData('orientation', structuredFields.orientation);
        }

        const fog = structuredFields.fog_data;
        if (typeof fog === 'string') {
            form.setData('fog_data', fog);
        }
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

                <div className="grid gap-8 lg:grid-cols-[1.8fr_1fr]">
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
                                    {orientationOptions.map((option) => (
                                        <option key={option.value} value={option.value}>
                                            {option.label}
                                        </option>
                                    ))}
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
                            <p className="text-xs text-zinc-500">Tip: include keys like <code className="rounded bg-zinc-800 px-1 py-0.5">mode</code>, <code className="rounded bg-zinc-800 px-1 py-0.5">opacity</code>, and <code className="rounded bg-zinc-800 px-1 py-0.5">revealed</code> arrays to drive automation or AI planning.</p>
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

                    <div className="space-y-6">
                        <div>
                            <h2 className="text-sm font-semibold uppercase tracking-wide text-zinc-300">Grid preview</h2>
                            <canvas ref={canvasRef} width={260} height={220} className="mt-3 w-full rounded-lg border border-zinc-800 bg-zinc-950" />
                        </div>

                        <AiIdeaPanel
                            key={`${form.data.base_layer}:${form.data.orientation}`}
                            domain="region_map"
                            endpoint={route('groups.maps.ai.seed', group.id)}
                            title="AI cartographer"
                            description="Feed a few themes and let the steward draft map dimensions, fog defaults, and a 512×512 art prompt."
                            placeholder="Three words about the encounter vibe..."
                            context={{
                                title: form.data.title,
                                base_layer: form.data.base_layer,
                                orientation: form.data.orientation,
                                width: form.data.width,
                                height: form.data.height,
                                region_id: form.data.region_id,
                                fog_data: form.data.fog_data,
                            }}
                            actions={[
                                {
                                    label: 'Apply grid plan',
                                    onApply: applyAiPlan,
                                },
                                {
                                    label: 'Apply fog plan',
                                    onApply: (result: AiIdeaResult) => {
                                        const fogSettings = result.structured?.fields?.fog_data ?? result.structured?.fog_settings;
                                        if (typeof fogSettings === 'string') {
                                            form.setData('fog_data', fogSettings);
                                        }
                                    },
                                },
                            ]}
                        />
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}

function drawHex(context: CanvasRenderingContext2D, x: number, y: number, size: number, flatTop: boolean) {
    const angle = Math.PI / 3;
    context.beginPath();
    for (let i = 0; i < 6; i += 1) {
        const theta = angle * i + (flatTop ? Math.PI / 6 : 0);
        const px = x + size * Math.cos(theta);
        const py = y + size * Math.sin(theta);
        if (i === 0) {
            context.moveTo(px, py);
        } else {
            context.lineTo(px, py);
        }
    }
    context.closePath();
    context.stroke();
}

function drawDiamond(context: CanvasRenderingContext2D, x: number, y: number, size: number) {
    context.beginPath();
    context.moveTo(x, y + size / 2);
    context.lineTo(x + size / 2, y);
    context.lineTo(x + size, y + size / 2);
    context.lineTo(x + size / 2, y + size);
    context.closePath();
    context.stroke();
}
