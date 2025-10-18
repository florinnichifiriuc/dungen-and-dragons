import { useEffect, useMemo, useRef, useState } from 'react';

import { Head, Link, useForm } from '@inertiajs/react';

import AppLayout from '@/Layouts/AppLayout';
import { AiIdeaPanel } from '@/components/AiIdeaPanel';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

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
    const drawingRef = useRef(false);
    const [canvasSeed, setCanvasSeed] = useState(0);

    const isHex = form.data.base_layer === 'hex';

    useEffect(() => {
        if (!isHex && form.data.orientation !== 'pointy') {
            form.setData('orientation', 'pointy');
        }
    }, [form, isHex]);

    useEffect(() => {
        const canvas = canvasRef.current;
        if (!canvas) {
            return;
        }

        const context = canvas.getContext('2d');
        if (!context) {
            return;
        }

        context.fillStyle = '#09090b';
        context.fillRect(0, 0, canvas.width, canvas.height);
        context.strokeStyle = '#fbbf24';
        context.lineWidth = 2;
        context.lineCap = 'round';

        const getPosition = (event: PointerEvent) => {
            const rect = canvas.getBoundingClientRect();
            return {
                x: event.clientX - rect.left,
                y: event.clientY - rect.top,
            };
        };

        const handlePointerDown = (event: PointerEvent) => {
            drawingRef.current = true;
            const point = getPosition(event);
            context.beginPath();
            context.moveTo(point.x, point.y);
        };

        const handlePointerMove = (event: PointerEvent) => {
            if (!drawingRef.current) {
                return;
            }

            const point = getPosition(event);
            context.lineTo(point.x, point.y);
            context.stroke();
        };

        const handlePointerUp = () => {
            drawingRef.current = false;
            context.closePath();
        };

        canvas.addEventListener('pointerdown', handlePointerDown);
        canvas.addEventListener('pointermove', handlePointerMove);
        window.addEventListener('pointerup', handlePointerUp);

        return () => {
            canvas.removeEventListener('pointerdown', handlePointerDown);
            canvas.removeEventListener('pointermove', handlePointerMove);
            window.removeEventListener('pointerup', handlePointerUp);
        };
    }, [canvasSeed]);

    const clearCanvas = () => {
        const canvas = canvasRef.current;
        const context = canvas?.getContext('2d');
        if (canvas && context) {
            context.clearRect(0, 0, canvas.width, canvas.height);
        }

        setCanvasSeed((seed) => seed + 1);
    };

    const fogPreview = useMemo(() => {
        if (!form.data.fog_data) {
            return null;
        }

        try {
            const parsed = JSON.parse(form.data.fog_data);
            return typeof parsed === 'object' && parsed !== null ? parsed : null;
        } catch (error) {
            return 'invalid';
        }
    }, [form.data.fog_data]);

    const applyAiFields = (fields: Record<string, unknown>) => {
        if (typeof fields.width === 'number') {
            form.setData('width', fields.width);
        }

        if (typeof fields.height === 'number') {
            form.setData('height', fields.height);
        }

        if (typeof fields.orientation === 'string') {
            form.setData('orientation', fields.orientation);
        }

        const fogData = fields.fog_data;
        if (typeof fogData === 'string') {
            form.setData('fog_data', fogData);
        } else if (fogData && typeof fogData === 'object') {
            form.setData('fog_data', JSON.stringify(fogData, null, 2));
        }
    };

    const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        form.put(route('groups.maps.update', [group.id, map.id]));
    };

    return (
        <AppLayout>
            <Head title={`Edit ${map.title} · ${group.name}`} />

            <div className="mx-auto grid max-w-5xl gap-6 lg:grid-cols-[2fr,1fr]">
                <div className="mb-6 flex items-center justify-between lg:col-span-2">
                    <div>
                        <h1 className="text-2xl font-semibold text-zinc-100">Edit map</h1>
                        <p className="text-sm text-zinc-400">Adjust map metadata and visibility settings.</p>
                    </div>
                    <Button asChild variant="outline" className="border-zinc-700 text-sm">
                        <Link href={route('groups.maps.show', [group.id, map.id])}>Back to map</Link>
                    </Button>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6 rounded-xl border border-zinc-800 bg-zinc-950/60 p-6">
                    <div className="space-y-2">
                        <Label htmlFor="title">Title</Label>
                        <Input
                            id="title"
                            value={form.data.title}
                            onChange={(event) => form.setData('title', event.target.value)}
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
                                disabled={!isHex}
                            >
                                <option value="pointy">Pointy-top hex</option>
                                <option value="flat">Flat-top hex</option>
                            </select>
                            <p className="text-xs text-zinc-500">
                                {isHex
                                    ? 'Choose the axial orientation for your hex grid.'
                                    : 'Orientation only applies to hex grids. Switch base layer to hex to adjust it.'}
                            </p>
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
                        />
                        {fogPreview === 'invalid' ? (
                            <p className="text-xs text-rose-400">Fog configuration must be valid JSON.</p>
                        ) : fogPreview ? (
                            <p className="text-xs text-zinc-500">{`Preview: ${Object.keys(fogPreview).length} keys detected.`}</p>
                        ) : (
                            <p className="text-xs text-zinc-500">Leave blank to disable fog or paste a JSON snippet from the mentor.</p>
                        )}
                        {form.errors.fog_data && <p className="text-sm text-rose-400">{form.errors.fog_data}</p>}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="sketch">Quick region sketch (not saved)</Label>
                        <canvas
                            key={canvasSeed}
                            ref={canvasRef}
                            id="sketch"
                            width={480}
                            height={300}
                            className="w-full rounded-lg border border-zinc-800 bg-zinc-950"
                        />
                        <div className="flex justify-end">
                            <Button type="button" size="sm" variant="outline" className="border-zinc-700 text-xs" onClick={clearCanvas}>
                                Clear sketch
                            </Button>
                        </div>
                        <p className="text-xs text-zinc-500">Use this canvas to rough in ideas before committing tiles to the map.</p>
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

                <AiIdeaPanel
                    endpoint={route('groups.maps.ai.plan', [group.id, map.id])}
                    title="Consult the map mentor"
                    description="Share the encounter goals or vibe and the AI will suggest dimensions, fog settings, and landmarks you can pull into this map."
                    submitLabel="Generate map plan"
                    applyLabel="Apply plan"
                    onApply={applyAiFields}
                />
            </div>
        </AppLayout>
    );
}
