import { useEffect, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';

import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { getEcho } from '@/lib/realtime';

type TileTemplateOption = {
    id: number;
    name: string;
    terrain_type: string;
    movement_cost: number;
    defense_bonus: number;
};

type MapTileSummary = {
    id: number;
    q: number;
    r: number;
    elevation: number;
    locked: boolean;
    variant: Record<string, unknown> | null;
    template: TileTemplateOption;
};

type MapShowProps = {
    group: { id: number; name: string };
    map: {
        id: number;
        title: string;
        base_layer: string;
        orientation: string;
        width: number | null;
        height: number | null;
        gm_only: boolean;
        region: { id: number; name: string } | null;
    };
    tiles: MapTileSummary[];
    tile_templates: TileTemplateOption[];
};

type CreateTileForm = {
    tile_template_id: number | '';
    q: number;
    r: number;
    elevation: number;
    variant: string;
    locked: boolean;
};

type TileDraft = {
    tile_template_id: number;
    elevation: number | '';
    variant: string;
};

type MapTileEventPayload = {
    tile: Partial<MapTileSummary> & { id: number };
};

const orderTiles = (items: MapTileSummary[]): MapTileSummary[] =>
    [...items].sort((a, b) => {
        if (a.q !== b.q) {
            return a.q - b.q;
        }

        if (a.r !== b.r) {
            return a.r - b.r;
        }

        return a.id - b.id;
    });

export default function MapShow({ group, map, tiles, tile_templates }: MapShowProps) {
    const createForm = useForm<CreateTileForm>({
        tile_template_id: tile_templates[0]?.id ?? '',
        q: 0,
        r: 0,
        elevation: 0,
        variant: '',
        locked: false,
    });

    const [liveTiles, setLiveTiles] = useState<MapTileSummary[]>(() => orderTiles(tiles));
    const [tileEdits, setTileEdits] = useState<Record<number, TileDraft>>({});
    const [updatingTile, setUpdatingTile] = useState<number | null>(null);
    const [removingTile, setRemovingTile] = useState<number | null>(null);

    useEffect(() => {
        setLiveTiles(orderTiles(tiles));
    }, [tiles]);

    useEffect(() => {
        if (tile_templates.length > 0 && createForm.data.tile_template_id === '') {
            createForm.setData('tile_template_id', tile_templates[0].id);
        }
    }, [tile_templates]);

    useEffect(() => {
        const drafts: Record<number, TileDraft> = {};
        liveTiles.forEach((tile) => {
            drafts[tile.id] = {
                tile_template_id: tile.template.id,
                elevation: tile.elevation,
                variant: tile.variant ? JSON.stringify(tile.variant, null, 2) : '',
            };
        });
        setTileEdits(drafts);
    }, [liveTiles]);

    useEffect(() => {
        const echo = getEcho();

        if (!echo) {
            return;
        }

        const channelName = `groups.${group.id}.maps.${map.id}`;
        const channel = echo.private(channelName);

        const upsertTile = (incoming: MapTileSummary) => {
            setLiveTiles((current) =>
                orderTiles([
                    ...current.filter((existing) => existing.id !== incoming.id),
                    incoming,
                ]),
            );
        };

        const handleCreatedOrUpdated = (payload: MapTileEventPayload) => {
            if (
                payload.tile.q === undefined ||
                payload.tile.r === undefined ||
                payload.tile.template === undefined
            ) {
                return;
            }

            upsertTile(payload.tile as MapTileSummary);
        };

        const handleDeleted = (payload: MapTileEventPayload) => {
            setLiveTiles((current) => current.filter((tile) => tile.id !== payload.tile.id));
        };

        channel.listen('.map-tile.created', handleCreatedOrUpdated);
        channel.listen('.map-tile.updated', handleCreatedOrUpdated);
        channel.listen('.map-tile.deleted', handleDeleted);

        return () => {
            channel.stopListening('.map-tile.created');
            channel.stopListening('.map-tile.updated');
            channel.stopListening('.map-tile.deleted');
            echo.leave(channelName);
        };
    }, [group.id, map.id]);

    const handleCreate = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        createForm.post(route('groups.maps.tiles.store', [group.id, map.id]), {
            preserveScroll: true,
            onSuccess: () => {
                createForm.setData({
                    tile_template_id: tile_templates[0]?.id ?? '',
                    q: 0,
                    r: 0,
                    elevation: 0,
                    variant: '',
                    locked: false,
                });
            },
        });
    };

    const handleTileDraftChange = (tileId: number, field: keyof TileDraft, value: string | number) => {
        setTileEdits((drafts) => ({
            ...drafts,
            [tileId]: {
                ...drafts[tileId],
                [field]: value,
            },
        }));
    };

    const handleTileUpdate = (tileId: number) => {
        const draft = tileEdits[tileId];
        if (!draft) {
            return;
        }

        setUpdatingTile(tileId);
        router.patch(
            route('groups.maps.tiles.update', [group.id, map.id, tileId]),
            {
                tile_template_id: draft.tile_template_id,
                elevation: draft.elevation === '' ? null : Number(draft.elevation),
                variant: draft.variant.trim() === '' ? null : draft.variant,
            },
            {
                preserveScroll: true,
                onFinish: () => setUpdatingTile(null),
            }
        );
    };

    const handleToggleLock = (tile: MapTileSummary) => {
        setUpdatingTile(tile.id);
        router.patch(
            route('groups.maps.tiles.update', [group.id, map.id, tile.id]),
            {
                locked: !tile.locked,
            },
            {
                preserveScroll: true,
                onFinish: () => setUpdatingTile(null),
            }
        );
    };

    const handleRemoveTile = (tile: MapTileSummary) => {
        setRemovingTile(tile.id);
        router.delete(route('groups.maps.tiles.destroy', [group.id, map.id, tile.id]), {
            preserveScroll: true,
            onFinish: () => setRemovingTile(null),
        });
    };

    const templateOptions = tile_templates.map((template) => ({
        value: template.id,
        label: `${template.name} · ${template.terrain_type}`,
    }));

    return (
        <AppLayout>
            <Head title={`${map.title} · ${group.name}`} />

            <div className="space-y-8">
                <div className="border-b border-zinc-800 bg-zinc-950/60 p-6 shadow-inner shadow-black/40">
                    <div className="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                        <div>
                            <p className="text-sm uppercase tracking-wide text-indigo-300">{group.name}</p>
                            <h1 className="text-3xl font-semibold text-zinc-100">{map.title}</h1>
                            <p className="mt-2 text-sm text-zinc-400">
                                {map.base_layer === 'hex' ? 'Hex grid' : map.base_layer === 'square' ? 'Square grid' : 'Image backdrop'} ·{' '}
                                {map.orientation === 'pointy' ? 'Pointy-top' : 'Flat-top'} ·{' '}
                                {map.region ? `Region: ${map.region.name}` : 'Unassigned region'}
                            </p>
                            <p className="mt-1 text-xs uppercase tracking-wide text-zinc-500">
                                {liveTiles.length} tiles · {map.gm_only ? 'GM only' : 'Visible to party'}
                            </p>
                        </div>
                        <div className="flex items-center gap-2">
                            <Button asChild variant="outline" className="border-zinc-700 text-sm">
                                <Link href={route('groups.maps.edit', [group.id, map.id])}>Map settings</Link>
                            </Button>
                            <Button asChild variant="ghost" className="text-sm text-zinc-400 hover:text-zinc-200">
                                <Link href={route('groups.show', group.id)}>Back to group</Link>
                            </Button>
                        </div>
                    </div>
                </div>

                <section className="mx-auto max-w-4xl rounded-xl border border-zinc-800 bg-zinc-950/60 p-6 shadow-inner shadow-black/30">
                    <header className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 className="text-lg font-semibold text-zinc-100">Place tile</h2>
                            <p className="text-sm text-zinc-400">Drop a template onto the axial grid. Coordinates use q,r.</p>
                        </div>
                    </header>

                    <form onSubmit={handleCreate} className="mt-4 grid gap-4 md:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="tile_template_id">Tile template</Label>
                            <select
                                id="tile_template_id"
                                value={createForm.data.tile_template_id}
                                onChange={(event) =>
                                    createForm.setData('tile_template_id', event.target.value === '' ? '' : Number(event.target.value))
                                }
                                className="w-full rounded-md border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-zinc-100 focus:border-indigo-500 focus:outline-none focus:ring-0"
                                disabled={tile_templates.length === 0 || createForm.processing}
                            >
                                {tile_templates.length === 0 ? (
                                    <option value="">No templates available</option>
                                ) : (
                                    templateOptions.map((option) => (
                                        <option key={option.value} value={option.value}>
                                            {option.label}
                                        </option>
                                    ))
                                )}
                            </select>
                            {createForm.errors.tile_template_id && (
                                <p className="text-sm text-rose-400">{createForm.errors.tile_template_id}</p>
                            )}
                        </div>
                        <div className="grid gap-4 sm:grid-cols-3">
                            <div className="space-y-2">
                                <Label htmlFor="q">Q</Label>
                                <Input
                                    id="q"
                                    type="number"
                                    value={createForm.data.q}
                                    onChange={(event) => createForm.setData('q', Number(event.target.value))}
                                />
                                {createForm.errors.q && <p className="text-sm text-rose-400">{createForm.errors.q}</p>}
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="r">R</Label>
                                <Input
                                    id="r"
                                    type="number"
                                    value={createForm.data.r}
                                    onChange={(event) => createForm.setData('r', Number(event.target.value))}
                                />
                                {createForm.errors.r && <p className="text-sm text-rose-400">{createForm.errors.r}</p>}
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="elevation">Elevation</Label>
                                <Input
                                    id="elevation"
                                    type="number"
                                    value={createForm.data.elevation}
                                    onChange={(event) => createForm.setData('elevation', Number(event.target.value))}
                                />
                                {createForm.errors.elevation && (
                                    <p className="text-sm text-rose-400">{createForm.errors.elevation}</p>
                                )}
                            </div>
                        </div>
                        <div className="space-y-2 md:col-span-2">
                            <Label htmlFor="variant">Variant JSON (optional)</Label>
                            <Textarea
                                id="variant"
                                value={createForm.data.variant}
                                onChange={(event) => createForm.setData('variant', event.target.value)}
                                placeholder='{"resource":"gold"}'
                            />
                            {createForm.errors.variant && <p className="text-sm text-rose-400">{createForm.errors.variant}</p>}
                        </div>
                        <div className="flex items-center gap-2 md:col-span-2">
                            <Checkbox
                                id="locked"
                                checked={createForm.data.locked}
                                onChange={(event) => createForm.setData('locked', event.target.checked)}
                            />
                            <Label htmlFor="locked" className="text-sm text-zinc-300">
                                Lock tile after placement
                            </Label>
                        </div>
                        <div className="md:col-span-2 flex items-center gap-3">
                            <Button type="submit" disabled={createForm.processing || tile_templates.length === 0}>
                                Place tile
                            </Button>
                            <Button
                                type="button"
                                variant="ghost"
                                disabled={createForm.processing}
                                onClick={() => createForm.reset()}
                            >
                                Reset
                            </Button>
                        </div>
                    </form>
                </section>

                <section className="mx-auto max-w-4xl space-y-4">
                    <header className="flex items-center justify-between">
                        <h2 className="text-lg font-semibold text-zinc-100">Placed tiles</h2>
                        <span className="text-xs uppercase tracking-wide text-zinc-500">{liveTiles.length} total</span>
                    </header>

                    {liveTiles.length === 0 ? (
                        <p className="rounded-lg border border-dashed border-zinc-800 bg-zinc-950/40 p-4 text-sm text-zinc-400">
                            No tiles placed yet. Drop a template above to start charting the region.
                        </p>
                    ) : (
                        <div className="space-y-4">
                            {liveTiles.map((tile) => {
                                const draft = tileEdits[tile.id];

                                return (
                                    <article
                                        key={tile.id}
                                        className="flex flex-col gap-4 rounded-lg border border-zinc-800 bg-zinc-950/50 p-4 md:flex-row md:items-start md:justify-between"
                                    >
                                        <div className="space-y-2">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <h3 className="text-base font-semibold text-zinc-100">
                                                    {tile.template.name}
                                                </h3>
                                                <span className="rounded-full bg-zinc-800 px-2 py-0.5 text-[11px] uppercase tracking-wide text-zinc-400">
                                                    q {tile.q}, r {tile.r}
                                                </span>
                                                {tile.locked && (
                                                    <span className="rounded-full bg-amber-500/20 px-2 py-0.5 text-[11px] uppercase tracking-wide text-amber-200">
                                                        Locked
                                                    </span>
                                                )}
                                            </div>
                                            <p className="text-sm text-zinc-400">
                                                Terrain: {tile.template.terrain_type} · Movement {tile.template.movement_cost} · Defense +{tile.template.defense_bonus}
                                            </p>
                                            <div className="grid gap-3 sm:grid-cols-2">
                                                <div className="space-y-1">
                                                    <Label htmlFor={`tile-template-${tile.id}`} className="text-xs uppercase tracking-wide text-zinc-500">
                                                        Template
                                                    </Label>
                                                    <select
                                                        id={`tile-template-${tile.id}`}
                                                        value={draft?.tile_template_id ?? tile.template.id}
                                                        onChange={(event) =>
                                                            handleTileDraftChange(tile.id, 'tile_template_id', Number(event.target.value))
                                                        }
                                                        className="w-full rounded-md border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-zinc-100 focus:border-indigo-500 focus:outline-none focus:ring-0"
                                                        disabled={updatingTile === tile.id}
                                                    >
                                                        {templateOptions.map((option) => (
                                                            <option key={option.value} value={option.value}>
                                                                {option.label}
                                                            </option>
                                                        ))}
                                                    </select>
                                                </div>
                                                <div className="space-y-1">
                                                    <Label htmlFor={`tile-elevation-${tile.id}`} className="text-xs uppercase tracking-wide text-zinc-500">
                                                        Elevation
                                                    </Label>
                                                    <Input
                                                        id={`tile-elevation-${tile.id}`}
                                                        type="number"
                                                        value={draft?.elevation ?? tile.elevation}
                                                        onChange={(event) =>
                                                            handleTileDraftChange(tile.id, 'elevation',
                                                                event.target.value === '' ? '' : Number(event.target.value)
                                                            )
                                                        }
                                                        disabled={updatingTile === tile.id}
                                                    />
                                                </div>
                                                <div className="space-y-1 sm:col-span-2">
                                                    <Label htmlFor={`tile-variant-${tile.id}`} className="text-xs uppercase tracking-wide text-zinc-500">
                                                        Variant JSON (optional)
                                                    </Label>
                                                    <Textarea
                                                        id={`tile-variant-${tile.id}`}
                                                        value={draft?.variant ?? ''}
                                                        onChange={(event) => handleTileDraftChange(tile.id, 'variant', event.target.value)}
                                                        disabled={updatingTile === tile.id}
                                                    />
                                                </div>
                                            </div>
                                        </div>

                                        <div className="flex flex-col gap-2 md:items-end">
                                            <Button
                                                type="button"
                                                size="sm"
                                                disabled={updatingTile === tile.id}
                                                onClick={() => handleTileUpdate(tile.id)}
                                            >
                                                {updatingTile === tile.id ? 'Saving…' : 'Save changes'}
                                            </Button>
                                            <Button
                                                type="button"
                                                size="sm"
                                                variant="outline"
                                                className="border-amber-400/40 text-amber-200"
                                                disabled={updatingTile === tile.id}
                                                onClick={() => handleToggleLock(tile)}
                                            >
                                                {tile.locked ? 'Unlock' : 'Lock'}
                                            </Button>
                                            <Button
                                                type="button"
                                                size="sm"
                                                variant="ghost"
                                                className="text-rose-300 hover:text-rose-200"
                                                disabled={removingTile === tile.id}
                                                onClick={() => handleRemoveTile(tile)}
                                            >
                                                {removingTile === tile.id ? 'Removing…' : 'Remove'}
                                            </Button>
                                        </div>
                                    </article>
                                );
                            })}
                        </div>
                    )}
                </section>
            </div>
        </AppLayout>
    );
}
