import { useCallback, useEffect, useMemo, useRef, useState } from 'react';

type BaseLayer = 'hex' | 'square' | 'image';

type TokenFaction = 'allied' | 'hostile' | 'neutral' | 'hazard';

type MapTileSummary = {
    id: number;
    q: number;
    r: number;
    elevation: number;
    locked: boolean;
    variant: Record<string, unknown> | null;
    template: {
        id: number;
        name: string;
        terrain_type: string;
        movement_cost: number;
        defense_bonus: number;
    };
};

type MapTokenSummary = {
    id: number;
    name: string;
    x: number;
    y: number;
    color: string | null;
    size: string;
    faction: TokenFaction;
    z_index: number;
    hidden: boolean;
};

type TerrainHighlight = {
    type: 'tile';
    q: number;
    r: number;
};

type TokenHighlight = {
    type: 'token';
    x: number;
    y: number;
    name?: string;
};

type Highlight = TerrainHighlight | TokenHighlight;

type TerrainPlacement = {
    q: number;
    r: number;
    templateId?: number;
    elevation?: number;
    variant?: string | null;
    locked?: boolean;
};

type TokenPlacement = {
    x: number;
    y: number;
    draft?: {
        name?: string;
        color?: string | null;
        size?: string;
        faction?: TokenFaction;
        hidden?: boolean;
    };
};

type SelectedTool =
    | { mode: 'inspect' }
    | { mode: 'terrain'; templateId: number | null }
    | { mode: 'token' }
    | { mode: 'fog'; action: 'toggle' };

type MapCanvasBoardProps = {
    baseLayer: BaseLayer;
    orientation: 'pointy' | 'flat';
    tiles: MapTileSummary[];
    tokens: MapTokenSummary[];
    hiddenTileIds: number[];
    selectedTileId: number | null;
    selectedTokenId: number | null;
    tool: SelectedTool;
    terrainPalette: Record<number, string>;
    onPlaceTerrain: (placement: TerrainPlacement) => void;
    onPlaceToken: (placement: TokenPlacement) => void;
    onTokenDrag: (tokenId: number, position: { x: number; y: number }) => void;
    onSelectTile: (tileId: number | null) => void;
    onSelectToken: (tokenId: number | null) => void;
    onToggleFog: (tileId: number) => void;
    highlights?: Highlight[];
    resetSignal?: number;
};

type Viewport = {
    offsetX: number;
    offsetY: number;
    zoom: number;
};

type TokenDragState = {
    tokenId: number;
    offsetX: number;
    offsetY: number;
    worldX: number;
    worldY: number;
};

type DragState =
    | { mode: 'idle' }
    | { mode: 'pan'; originX: number; originY: number; startOffsetX: number; startOffsetY: number; moved: boolean }
    | { mode: 'token'; data: TokenDragState };

const HEX_SIZE = 42;
const SQUARE_SIZE = 72;
const MIN_ZOOM = 0.35;
const MAX_ZOOM = 2.75;
const TERRAIN_COLORS: Record<string, string> = {
    forest: '#254d3a',
    swamp: '#2d3a3a',
    hills: '#584630',
    mountain: '#4d3c3c',
    desert: '#705d33',
    tundra: '#486474',
    ocean: '#1c3d5e',
    river: '#20506d',
    plains: '#3f5330',
    city: '#4f3d5a',
    dungeon: '#3b2f2f',
};

const TOKEN_FACTION_COLORS: Record<TokenFaction, string> = {
    allied: '#4ade80',
    hostile: '#f87171',
    neutral: '#cbd5f5',
    hazard: '#facc15',
};

type WorldPoint = { x: number; y: number };

type TileWorld = { tile: MapTileSummary; world: WorldPoint };
type TokenWorld = { token: MapTokenSummary; world: WorldPoint };

const hexToPixel = (q: number, r: number, orientation: 'pointy' | 'flat'): WorldPoint => {
    if (orientation === 'pointy') {
        const x = HEX_SIZE * Math.sqrt(3) * (q + r / 2);
        const y = HEX_SIZE * (3 / 2) * r;
        return { x, y };
    }

    const x = HEX_SIZE * (3 / 2) * q;
    const y = HEX_SIZE * Math.sqrt(3) * (r + q / 2);
    return { x, y };
};

const pixelToHex = (x: number, y: number, orientation: 'pointy' | 'flat'): { q: number; r: number } => {
    if (orientation === 'pointy') {
        const q = ((Math.sqrt(3) / 3) * x - (1 / 3) * y) / HEX_SIZE;
        const r = ((2 / 3) * y) / HEX_SIZE;
        return cubeRound(q, -q - r, r);
    }

    const q = ((2 / 3) * x) / HEX_SIZE;
    const r = ((-1 / 3) * x + (Math.sqrt(3) / 3) * y) / HEX_SIZE;
    return cubeRound(q, -q - r, r);
};

const cubeRound = (x: number, y: number, z: number): { q: number; r: number } => {
    let rx = Math.round(x);
    let ry = Math.round(y);
    let rz = Math.round(z);

    const xDiff = Math.abs(rx - x);
    const yDiff = Math.abs(ry - y);
    const zDiff = Math.abs(rz - z);

    if (xDiff > yDiff && xDiff > zDiff) {
        rx = -ry - rz;
    } else if (yDiff > zDiff) {
        ry = -rx - rz;
    } else {
        rz = -rx - ry;
    }

    return { q: rx, r: rz };
};

const squareToWorld = (x: number, y: number): WorldPoint => ({
    x: x * SQUARE_SIZE,
    y: y * SQUARE_SIZE,
});

const worldToSquare = (x: number, y: number): { q: number; r: number } => ({
    q: Math.round(x / SQUARE_SIZE),
    r: Math.round(y / SQUARE_SIZE),
});

const terrainColor = (terrain: string, palette: Record<number, string>, templateId: number): string => {
    const paletteColor = palette[templateId];
    if (paletteColor) {
        return paletteColor;
    }

    const normalized = terrain.toLowerCase();
    return TERRAIN_COLORS[normalized] ?? '#3b4252';
};

const tokenRadiusBySize: Record<string, number> = {
    tiny: 12,
    small: 16,
    medium: 20,
    large: 26,
    huge: 32,
    gargantuan: 36,
};

const pointerPosition = (
    event: React.PointerEvent<HTMLCanvasElement>,
    rect: DOMRect
): { x: number; y: number } => ({
    x: event.clientX - rect.left,
    y: event.clientY - rect.top,
});

export function MapCanvasBoard({
    baseLayer,
    orientation,
    tiles,
    tokens,
    hiddenTileIds,
    selectedTileId,
    selectedTokenId,
    tool,
    terrainPalette,
    onPlaceTerrain,
    onPlaceToken,
    onTokenDrag,
    onSelectTile,
    onSelectToken,
    onToggleFog,
    highlights = [],
    resetSignal,
}: MapCanvasBoardProps) {
    const containerRef = useRef<HTMLDivElement | null>(null);
    const canvasRef = useRef<HTMLCanvasElement | null>(null);
    const [dimensions, setDimensions] = useState<{ width: number; height: number }>({ width: 960, height: 640 });
    const [viewport, setViewport] = useState<Viewport>({ offsetX: 0, offsetY: 0, zoom: 1 });
    const [dragState, setDragState] = useState<DragState>({ mode: 'idle' });

    const tileWorlds = useMemo<TileWorld[]>(() => {
        return tiles.map((tile) => {
            if (baseLayer === 'hex') {
                return { tile, world: hexToPixel(tile.q, tile.r, orientation) };
            }

            return { tile, world: squareToWorld(tile.q, tile.r) };
        });
    }, [tiles, baseLayer, orientation]);

    const tokenWorlds = useMemo<TokenWorld[]>(() => {
        return tokens.map((token) => {
            if (baseLayer === 'hex') {
                return { token, world: hexToPixel(token.x, token.y, orientation) };
            }

            return { token, world: squareToWorld(token.x, token.y) };
        });
    }, [tokens, baseLayer, orientation]);

    const worldExtents = useMemo(() => {
        const points = [...tileWorlds.map((entry) => entry.world), ...tokenWorlds.map((entry) => entry.world)];
        if (points.length === 0) {
            return { minX: -SQUARE_SIZE, maxX: SQUARE_SIZE, minY: -SQUARE_SIZE, maxY: SQUARE_SIZE };
        }

        return points.reduce(
            (acc, point) => ({
                minX: Math.min(acc.minX, point.x),
                maxX: Math.max(acc.maxX, point.x),
                minY: Math.min(acc.minY, point.y),
                maxY: Math.max(acc.maxY, point.y),
            }),
            {
                minX: points[0].x,
                maxX: points[0].x,
                minY: points[0].y,
                maxY: points[0].y,
            }
        );
    }, [tileWorlds, tokenWorlds]);

    const worldCenter = useMemo(() => {
        const { minX, maxX, minY, maxY } = worldExtents;
        return {
            x: (minX + maxX) / 2,
            y: (minY + maxY) / 2,
        };
    }, [worldExtents]);

    const hiddenSet = useMemo(() => new Set(hiddenTileIds), [hiddenTileIds]);

    const highlightTiles = useMemo(() => highlights.filter((highlight) => highlight.type === 'tile') as TerrainHighlight[], [
        highlights,
    ]);
    const highlightTokens = useMemo(
        () => highlights.filter((highlight) => highlight.type === 'token') as TokenHighlight[],
        [highlights]
    );

    const terrainLookup = useMemo(() => {
        const map = new Map<string, TileWorld>();
        tileWorlds.forEach((entry) => {
            map.set(`${entry.tile.q}:${entry.tile.r}`, entry);
        });
        return map;
    }, [tileWorlds]);

    const tokenLookup = useMemo(() => {
        const map = new Map<number, TokenWorld>();
        tokenWorlds.forEach((entry) => {
            map.set(entry.token.id, entry);
        });
        return map;
    }, [tokenWorlds]);

    const screenToWorld = useCallback(
        (screenX: number, screenY: number, customViewport?: Viewport): WorldPoint => {
            const { width, height } = dimensions;
            const state = customViewport ?? viewport;

            return {
                x: (screenX - width / 2 - state.offsetX) / state.zoom + worldCenter.x,
                y: (screenY - height / 2 - state.offsetY) / state.zoom + worldCenter.y,
            };
        },
        [dimensions, viewport, worldCenter]
    );

    const worldToScreen = useCallback(
        (worldX: number, worldY: number, customViewport?: Viewport): { x: number; y: number } => {
            const { width, height } = dimensions;
            const state = customViewport ?? viewport;

            return {
                x: (worldX - worldCenter.x) * state.zoom + width / 2 + state.offsetX,
                y: (worldY - worldCenter.y) * state.zoom + height / 2 + state.offsetY,
            };
        },
        [dimensions, viewport, worldCenter]
    );

    const resetViewport = useCallback(
        (initial?: boolean) => {
            const { minX, maxX, minY, maxY } = worldExtents;
            const boundsWidth = Math.max(1, maxX - minX + SQUARE_SIZE * 2);
            const boundsHeight = Math.max(1, maxY - minY + SQUARE_SIZE * 2);
            const widthScale = dimensions.width / boundsWidth;
            const heightScale = dimensions.height / boundsHeight;
            const fitZoom = Math.min(Math.max(Math.min(widthScale, heightScale), MIN_ZOOM), MAX_ZOOM);
            setViewport({
                offsetX: 0,
                offsetY: 0,
                zoom: initial ? Math.min(Math.max(fitZoom, 0.75), 1.25) : fitZoom,
            });
        },
        [dimensions, worldExtents]
    );

    useEffect(() => {
        if (!containerRef.current) {
            return;
        }

        const observer = new ResizeObserver((entries) => {
            for (const entry of entries) {
                if (entry.target !== containerRef.current) {
                    continue;
                }

                const { width, height } = entry.contentRect;
                setDimensions({ width, height });
            }
        });

        observer.observe(containerRef.current);

        return () => observer.disconnect();
    }, []);

    const didInitRef = useRef(false);
    useEffect(() => {
        if (!didInitRef.current) {
            didInitRef.current = true;
            resetViewport(true);
        }
    }, [resetViewport]);

    useEffect(() => {
        if (resetSignal !== undefined) {
            resetViewport();
        }
    }, [resetSignal, resetViewport]);

    useEffect(() => {
        const canvas = canvasRef.current;
        if (!canvas) {
            return;
        }

        const context = canvas.getContext('2d');
        if (!context) {
            return;
        }

        const { width, height } = dimensions;
        context.clearRect(0, 0, width, height);
        context.fillStyle = '#0f172a';
        context.fillRect(0, 0, width, height);

        context.save();
        context.globalAlpha = 0.12;
        context.fillStyle = '#38bdf8';
        for (let y = 0; y < height; y += 64) {
            context.fillRect(0, y, width, 1);
        }
        for (let x = 0; x < width; x += 64) {
            context.fillRect(x, 0, 1, height);
        }
        context.restore();

        const drawHex = (center: { x: number; y: number }, size: number, strokeStyle: string) => {
            context.beginPath();
            for (let i = 0; i < 6; i += 1) {
                const angleDeg = orientation === 'pointy' ? 60 * i - 30 : 60 * i;
                const angleRad = (Math.PI / 180) * angleDeg;
                const x = center.x + size * Math.cos(angleRad);
                const y = center.y + size * Math.sin(angleRad);
                if (i === 0) {
                    context.moveTo(x, y);
                } else {
                    context.lineTo(x, y);
                }
            }
            context.closePath();
            context.strokeStyle = strokeStyle;
            context.stroke();
        };

        tileWorlds.forEach(({ tile, world }) => {
            const { x, y } = worldToScreen(world.x, world.y);
            const fill = terrainColor(tile.template.terrain_type, terrainPalette, tile.template.id);
            const selected = tile.id === selectedTileId;
            const radius = baseLayer === 'hex' ? HEX_SIZE * viewport.zoom : (SQUARE_SIZE * viewport.zoom) / 2;

            context.save();
            context.translate(x, y);
            context.fillStyle = fill;
            context.globalAlpha = selected ? 0.95 : 0.82;

            if (baseLayer === 'hex') {
                context.beginPath();
                for (let i = 0; i < 6; i += 1) {
                    const angleDeg = orientation === 'pointy' ? 60 * i - 30 : 60 * i;
                    const angleRad = (Math.PI / 180) * angleDeg;
                    const px = radius * Math.cos(angleRad);
                    const py = radius * Math.sin(angleRad);
                    if (i === 0) {
                        context.moveTo(px, py);
                    } else {
                        context.lineTo(px, py);
                    }
                }
                context.closePath();
                context.fill();
            } else {
                context.fillRect(-radius, -radius, radius * 2, radius * 2);
            }

            context.restore();

            context.save();
            context.translate(x, y);
            context.lineWidth = selected ? 3 : 1.5;
            context.strokeStyle = selected ? '#fbbf24' : '#475569';
            context.globalAlpha = selected ? 1 : 0.9;
            if (baseLayer === 'hex') {
                drawHex({ x: 0, y: 0 }, radius, context.strokeStyle);
            } else {
                context.strokeRect(-radius, -radius, radius * 2, radius * 2);
            }
            context.restore();

            if (hiddenSet.has(tile.id)) {
                context.save();
                context.globalAlpha = 0.6;
                context.fillStyle = '#0f172a';
                if (baseLayer === 'hex') {
                    context.beginPath();
                    for (let i = 0; i < 6; i += 1) {
                        const angleDeg = orientation === 'pointy' ? 60 * i - 30 : 60 * i;
                        const angleRad = (Math.PI / 180) * angleDeg;
                        const px = x + radius * Math.cos(angleRad);
                        const py = y + radius * Math.sin(angleRad);
                        if (i === 0) {
                            context.moveTo(px, py);
                        } else {
                            context.lineTo(px, py);
                        }
                    }
                    context.closePath();
                    context.fill();
                } else {
                    context.fillRect(x - radius, y - radius, radius * 2, radius * 2);
                }
                context.restore();
            }
        });

        highlightTiles.forEach((highlight) => {
            const key = `${highlight.q}:${highlight.r}`;
            const entry = terrainLookup.get(key);
            const world = entry?.world ?? (baseLayer === 'hex'
                ? hexToPixel(highlight.q, highlight.r, orientation)
                : squareToWorld(highlight.q, highlight.r));
            const { x, y } = worldToScreen(world.x, world.y);
            const radius = baseLayer === 'hex' ? HEX_SIZE * viewport.zoom : (SQUARE_SIZE * viewport.zoom) / 2;

            context.save();
            context.strokeStyle = '#38bdf8';
            context.lineWidth = 2.5;
            context.setLineDash([8, 6]);
            if (baseLayer === 'hex') {
                drawHex({ x, y }, radius + 6 * viewport.zoom, context.strokeStyle);
            } else {
                context.strokeRect(
                    x - radius - 6 * viewport.zoom,
                    y - radius - 6 * viewport.zoom,
                    (radius + 6 * viewport.zoom) * 2,
                    (radius + 6 * viewport.zoom) * 2
                );
            }
            context.restore();
        });

        const renderTokens: TokenWorld[] = tokenWorlds.map((entry) => ({ ...entry }));
        if (dragState.mode === 'token') {
            renderTokens.forEach((entry) => {
                if (entry.token.id === dragState.data.tokenId) {
                    entry.world = { x: dragState.data.worldX, y: dragState.data.worldY };
                }
            });
        }

        renderTokens.forEach(({ token, world }) => {
            const { x, y } = worldToScreen(world.x, world.y);
            const factionColor = TOKEN_FACTION_COLORS[token.faction] ?? '#cbd5f5';
            const selected = token.id === selectedTokenId;
            const baseRadius = tokenRadiusBySize[token.size] ?? 20;
            const radius = Math.max(12, baseRadius * viewport.zoom * 0.9);

            context.save();
            context.translate(x, y);
            context.beginPath();
            context.fillStyle = token.color ?? factionColor;
            context.globalAlpha = token.hidden ? 0.55 : 0.9;
            context.arc(0, 0, radius, 0, Math.PI * 2);
            context.fill();
            context.lineWidth = selected ? 3 : 2;
            context.strokeStyle = selected ? '#f59e0b' : factionColor;
            context.stroke();

            context.fillStyle = '#0f172a';
            context.globalAlpha = 0.95;
            context.font = `${Math.max(10, radius * 0.65)}px "Inter", sans-serif`;
            context.textAlign = 'center';
            context.textBaseline = 'middle';
            context.fillText(token.name.slice(0, 3).toUpperCase(), 0, 0);
            context.restore();
        });

        highlightTokens.forEach((highlight) => {
            const world = baseLayer === 'hex'
                ? hexToPixel(highlight.x, highlight.y, orientation)
                : squareToWorld(highlight.x, highlight.y);
            const { x, y } = worldToScreen(world.x, world.y);
            context.save();
            context.strokeStyle = '#f472b6';
            context.setLineDash([6, 6]);
            context.lineWidth = 2;
            context.beginPath();
            context.arc(x, y, 30 * viewport.zoom, 0, Math.PI * 2);
            context.stroke();
            context.restore();
        });
    }, [
        baseLayer,
        dimensions,
        dragState,
        highlightTiles,
        highlightTokens,
        orientation,
        selectedTileId,
        selectedTokenId,
        terrainLookup,
        terrainPalette,
        tileWorlds,
        tokenWorlds,
        viewport,
        worldToScreen,
        hiddenSet,
    ]);

    const getTileFromWorld = useCallback(
        (world: WorldPoint): TileWorld | null => {
            if (baseLayer === 'hex') {
                const axial = pixelToHex(world.x, world.y, orientation);
                const key = `${axial.q}:${axial.r}`;
                return terrainLookup.get(key) ?? null;
            }

            const square = worldToSquare(world.x, world.y);
            const key = `${square.q}:${square.r}`;
            return terrainLookup.get(key) ?? null;
        },
        [baseLayer, orientation, terrainLookup]
    );

    const handleDoubleClick = useCallback(
        (event: React.MouseEvent<HTMLCanvasElement>) => {
            const canvas = canvasRef.current;
            if (!canvas) {
                return;
            }

            const rect = canvas.getBoundingClientRect();
            const { x, y } = pointerPosition(event as React.PointerEvent<HTMLCanvasElement>, rect);
            const world = screenToWorld(x, y);

            if (tool.mode === 'terrain') {
                const placement = baseLayer === 'hex'
                    ? pixelToHex(world.x, world.y, orientation)
                    : worldToSquare(world.x, world.y);

                if (tool.templateId !== null) {
                    onPlaceTerrain({ q: placement.q, r: placement.r, templateId: tool.templateId });
                }
                return;
            }

            if (tool.mode === 'token') {
                const placement = baseLayer === 'hex'
                    ? pixelToHex(world.x, world.y, orientation)
                    : worldToSquare(world.x, world.y);
                onPlaceToken({ x: placement.q, y: placement.r });
            }
        },
        [baseLayer, onPlaceTerrain, onPlaceToken, orientation, screenToWorld, tool]
    );

    const handleDrop = useCallback(
        (event: React.DragEvent<HTMLDivElement>) => {
            event.preventDefault();
            const json = event.dataTransfer.getData('application/json');
            const text = json || event.dataTransfer.getData('text/plain');
            if (!text) {
                return;
            }

            try {
                const payload = JSON.parse(text) as { kind: string; [key: string]: unknown };
                const canvas = canvasRef.current;
                if (!canvas) {
                    return;
                }

                const rect = canvas.getBoundingClientRect();
                const { x, y } = { x: event.clientX - rect.left, y: event.clientY - rect.top };
                const world = screenToWorld(x, y);

                if (payload.kind === 'tile-template') {
                    const placement = baseLayer === 'hex'
                        ? pixelToHex(world.x, world.y, orientation)
                        : worldToSquare(world.x, world.y);
                    onPlaceTerrain({
                        q: placement.q,
                        r: placement.r,
                        templateId: typeof payload.templateId === 'number' ? payload.templateId : undefined,
                    });
                }

                if (payload.kind === 'token-draft') {
                    const placement = baseLayer === 'hex'
                        ? pixelToHex(world.x, world.y, orientation)
                        : worldToSquare(world.x, world.y);
                    onPlaceToken({
                        x: placement.q,
                        y: placement.r,
                        draft: {
                            name: typeof payload.name === 'string' ? payload.name : undefined,
                            color: typeof payload.color === 'string' ? payload.color : undefined,
                            size: typeof payload.size === 'string' ? payload.size : undefined,
                            faction: typeof payload.faction === 'string' ? (payload.faction as TokenFaction) : undefined,
                            hidden: typeof payload.hidden === 'boolean' ? payload.hidden : undefined,
                        },
                    });
                }
            } catch (error) {
                console.warn('Unable to parse canvas drop payload', error);
            }
        },
        [baseLayer, onPlaceTerrain, onPlaceToken, orientation, screenToWorld]
    );

    const handleDragOver = useCallback((event: React.DragEvent<HTMLDivElement>) => {
        event.preventDefault();
        event.dataTransfer.dropEffect = 'copy';
    }, []);

    const handlePointerDown = useCallback(
        (event: React.PointerEvent<HTMLCanvasElement>) => {
            const canvas = canvasRef.current;
            if (!canvas) {
                return;
            }

            canvas.focus();

            const rect = canvas.getBoundingClientRect();
            const { x, y } = pointerPosition(event, rect);
            const world = screenToWorld(x, y);

            const hitToken = tokenWorlds
                .map((entry) => {
                    const screen = worldToScreen(entry.world.x, entry.world.y);
                    const dx = x - screen.x;
                    const dy = y - screen.y;
                    const radius = (tokenRadiusBySize[entry.token.size] ?? 20) * viewport.zoom;
                    const distance = Math.sqrt(dx * dx + dy * dy);
                    return { entry, distance, radius };
                })
                .filter((candidate) => candidate.distance <= candidate.radius)
                .sort((a, b) => b.entry.token.z_index - a.entry.token.z_index)[0];

            if (hitToken) {
                const { entry } = hitToken;
                const offsetX = world.x - entry.world.x;
                const offsetY = world.y - entry.world.y;
                setDragState({
                    mode: 'token',
                    data: {
                        tokenId: entry.token.id,
                        offsetX,
                        offsetY,
                        worldX: entry.world.x,
                        worldY: entry.world.y,
                    },
                });
                canvas.setPointerCapture(event.pointerId);
                onSelectToken(entry.token.id);
                return;
            }

            setDragState({
                mode: 'pan',
                originX: x,
                originY: y,
                startOffsetX: viewport.offsetX,
                startOffsetY: viewport.offsetY,
                moved: false,
            });
            canvas.setPointerCapture(event.pointerId);
        },
        [onSelectToken, screenToWorld, tokenWorlds, viewport]
    );

    const handlePointerMove = useCallback(
        (event: React.PointerEvent<HTMLCanvasElement>) => {
            if (dragState.mode === 'idle') {
                return;
            }

            const canvas = canvasRef.current;
            if (!canvas) {
                return;
            }

            const rect = canvas.getBoundingClientRect();
            const { x, y } = pointerPosition(event, rect);

            if (dragState.mode === 'pan') {
                const deltaX = x - dragState.originX;
                const deltaY = y - dragState.originY;
                setViewport((current) => ({
                    offsetX: dragState.startOffsetX + deltaX,
                    offsetY: dragState.startOffsetY + deltaY,
                    zoom: current.zoom,
                }));

                const moved = dragState.moved || Math.hypot(deltaX, deltaY) > 4;
                if (moved !== dragState.moved) {
                    setDragState({ ...dragState, moved });
                }
                return;
            }

            if (dragState.mode === 'token') {
                const world = screenToWorld(x, y);
                setDragState({
                    mode: 'token',
                    data: {
                        ...dragState.data,
                        worldX: world.x - dragState.data.offsetX,
                        worldY: world.y - dragState.data.offsetY,
                    },
                });
            }
        },
        [dragState, screenToWorld]
    );

    const finishPointerInteraction = useCallback(
        (event: React.PointerEvent<HTMLCanvasElement>) => {
            if (dragState.mode === 'idle') {
                return;
            }

            const canvas = canvasRef.current;
            if (!canvas) {
                return;
            }

            canvas.releasePointerCapture(event.pointerId);

            const rect = canvas.getBoundingClientRect();
            const { x, y } = pointerPosition(event, rect);
            const world = screenToWorld(x, y);

            if (dragState.mode === 'token') {
                const nextWorldX = world.x - dragState.data.offsetX;
                const nextWorldY = world.y - dragState.data.offsetY;
                const placement = baseLayer === 'hex'
                    ? pixelToHex(nextWorldX, nextWorldY, orientation)
                    : worldToSquare(nextWorldX, nextWorldY);
                onTokenDrag(dragState.data.tokenId, { x: placement.q, y: placement.r });
                setDragState({ mode: 'idle' });
                return;
            }

            if (!dragState.moved) {
                const tile = getTileFromWorld(world);
                if (tile && event.altKey) {
                    onToggleFog(tile.tile.id);
                } else {
                    onSelectTile(tile?.tile.id ?? null);
                    if (tile === null) {
                        onSelectToken(null);
                    }
                }
            }

            setDragState({ mode: 'idle' });
        },
        [
            baseLayer,
            dragState,
            getTileFromWorld,
            onSelectTile,
            onSelectToken,
            onTokenDrag,
            onToggleFog,
            orientation,
            screenToWorld,
        ]
    );

    const handlePointerUp = useCallback(
        (event: React.PointerEvent<HTMLCanvasElement>) => {
            finishPointerInteraction(event);
        },
        [finishPointerInteraction]
    );

    const handlePointerLeave = useCallback(
        (event: React.PointerEvent<HTMLCanvasElement>) => {
            if (dragState.mode !== 'idle') {
                finishPointerInteraction(event);
            }
        },
        [dragState.mode, finishPointerInteraction]
    );

    const handleWheel = useCallback(
        (event: React.WheelEvent<HTMLCanvasElement>) => {
            event.preventDefault();
            const { deltaY } = event;
            const factor = deltaY > 0 ? 0.9 : 1.1;

            setViewport((current) => {
                const newZoom = Math.min(Math.max(current.zoom * factor, MIN_ZOOM), MAX_ZOOM);
                if (Math.abs(newZoom - current.zoom) < 0.0001) {
                    return current;
                }

                const canvas = canvasRef.current;
                if (!canvas) {
                    return { ...current, zoom: newZoom };
                }

                const rect = canvas.getBoundingClientRect();
                const screenX = event.clientX - rect.left;
                const screenY = event.clientY - rect.top;
                const before = screenToWorld(screenX, screenY, current);
                const afterScreen = worldToScreen(before.x, before.y, { ...current, zoom: newZoom });
                const offsetX = current.offsetX + (screenX - afterScreen.x);
                const offsetY = current.offsetY + (screenY - afterScreen.y);

                return {
                    offsetX,
                    offsetY,
                    zoom: newZoom,
                };
            });
        },
        [screenToWorld, worldToScreen]
    );

    return (
        <div
            ref={containerRef}
            className="relative h-full w-full overflow-hidden rounded-xl border border-zinc-800 bg-zinc-950/80 shadow-inner shadow-black/40"
            onDrop={handleDrop}
            onDragOver={handleDragOver}
        >
            <canvas
                ref={canvasRef}
                className="h-full w-full cursor-crosshair outline-none"
                width={dimensions.width}
                height={dimensions.height}
                onPointerDown={handlePointerDown}
                onPointerMove={handlePointerMove}
                onPointerUp={handlePointerUp}
                onPointerLeave={handlePointerLeave}
                onDoubleClick={handleDoubleClick}
                onWheel={handleWheel}
                onContextMenu={(event) => event.preventDefault()}
                tabIndex={0}
                role="application"
                aria-label="Region map canvas"
            />
            <div className="pointer-events-none absolute inset-x-0 bottom-2 flex justify-center">
                <div className="rounded-full bg-zinc-900/80 px-3 py-1 text-[11px] uppercase tracking-wide text-zinc-300 shadow-lg shadow-black/50">
                    Drag tokens to reposition · Double-click to place · Alt-click tile to toggle fog · Scroll to zoom
                </div>
            </div>
        </div>
    );
}

