import { useEffect, useMemo, useRef } from 'react';

import { Head, Link, useForm } from '@inertiajs/react';

import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { InputError } from '@/components/InputError';
import AiIdeaPanel, { AiIdeaResult } from '@/components/ai/AiIdeaPanel';

type MapEditProps = {
    group: { id: number; name: string };
    regions: { id: number; name: string }[];
    map: {
        id: number;
        title: string;
        base_layer: string;
        orientation: string;
        width: number | null;
        height: number | null;
        gm_only: boolean;
        region_id: number | null;
        fog_data: Record<string, unknown> | null;
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

export default function MapEdit({ group, regions, map }: MapEditProps) {
    const form = useForm<MapForm>({
        title: map.title,
        base_layer: map.base_layer,
        orientation: map.orientation,
        width: map.width ?? '',
        height: map.height ?? '',
        region_id: map.region_id ?? '',
        gm_only: map.gm_only,
        fog_data: map.fog_data ? JSON.stringify(map.fog_data, null, 2) : '',
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
            context.fillText('Use freeform overlays for image backdrops.', 30, height / 2);
        }
    }, [form.data.base_layer, form.data.orientation]);

    const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        form.put(route('groups.maps.update', [group.id, map.id]));
    };

    return (
        <AppLayout>
            <Head title={`Edit ${map.title} · ${group.name}`} />

            <div className="mx-auto max-w-3xl">
                <div className="mb-6 flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold text-zinc-100">Edit map</h1>
                        <p className="text-sm text-zinc-400">Adjust map metadata and visibility settings.</p>
                    </div>
                    <Button asChild variant="outline" className="border-zinc-700 text-sm">
                        <Link href={route('groups.maps.show', [group.id, map.id])}>Back to map</Link>
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
                            />
                            <InputError message={form.errors.title} />
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
                                <InputError message={form.errors.orientation} />
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
                                    onChange={(event) =>
                                        form.setData('width', event.target.value === '' ? '' : Number(event.target.value))
                                    }
                                />
                                <InputError message={form.errors.width} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="height">Height (optional)</Label>
                                <Input
                                    id="height"
                                    type="number"
                                    min={1}
                                    max={200}
                                    value={form.data.height}
                                    onChange={(event) =>
                                        form.setData('height', event.target.value === '' ? '' : Number(event.target.value))
                                    }
                                />
                                <InputError message={form.errors.height} />
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
                                <InputError message={form.errors.region_id} />
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
                            />
                            <InputError message={form.errors.fog_data} />
                            <p className="text-xs text-zinc-500">Tip: include keys like mode, opacity, and reveal_radius to guide automations.</p>
                        </div>

                        <div className="flex items-center gap-3">
                            <Button type="submit" disabled={form.processing}>
                                Update map
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

                    <div className="space-y-6">
                        <div>
                            <h2 className="text-sm font-semibold uppercase tracking-wide text-zinc-300">Grid preview</h2>
                            <canvas ref={canvasRef} width={260} height={220} className="mt-3 w-full rounded-lg border border-zinc-800 bg-zinc-950" />
                        </div>

                        <AiIdeaPanel
                            domain="region_map"
                            endpoint={route('groups.maps.ai.plan', [group.id, map.id])}
                            title="Ask the AI cartographer"
                            description="Request layout beats, fog defaults, and an art prompt to jumpstart this region map."
                            context={{
                                title: form.data.title,
                                base_layer: form.data.base_layer,
                                orientation: form.data.orientation,
                                fog_data: form.data.fog_data,
                            }}
                            actions={[
                                {
                                    label: 'Apply fog suggestions',
                                    onApply: (result: AiIdeaResult) => {
                                        const fogSettings = result.structured?.fog_settings;
                                        if (fogSettings) {
                                            form.setData('fog_data', JSON.stringify(fogSettings, null, 2));
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
