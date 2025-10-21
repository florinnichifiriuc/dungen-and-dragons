import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { Minus, Plus, X } from 'lucide-react';

import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { getEcho } from '@/lib/realtime';
import { MapCanvasBoard } from '@/components/maps/MapCanvasBoard';
import { AiCompanionDrawer } from '@/components/ai/AiCompanionDrawer';

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

type TokenFaction = 'allied' | 'hostile' | 'neutral' | 'hazard';

type MapTokenSummary = {
    id: number;
    name: string;
    x: number;
    y: number;
    color: string | null;
    size: string;
    faction: TokenFaction;
    initiative: number | null;
    status_effects: string | null;
    status_conditions: string[];
    status_condition_durations: Record<string, number>;
    hit_points: number | null;
    temporary_hit_points: number | null;
    max_hit_points: number | null;
    z_index: number;
    hidden: boolean;
    gm_note: string | null;
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
        fog: {
            hidden_tile_ids: number[];
        };
    };
    tiles: MapTileSummary[];
    tile_templates: TileTemplateOption[];
    tokens: MapTokenSummary[];
    token_conditions: { value: string; label: string }[];
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

type CreateTokenForm = {
    name: string;
    x: number;
    y: number;
    color: string;
    size: string;
    faction: TokenFaction;
    initiative: number | '';
    status_effects: string;
    status_conditions: string[];
    status_condition_durations: Record<string, number | ''>;
    hit_points: number | '';
    temporary_hit_points: number | '';
    max_hit_points: number | '';
    z_index: number | '';
    hidden: boolean;
    gm_note: string;
};

type TokenDraft = {
    name: string;
    x: number | '';
    y: number | '';
    color: string;
    size: string;
    faction: TokenFaction;
    initiative: number | '';
    status_effects: string;
    status_conditions: string[];
    status_condition_durations: Record<string, number | ''>;
    hit_points: number | '';
    temporary_hit_points: number | '';
    max_hit_points: number | '';
    z_index: number | '';
    gm_note: string;
};

type MapTokenEventPayload = {
    token: Partial<MapTokenSummary> & { id: number };
};

type ConditionExpirationEventPayload = {
    token: { id: number; name?: string };
    conditions: string[];
};

type ConditionAlert = {
    tokenId: number;
    tokenName: string;
    conditions: string[];
    createdAt: number;
};

type ConditionTimerEntry = {
    condition: string;
    roundsRemaining: number;
};

type ConditionTimerGroup = {
    tokenId: number;
    tokenName: string;
    faction: TokenFaction;
    timers: ConditionTimerEntry[];
};

type SelectedTimerMap = Record<number, string[]>;

type BatchAdjustmentPlan =
    | {
          tokenId: number;
          condition: string;
          type: 'delta';
          value: number;
          expected: number;
      }
    | {
          tokenId: number;
          condition: string;
          type: 'set';
          value: number;
          expected: number;
      };

type CanvasToolState =
    | { mode: 'inspect' }
    | { mode: 'terrain'; templateId: number | null }
    | { mode: 'token' };

type CanvasHighlight =
    | { type: 'tile'; q: number; r: number }
    | { type: 'token'; x: number; y: number; name?: string };

type AiDraftTile = {
    q: number;
    r: number;
    templateId?: number | null;
    templateKey?: string | null;
    templateName?: string | null;
    elevation?: number | null;
    variant?: string | null;
    locked?: boolean;
};

type AiDraftToken = {
    name?: string;
    x: number;
    y: number;
    color?: string | null;
    size?: string | null;
    faction?: TokenFaction | null;
    hidden?: boolean;
};

type AiPlan = {
    summary: string;
    layoutNotes: string[];
    fogSettings?: { mode?: string; opacity?: number | string; notes?: string } | null;
    explorationHooks: string[];
    imagePrompt?: string | null;
    draftTiles: AiDraftTile[];
    draftTokens: AiDraftToken[];
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

const orderTokens = (items: MapTokenSummary[]): MapTokenSummary[] =>
    [...items].sort((a, b) => {
        const hasInitiativeA = a.initiative !== null && a.initiative !== undefined;
        const hasInitiativeB = b.initiative !== null && b.initiative !== undefined;

        if (hasInitiativeA && hasInitiativeB && a.initiative !== b.initiative) {
            return (b.initiative ?? 0) - (a.initiative ?? 0);
        }

        if (hasInitiativeA && !hasInitiativeB) {
            return -1;
        }

        if (!hasInitiativeA && hasInitiativeB) {
            return 1;
        }

        if (a.z_index !== b.z_index) {
            return b.z_index - a.z_index;
        }

        const nameCompare = a.name.localeCompare(b.name);

        if (nameCompare !== 0) {
            return nameCompare;
        }

        return a.id - b.id;
    });

const MAX_CONDITION_DURATION = 20;
const CONDITION_ALERT_LIFESPAN = 90_000;
const CRITICAL_TIMER_THRESHOLD = 3;

const formatRoundsRemaining = (value: number): string =>
    `${value} ${value === 1 ? 'round' : 'rounds'}`;

const getRoundsAccentClass = (value: number): string => {
    if (value <= 1) {
        return 'text-rose-200';
    }

    if (value <= 3) {
        return 'text-amber-200';
    }

    return 'text-emerald-200';
};

const normalizeConditionDurations = (
    durations: Record<string, number | null | undefined> | null | undefined,
    orderedConditions: string[]
): Record<string, number> => {
    const next: Record<string, number> = {};

    orderedConditions.forEach((condition) => {
        const value = durations?.[condition];

        if (typeof value === 'number' && Number.isFinite(value)) {
            next[condition] = value;
        }
    });

    return next;
};

const syncDurationDraft = (
    durations: Record<string, number | ''>,
    orderedConditions: string[]
): Record<string, number | ''> => {
    const next: Record<string, number | ''> = {};

    orderedConditions.forEach((condition) => {
        const value = durations[condition];

        if (value === '' || value === undefined || value === null) {
            next[condition] = '';
            return;
        }

        const numericValue = typeof value === 'number' ? value : Number(value);

        if (Number.isNaN(numericValue)) {
            next[condition] = '';
            return;
        }

        next[condition] = Math.min(Math.max(Math.round(numericValue), 1), MAX_CONDITION_DURATION);
    });

    return next;
};

const serializeConditionDurations = (
    durations: Record<string, number | ''>,
    orderedConditions: string[]
): Record<string, number> | null => {
    const next: Record<string, number> = {};

    orderedConditions.forEach((condition) => {
        const value = durations[condition];

        if (value === '' || value === undefined || value === null) {
            return;
        }

        const numericValue = typeof value === 'number' ? value : Number(value);

        if (Number.isNaN(numericValue)) {
            return;
        }

        next[condition] = Math.min(Math.max(Math.round(numericValue), 1), MAX_CONDITION_DURATION);
    });

    return Object.keys(next).length === 0 ? null : next;
};

const prepareTokenForState = (token: MapTokenSummary): MapTokenSummary => {
    const orderedConditions = orderConditions(token.status_conditions ?? []);
    const normalizedDurations = normalizeConditionDurations(
        token.status_condition_durations ?? {},
        orderedConditions
    );

    return {
        ...token,
        status_conditions: orderedConditions,
        status_condition_durations: normalizedDurations,
    };
};

export default function MapShow({
    group,
    map,
    tiles,
    tile_templates,
    tokens,
    token_conditions,
}: MapShowProps) {
    const createForm = useForm<CreateTileForm>({
        tile_template_id: tile_templates[0]?.id ?? '',
        q: 0,
        r: 0,
        elevation: 0,
        variant: '',
        locked: false,
    });

    const tokenForm = useForm<CreateTokenForm>({
        name: '',
        x: 0,
        y: 0,
        color: '#f97316',
        size: 'medium',
        faction: 'neutral',
        initiative: '',
        status_effects: '',
        status_conditions: [],
        status_condition_durations: {},
        hit_points: '',
        temporary_hit_points: '',
        max_hit_points: '',
        z_index: 0,
        hidden: false,
        gm_note: '',
    });

    const baseLayer = (map.base_layer ?? 'hex') as 'hex' | 'square' | 'image';
    const mapOrientation = map.orientation === 'flat' ? 'flat' : 'pointy';

    const conditionOrder = useMemo(
        () => token_conditions.map((option) => option.value),
        [token_conditions]
    );
    const conditionLabelMap = useMemo(() => {
        return token_conditions.reduce<Record<string, string>>((acc, option) => {
            acc[option.value] = option.label;
            return acc;
        }, {});
    }, [token_conditions]);

    const terrainPalette = useMemo(() => {
        const swatches = ['#38bdf8', '#f472b6', '#22d3ee', '#f97316', '#a855f7', '#4ade80', '#facc15', '#f9a8d4'];
        const palette: Record<number, string> = {};
        tile_templates.forEach((template, index) => {
            palette[template.id] = swatches[index % swatches.length];
        });

        return palette;
    }, [tile_templates]);

    const aiHighlights = useMemo<CanvasHighlight[]>(() => {
        if (!aiPlan) {
            return [];
        }

        const tileHighlights = aiPlan.draftTiles.map<CanvasHighlight>((tile) => ({
            type: 'tile',
            q: tile.q,
            r: tile.r,
        }));

        const tokenHighlights = aiPlan.draftTokens.map<CanvasHighlight>((token) => ({
            type: 'token',
            x: token.x,
            y: token.y,
            name: token.name,
        }));

        return [...tileHighlights, ...tokenHighlights];
    }, [aiPlan]);

    const templateLookupByName = useMemo(() => {
        const map = new Map<string, TileTemplateOption>();
        tile_templates.forEach((template) => {
            map.set(template.name.toLowerCase(), template);
            map.set(template.terrain_type.toLowerCase(), template);
        });

        return map;
    }, [tile_templates]);

    const aiContext = useMemo(
        () => ({
            title: map.title,
            base_layer: map.base_layer,
            orientation: map.orientation,
            fog_data: {
                hidden_tile_ids: hiddenTiles,
                hidden_count: hiddenTiles.length,
                total_tiles: liveTiles.length,
            },
            templates: tile_templates.slice(0, 18).map((template) => ({
                id: template.id,
                name: template.name,
                terrain: template.terrain_type,
            })),
            tokens: liveTokens.slice(0, 18).map((token) => ({
                name: token.name,
                faction: token.faction,
                x: token.x,
                y: token.y,
            })),
        }),
        [hiddenTiles, liveTiles, liveTokens, map.base_layer, map.orientation, map.title, tile_templates]
    );

    const selectedTile = useMemo(
        () => liveTiles.find((tile) => tile.id === selectedTileId) ?? null,
        [liveTiles, selectedTileId]
    );
    const selectedToken = useMemo(
        () => liveTokens.find((token) => token.id === selectedTokenId) ?? null,
        [liveTokens, selectedTokenId]
    );

    const conditionOrderRef = useRef(conditionOrder);

    const orderConditions = (values: string[]): string[] =>
        conditionOrder.filter((condition) => values.includes(condition));

    const toggleCondition = (values: string[], condition: string): string[] => {
        const nextValues = values.includes(condition)
            ? values.filter((value) => value !== condition)
            : [...values, condition];

        return orderConditions(nextValues);
    };

    const buildTokenPayload = useCallback(
        (data: CreateTokenForm): Record<string, unknown> => {
            const orderedConditions = orderConditions(data.status_conditions);
            const durations = serializeConditionDurations(
                data.status_condition_durations,
                orderedConditions
            );

            return {
                name: data.name,
                x: Number(data.x),
                y: Number(data.y),
                color: data.color.trim() === '' ? null : data.color,
                size: data.size,
                faction: data.faction,
                initiative: data.initiative === '' ? null : Number(data.initiative),
                status_effects: data.status_effects.trim() === '' ? null : data.status_effects,
                status_conditions: orderedConditions,
                status_condition_durations: durations ?? null,
                hit_points: data.hit_points === '' ? null : Number(data.hit_points),
                temporary_hit_points:
                    data.temporary_hit_points === '' ? null : Number(data.temporary_hit_points),
                max_hit_points: data.max_hit_points === '' ? null : Number(data.max_hit_points),
                z_index: data.z_index === '' ? 0 : Number(data.z_index),
                hidden: data.hidden,
                gm_note: data.gm_note.trim() === '' ? null : data.gm_note,
            };
        },
        [orderConditions]
    );

    const statusConditionsError =
        Object.entries(tokenForm.errors).find(([key]) => key.startsWith('status_conditions'))?.[1] ?? null;

    const [liveTiles, setLiveTiles] = useState<MapTileSummary[]>(() => orderTiles(tiles));
    const [tileEdits, setTileEdits] = useState<Record<number, TileDraft>>({});
    const [updatingTile, setUpdatingTile] = useState<number | null>(null);
    const [removingTile, setRemovingTile] = useState<number | null>(null);
    const [hiddenTiles, setHiddenTiles] = useState<number[]>(() => map.fog.hidden_tile_ids);
    const [fogPendingTileId, setFogPendingTileId] = useState<number | null>(null);
    const [liveTokens, setLiveTokens] = useState<MapTokenSummary[]>(() =>
        orderTokens(tokens.map((token) => prepareTokenForState(token)))
    );
    const [tokenEdits, setTokenEdits] = useState<Record<number, TokenDraft>>({});
    const [updatingToken, setUpdatingToken] = useState<number | null>(null);
    const [removingToken, setRemovingToken] = useState<number | null>(null);
    const [tokenFactionFilter, setTokenFactionFilter] = useState<'all' | TokenFaction>('all');
    const [timerSearchQuery, setTimerSearchQuery] = useState('');
    const [showCriticalTimersOnly, setShowCriticalTimersOnly] = useState(false);
    const [conditionAlerts, setConditionAlerts] = useState<ConditionAlert[]>([]);
    const [selectedTimers, setSelectedTimers] = useState<SelectedTimerMap>({});
    const [batchDeltaInput, setBatchDeltaInput] = useState('1');
    const [batchSetInput, setBatchSetInput] = useState('');
    const [batchProcessing, setBatchProcessing] = useState(false);
    const [batchSummary, setBatchSummary] = useState<string | null>(null);
    const [pendingBatchSummary, setPendingBatchSummary] = useState<string | null>(null);
    const initialCanvasTemplateId =
        typeof createForm.data.tile_template_id === 'number'
            ? createForm.data.tile_template_id
            : tile_templates[0]?.id ?? null;
    const [canvasTool, setCanvasTool] = useState<CanvasToolState>({
        mode: 'terrain',
        templateId: initialCanvasTemplateId,
    });
    const [selectedTileId, setSelectedTileId] = useState<number | null>(null);
    const [selectedTokenId, setSelectedTokenId] = useState<number | null>(null);
    const [viewportResetSignal, setViewportResetSignal] = useState(0);
    const [aiPlan, setAiPlan] = useState<AiPlan | null>(null);
    const [aiSummary, setAiSummary] = useState('');
    const [aiError, setAiError] = useState<string | null>(null);

    const fogBusy = fogPendingTileId !== null;

    const dismissConditionAlert = (createdAt: number) => {
        setConditionAlerts((current) => current.filter((alert) => alert.createdAt !== createdAt));
    };

    const clearConditionAlerts = () => {
        setConditionAlerts([]);
    };

    useEffect(() => {
        conditionOrderRef.current = conditionOrder;
    }, [conditionOrder]);

    useEffect(() => {
        setLiveTiles(orderTiles(tiles));
    }, [tiles]);

    useEffect(() => {
        setLiveTokens(orderTokens(tokens.map((token) => prepareTokenForState(token))));
    }, [tokens]);

    useEffect(() => {
        const interval = window.setInterval(() => {
            const threshold = Date.now() - CONDITION_ALERT_LIFESPAN;

            setConditionAlerts((current) =>
                current.filter((alert) => alert.createdAt >= threshold)
            );
        }, 5000);

        return () => window.clearInterval(interval);
    }, []);

    useEffect(() => {
        if (tile_templates.length > 0 && createForm.data.tile_template_id === '') {
            createForm.setData('tile_template_id', tile_templates[0].id);
        }
    }, [tile_templates]);

    useEffect(() => {
        const templateId =
            typeof createForm.data.tile_template_id === 'number'
                ? createForm.data.tile_template_id
                : tile_templates[0]?.id ?? null;
        setCanvasTool((current) => {
            if (current.mode !== 'terrain') {
                return current;
            }

            if (current.templateId === templateId) {
                return current;
            }

            return { mode: 'terrain', templateId };
        });
    }, [createForm.data.tile_template_id, tile_templates]);

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
        const drafts: Record<number, TokenDraft> = {};
        liveTokens.forEach((token) => {
            const orderedConditions = orderConditions(token.status_conditions ?? []);

            drafts[token.id] = {
                name: token.name,
                x: token.x,
                y: token.y,
                color: token.color ?? '#ffffff',
                size: token.size,
                faction: token.faction,
                initiative: token.initiative ?? '',
                status_effects: token.status_effects ?? '',
                status_conditions: orderedConditions,
                status_condition_durations: syncDurationDraft(
                    token.status_condition_durations as Record<string, number | ''>,
                    orderedConditions
                ),
                hit_points: token.hit_points ?? '',
                temporary_hit_points: token.temporary_hit_points ?? '',
                max_hit_points: token.max_hit_points ?? '',
                z_index: token.z_index ?? 0,
                gm_note: token.gm_note ?? '',
            };
        });
        setTokenEdits(drafts);
    }, [liveTokens]);

    useEffect(() => {
        setHiddenTiles(map.fog.hidden_tile_ids);
    }, [map.fog.hidden_tile_ids]);

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

        const upsertToken = (incoming: MapTokenSummary) => {
            const statusConditions = incoming.status_conditions ?? [];
            const orderedConditions = orderConditions(statusConditions);

            setLiveTokens((current) =>
                orderTokens([
                    ...current.filter((existing) => existing.id !== incoming.id),
                    {
                        ...incoming,
                        status_conditions: orderedConditions,
                        status_condition_durations: normalizeConditionDurations(
                            incoming.status_condition_durations ?? {},
                            orderedConditions
                        ),
                    },
                ]),
            );
        };

        const handleTokenCreatedOrUpdated = (payload: MapTokenEventPayload) => {
            if (
                payload.token.name === undefined ||
                payload.token.x === undefined ||
                payload.token.y === undefined ||
                payload.token.faction === undefined ||
                payload.token.hidden === undefined ||
                payload.token.z_index === undefined
            ) {
                return;
            }

            upsertToken(payload.token as MapTokenSummary);
        };

        const handleTokenDeleted = (payload: MapTokenEventPayload) => {
            setLiveTokens((current) => current.filter((token) => token.id !== payload.token.id));
        };

        const handleConditionsExpired = (payload: ConditionExpirationEventPayload) => {
            if (
                payload.token?.id === undefined ||
                !Array.isArray(payload.conditions) ||
                payload.conditions.length === 0
            ) {
                return;
            }

            const order = conditionOrderRef.current ?? [];
            const sanitized = order.filter((condition) => payload.conditions.includes(condition));

            if (sanitized.length === 0) {
                return;
            }

            setConditionAlerts((current) => {
                const nextAlert: ConditionAlert = {
                    tokenId: payload.token.id,
                    tokenName: payload.token.name ?? 'Token',
                    conditions: sanitized,
                    createdAt: Date.now(),
                };

                const merged = [...current, nextAlert];

                return merged.slice(-8);
            });
        };

        channel.listen('.map-token.created', handleTokenCreatedOrUpdated);
        channel.listen('.map-token.updated', handleTokenCreatedOrUpdated);
        channel.listen('.map-token.deleted', handleTokenDeleted);
        channel.listen('.map-token.conditions-expired', handleConditionsExpired);

        return () => {
            channel.stopListening('.map-tile.created');
            channel.stopListening('.map-tile.updated');
            channel.stopListening('.map-tile.deleted');
            channel.stopListening('.map-token.created');
            channel.stopListening('.map-token.updated');
            channel.stopListening('.map-token.deleted');
            channel.stopListening('.map-token.conditions-expired');
            echo.leave(channelName);
        };
    }, [group.id, map.id]);

    const handleCanvasPlaceTerrain = useCallback(
        (placement: { q: number; r: number; templateId?: number | null; elevation?: number | null; variant?: string | null; locked?: boolean }) => {
            if (createForm.processing) {
                return;
            }

            const templateId =
                placement.templateId ??
                (typeof createForm.data.tile_template_id === 'number'
                    ? createForm.data.tile_template_id
                    : null);

            if (templateId === null) {
                return;
            }

            const variantSource =
                placement.variant !== undefined
                    ? placement.variant
                    : createForm.data.variant;
            const variantValue =
                variantSource === null || variantSource === undefined || variantSource === ''
                    ? null
                    : variantSource;

            const payload = {
                tile_template_id: templateId,
                q: placement.q,
                r: placement.r,
                elevation:
                    placement.elevation !== undefined && placement.elevation !== null
                        ? placement.elevation
                        : createForm.data.elevation ?? 0,
                variant: variantValue,
                locked:
                    placement.locked !== undefined
                        ? placement.locked
                        : createForm.data.locked ?? false,
            };

            createForm.submit('post', route('groups.maps.tiles.store', [group.id, map.id]), {
                data: payload,
                preserveScroll: true,
                onSuccess: () => {
                    setSelectedTileId(null);
                },
            });
        },
        [createForm, group.id, map.id]
    );

    const handleCanvasPlaceToken = useCallback(
        (placement: { x: number; y: number; draft?: Partial<CreateTokenForm> }) => {
            if (tokenForm.processing) {
                return;
            }

            const merged: CreateTokenForm = {
                ...tokenForm.data,
                ...placement.draft,
                x: placement.x,
                y: placement.y,
            } as CreateTokenForm;

            const resolvedName = (merged.name ?? '').trim();
            if (resolvedName === '') {
                return;
            }

            merged.name = resolvedName;

            tokenForm.submit('post', route('groups.maps.tokens.store', [group.id, map.id]), {
                data: buildTokenPayload(merged),
                preserveScroll: true,
                onSuccess: () => {
                    setSelectedTokenId(null);
                },
                onFinish: () => {
                    tokenForm.transform((original) => original);
                },
            });
        },
        [buildTokenPayload, group.id, map.id, tokenForm]
    );

    const handleCanvasTokenDrag = useCallback(
        (tokenId: number, position: { x: number; y: number }) => {
            setTokenEdits((drafts) => {
                const current = drafts[tokenId];
                if (!current) {
                    return drafts;
                }

                return {
                    ...drafts,
                    [tokenId]: {
                        ...current,
                        x: position.x,
                        y: position.y,
                    },
                };
            });

            setLiveTokens((tokensState) =>
                orderTokens(
                    tokensState.map((token) =>
                        token.id === tokenId ? { ...token, x: position.x, y: position.y } : token
                    )
                )
            );

            setUpdatingToken(tokenId);
            router.patch(
                route('groups.maps.tokens.update', [group.id, map.id, tokenId]),
                {
                    x: position.x,
                    y: position.y,
                },
                {
                    preserveScroll: true,
                    onFinish: () => setUpdatingToken(null),
                }
            );
        },
        [group.id, map.id]
    );

    const handleCanvasSelectTile = useCallback((tileId: number | null) => {
        setSelectedTileId(tileId);
    }, []);

    const handleCanvasSelectToken = useCallback((tokenId: number | null) => {
        setSelectedTokenId(tokenId);
    }, []);

    const handleAiPlanApply = useCallback(
        (result: { text: string; structured?: Record<string, unknown> | null }) => {
            const structured = (result.structured ?? {}) as Record<string, unknown>;
            const layoutNotes = Array.isArray(structured.layout_notes)
                ? structured.layout_notes.map((entry) => String(entry))
                : [];
            const explorationHooks = Array.isArray(structured.exploration_hooks)
                ? structured.exploration_hooks.map((entry) => String(entry))
                : [];
            const fogSettings =
                structured.fog_settings && typeof structured.fog_settings === 'object'
                    ? (structured.fog_settings as { mode?: string; opacity?: number | string; notes?: string })
                    : null;

            const draftTilesRaw = Array.isArray(structured.draft_tiles)
                ? structured.draft_tiles.slice(0, 24)
                : [];
            const draftTokensRaw = Array.isArray(structured.draft_tokens)
                ? structured.draft_tokens.slice(0, 24)
                : [];

            const normalizedTiles: AiDraftTile[] = draftTilesRaw
                .map((entry) => {
                    if (typeof entry !== 'object' || entry === null) {
                        return null;
                    }

                    const raw = entry as Record<string, unknown>;
                    const q = Number(raw.q);
                    const r = Number(raw.r);

                    if (!Number.isFinite(q) || !Number.isFinite(r)) {
                        return null;
                    }

                    let templateId: number | null = null;
                    if (typeof raw.template_id === 'number') {
                        templateId = raw.template_id;
                    }

                    const templateKey =
                        typeof raw.template_key === 'string'
                            ? raw.template_key
                            : typeof raw.template === 'string'
                            ? raw.template
                            : typeof raw.template_name === 'string'
                            ? raw.template_name
                            : undefined;

                    if (templateId === null && templateKey) {
                        const candidate = templateLookupByName.get(templateKey.toLowerCase());
                        if (candidate) {
                            templateId = candidate.id;
                        }
                    }

                    return {
                        q,
                        r,
                        templateId,
                        templateKey: templateKey ?? null,
                        templateName:
                            typeof raw.template_name === 'string' ? (raw.template_name as string) : null,
                        elevation:
                            raw.elevation !== undefined && raw.elevation !== null
                                ? Number(raw.elevation)
                                : null,
                        variant: typeof raw.variant === 'string' ? (raw.variant as string) : null,
                        locked: typeof raw.locked === 'boolean' ? (raw.locked as boolean) : undefined,
                    } as AiDraftTile;
                })
                .filter((tile): tile is AiDraftTile => tile !== null);

            const normalizedTokens: AiDraftToken[] = draftTokensRaw
                .map((entry) => {
                    if (typeof entry !== 'object' || entry === null) {
                        return null;
                    }

                    const raw = entry as Record<string, unknown>;
                    const x = Number(raw.x);
                    const y = Number(raw.y);

                    if (!Number.isFinite(x) || !Number.isFinite(y)) {
                        return null;
                    }

                    const faction =
                        typeof raw.faction === 'string' && ['allied', 'hostile', 'neutral', 'hazard'].includes(raw.faction)
                            ? (raw.faction as TokenFaction)
                            : null;

                    return {
                        name: typeof raw.name === 'string' ? (raw.name as string) : undefined,
                        x,
                        y,
                        color: typeof raw.color === 'string' ? (raw.color as string) : undefined,
                        size: typeof raw.size === 'string' ? (raw.size as string) : undefined,
                        faction,
                        hidden: typeof raw.hidden === 'boolean' ? (raw.hidden as boolean) : undefined,
                    } as AiDraftToken;
                })
                .filter((token): token is AiDraftToken => token !== null);

            const plan: AiPlan = {
                summary: result.text,
                layoutNotes,
                fogSettings,
                explorationHooks,
                imagePrompt: typeof structured.image_prompt === 'string' ? (structured.image_prompt as string) : null,
                draftTiles: normalizedTiles,
                draftTokens: normalizedTokens,
            };

            setAiPlan(plan);
            setAiSummary(result.text);
            setAiError(null);

            if (
                plan.layoutNotes.length === 0 &&
                plan.draftTiles.length === 0 &&
                plan.draftTokens.length === 0 &&
                plan.summary.trim() === ''
            ) {
                setAiError('The steward could not suggest any placements. Share more about the terrain or encounters you need.');
            }

            if (plan.draftTiles.length > 0) {
                const first = plan.draftTiles.find((tile) => tile.templateId !== null);
                if (first?.templateId) {
                    setCanvasTool({ mode: 'terrain', templateId: first.templateId });
                    createForm.setData('tile_template_id', first.templateId);
                }
            }
        },
        [createForm, templateLookupByName]
    );

    const handleApplyAiTile = useCallback(
        (tile: AiDraftTile) => {
            handleCanvasPlaceTerrain({
                q: tile.q,
                r: tile.r,
                templateId:
                    tile.templateId ?? (canvasTool.mode === 'terrain' ? canvasTool.templateId : null),
                elevation: tile.elevation ?? undefined,
                variant: tile.variant ?? undefined,
                locked: tile.locked,
            });
        },
        [canvasTool, handleCanvasPlaceTerrain]
    );

    const handleApplyAiToken = useCallback(
        (token: AiDraftToken) => {
            handleCanvasPlaceToken({
                x: token.x,
                y: token.y,
                draft: {
                    name: token.name ?? tokenForm.data.name,
                    color: token.color ?? tokenForm.data.color,
                    size: (token.size as CreateTokenForm['size']) ?? tokenForm.data.size,
                    faction: token.faction ?? tokenForm.data.faction,
                    hidden: token.hidden ?? tokenForm.data.hidden,
                },
            });
        },
        [handleCanvasPlaceToken, tokenForm.data]
    );

    const clearAiPlan = useCallback(() => {
        setAiPlan(null);
        setAiSummary('');
        setAiError(null);
    }, []);

    const triggerViewportReset = useCallback(() => {
        setViewportResetSignal((value) => value + 1);
    }, []);

    const handleToolSelect = useCallback(
        (mode: CanvasToolState['mode'], templateId?: number | null) => {
            if (mode === 'terrain') {
                const normalized =
                    templateId ??
                    (typeof createForm.data.tile_template_id === 'number'
                        ? createForm.data.tile_template_id
                        : tile_templates[0]?.id ?? null);

                if (normalized !== null) {
                    createForm.setData('tile_template_id', normalized);
                }

                setCanvasTool({ mode: 'terrain', templateId: normalized });
                return;
            }

            if (mode === 'token') {
                setCanvasTool({ mode: 'token' });
                return;
            }

            setCanvasTool({ mode: 'inspect' });
        },
        [createForm, tile_templates]
    );

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

    const isTileHidden = (tileId: number) => hiddenTiles.includes(tileId);

    const tokenSizeOptions = [
        { value: 'tiny', label: 'Tiny' },
        { value: 'small', label: 'Small' },
        { value: 'medium', label: 'Medium' },
        { value: 'large', label: 'Large' },
        { value: 'huge', label: 'Huge' },
        { value: 'gargantuan', label: 'Gargantuan' },
    ];

    const tokenFactionOptions: { value: TokenFaction; label: string }[] = [
        { value: 'allied', label: 'Allied' },
        { value: 'hostile', label: 'Hostile' },
        { value: 'neutral', label: 'Neutral' },
        { value: 'hazard', label: 'Hazard' },
    ];

    const tokenFactionLabels: Record<TokenFaction, string> = {
        allied: 'Allied',
        hostile: 'Hostile',
        neutral: 'Neutral',
        hazard: 'Hazard',
    };

    const tokenFactionStyles: Record<TokenFaction, string> = {
        allied: 'bg-emerald-500/15 text-emerald-200 border-emerald-500/40',
        hostile: 'bg-rose-500/15 text-rose-200 border-rose-500/40',
        neutral: 'bg-zinc-500/10 text-zinc-200 border-zinc-500/30',
        hazard: 'bg-amber-500/15 text-amber-200 border-amber-500/40',
    };

    const tokenFactionCounts = liveTokens.reduce<Record<TokenFaction, number>>((acc, token) => {
        acc[token.faction] = (acc[token.faction] ?? 0) + 1;

        return acc;
    }, {
        allied: 0,
        hostile: 0,
        neutral: 0,
        hazard: 0,
    });

    const conditionTimerGroups = useMemo<ConditionTimerGroup[]>(() => {
        if (liveTokens.length === 0) {
            return [];
        }

        const grouped = liveTokens.reduce<ConditionTimerGroup[]>((acc, token) => {
            const durations = token.status_condition_durations ?? {};
            const orderedConditions = (token.status_conditions ?? []).filter((condition) =>
                conditionOrder.includes(condition)
            );

            if (orderedConditions.length === 0) {
                return acc;
            }

            const timers: ConditionTimerEntry[] = [];

            orderedConditions.forEach((condition) => {
                const rawValue = durations[condition];

                if (typeof rawValue !== 'number' || Number.isNaN(rawValue)) {
                    return;
                }

                const normalized = Math.min(
                    Math.max(Math.round(rawValue), 0),
                    MAX_CONDITION_DURATION
                );

                if (normalized <= 0) {
                    return;
                }

                timers.push({
                    condition,
                    roundsRemaining: normalized,
                });
            });

            if (timers.length === 0) {
                return acc;
            }

            const sortedTimers = [...timers].sort((a, b) => {
                if (a.roundsRemaining !== b.roundsRemaining) {
                    return a.roundsRemaining - b.roundsRemaining;
                }

                const aIndex = conditionOrder.indexOf(a.condition);
                const bIndex = conditionOrder.indexOf(b.condition);

                return (aIndex === -1 ? Number.MAX_SAFE_INTEGER : aIndex) -
                    (bIndex === -1 ? Number.MAX_SAFE_INTEGER : bIndex);
            });

            acc.push({
                tokenId: token.id,
                tokenName: token.name,
                faction: token.faction,
                timers: sortedTimers,
            });

            return acc;
        }, []);

        return grouped.sort((a, b) => {
            const soonestA = Math.min(...a.timers.map((timer) => timer.roundsRemaining));
            const soonestB = Math.min(...b.timers.map((timer) => timer.roundsRemaining));

            if (soonestA !== soonestB) {
                return soonestA - soonestB;
            }

            const nameComparison = a.tokenName.localeCompare(b.tokenName, undefined, {
                sensitivity: 'base',
            });

            if (nameComparison !== 0) {
                return nameComparison;
            }

            return a.tokenId - b.tokenId;
        });
    }, [conditionOrder, liveTokens]);

    const totalActiveConditionCount = useMemo(
        () =>
            conditionTimerGroups.reduce(
                (total, group) => total + group.timers.length,
                0
            ),
        [conditionTimerGroups]
    );

    const filteredConditionTimerGroups = useMemo(() => {
        const normalizedQuery = timerSearchQuery.trim().toLowerCase();

        return conditionTimerGroups.filter((group) => {
            if (tokenFactionFilter !== 'all' && group.faction !== tokenFactionFilter) {
                return false;
            }

            if (
                showCriticalTimersOnly &&
                group.timers.every(
                    (timer) => timer.roundsRemaining > CRITICAL_TIMER_THRESHOLD
                )
            ) {
                return false;
            }

            if (normalizedQuery.length === 0) {
                return true;
            }

            const matchesToken = group.tokenName
                .toLowerCase()
                .includes(normalizedQuery);

            if (matchesToken) {
                return true;
            }

            return group.timers.some((timer) => {
                const label = (conditionLabelMap[timer.condition] ?? timer.condition).toLowerCase();

                return (
                    label.includes(normalizedQuery) ||
                    timer.condition.toLowerCase().includes(normalizedQuery)
                );
            });
        });
    }, [
        conditionLabelMap,
        conditionTimerGroups,
        showCriticalTimersOnly,
        timerSearchQuery,
        tokenFactionFilter,
    ]);

    const filteredActiveConditionCount = useMemo(
        () =>
            filteredConditionTimerGroups.reduce(
                (total, group) => total + group.timers.length,
                0
            ),
        [filteredConditionTimerGroups]
    );

    useEffect(() => {
        const availableKeys = new Set<string>();

        conditionTimerGroups.forEach((group) => {
            group.timers.forEach((timer) => {
                availableKeys.add(`${group.tokenId}:${timer.condition}`);
            });
        });

        setSelectedTimers((current) => {
            let changed = false;
            const next: SelectedTimerMap = {};

            Object.entries(current).forEach(([tokenIdKey, conditions]) => {
                const tokenId = Number(tokenIdKey);
                const validConditions = conditions.filter((condition) =>
                    availableKeys.has(`${tokenId}:${condition}`)
                );

                if (validConditions.length > 0) {
                    next[tokenId] = validConditions;
                }

                if (validConditions.length !== conditions.length) {
                    changed = true;
                }
            });

            if (! changed && Object.keys(next).length === Object.keys(current).length) {
                return current;
            }

            return next;
        });
    }, [conditionTimerGroups]);

    const selectedTimerEntries = useMemo(() => {
        const lookup = new Map<string, { tokenId: number; condition: string; roundsRemaining: number }>();

        conditionTimerGroups.forEach((group) => {
            group.timers.forEach((timer) => {
                lookup.set(`${group.tokenId}:${timer.condition}`, {
                    tokenId: group.tokenId,
                    condition: timer.condition,
                    roundsRemaining: timer.roundsRemaining,
                });
            });
        });

        const entries: { tokenId: number; condition: string; roundsRemaining: number }[] = [];

        Object.entries(selectedTimers).forEach(([tokenIdKey, conditions]) => {
            const tokenId = Number(tokenIdKey);

            conditions.forEach((condition) => {
                const key = `${tokenId}:${condition}`;
                const timer = lookup.get(key);

                if (timer) {
                    entries.push(timer);
                }
            });
        });

        return entries;
    }, [conditionTimerGroups, selectedTimers]);

    const selectedTimerCount = selectedTimerEntries.length;

    const selectedTokenCount = useMemo(() => {
        const unique = new Set<number>();

        selectedTimerEntries.forEach((entry) => unique.add(entry.tokenId));

        return unique.size;
    }, [selectedTimerEntries]);

    const isTimerSelected = (tokenId: number, condition: string) =>
        selectedTimers[tokenId]?.includes(condition) ?? false;

    const toggleTimerSelection = (tokenId: number, condition: string) => {
        setSelectedTimers((current) => {
            const currentForToken = current[tokenId] ?? [];
            const isSelected = currentForToken.includes(condition);
            const nextConditions = isSelected
                ? currentForToken.filter((value) => value !== condition)
                : [...currentForToken, condition];

            if (nextConditions.length === 0) {
                const { [tokenId]: _removed, ...rest } = current;

                return rest;
            }

            return {
                ...current,
                [tokenId]: nextConditions,
            };
        });
    };

    const clearSelectedTimers = () => setSelectedTimers({});

    const applyBatchAdjustments = (
        plans: BatchAdjustmentPlan[],
        summaryMessage: string
    ) => {
        if (plans.length === 0) {
            return;
        }

        const plansByToken = plans.reduce<Record<number, BatchAdjustmentPlan[]>>(
            (accumulator, plan) => {
                const bucket = accumulator[plan.tokenId] ?? [];
                bucket.push(plan);
                accumulator[plan.tokenId] = bucket;

                return accumulator;
            },
            {}
        );

        const draftSnapshots: Record<number, TokenDraft> = {};
        const tokenSnapshots: Record<number, MapTokenSummary> = {};

        Object.keys(plansByToken).forEach((tokenIdKey) => {
            const tokenId = Number(tokenIdKey);
            const draft = tokenEdits[tokenId];

            if (draft) {
                draftSnapshots[tokenId] = {
                    ...draft,
                    status_conditions: [...draft.status_conditions],
                    status_condition_durations: { ...draft.status_condition_durations },
                };
            }

            const liveToken = liveTokens.find((token) => token.id === tokenId);

            if (liveToken) {
                tokenSnapshots[tokenId] = {
                    ...liveToken,
                    status_conditions: [...liveToken.status_conditions],
                    status_condition_durations: { ...liveToken.status_condition_durations },
                };
            }
        });

        const stateUpdates: Record<
            number,
            {
                orderedConditions: string[];
                syncedDurations: Record<string, number | ''>;
                normalizedDurations: Record<string, number>;
            }
        > = {};

        setTokenEdits((drafts) => {
            const nextDrafts = { ...drafts };

            Object.entries(plansByToken).forEach(([tokenIdKey, tokenPlans]) => {
                const tokenId = Number(tokenIdKey);
                const current = nextDrafts[tokenId];

                if (! current) {
                    return;
                }

                const orderedConditions = orderConditions(current.status_conditions);
                let durationDraft = { ...current.status_condition_durations };

                tokenPlans.forEach((plan) => {
                    const rawCurrent = durationDraft[plan.condition];
                    const numericCurrent =
                        typeof rawCurrent === 'number' ? rawCurrent : Number(rawCurrent);
                    const sanitizedCurrent = Number.isNaN(numericCurrent)
                        ? 0
                        : numericCurrent;

                    let nextValue =
                        plan.type === 'delta'
                            ? sanitizedCurrent + plan.value
                            : plan.value;

                    if (nextValue <= 0) {
                        delete durationDraft[plan.condition];
                    } else {
                        const clamped = Math.min(
                            Math.max(Math.round(nextValue), 1),
                            MAX_CONDITION_DURATION
                        );
                        durationDraft[plan.condition] = clamped;
                    }
                });

                const syncedDurations = syncDurationDraft(durationDraft, orderedConditions);
                const serializedDurations = serializeConditionDurations(
                    syncedDurations,
                    orderedConditions
                );
                const normalizedDurations = normalizeConditionDurations(
                    serializedDurations ?? {},
                    orderedConditions
                );

                stateUpdates[tokenId] = {
                    orderedConditions,
                    syncedDurations,
                    normalizedDurations,
                };

                nextDrafts[tokenId] = {
                    ...current,
                    status_conditions: orderedConditions,
                    status_condition_durations: syncedDurations,
                };
            });

            return nextDrafts;
        });

        setLiveTokens((tokensState) =>
            orderTokens(
                tokensState.map((token) => {
                    const update = stateUpdates[token.id];

                    if (! update) {
                        return token;
                    }

                    return {
                        ...token,
                        status_conditions: update.orderedConditions,
                        status_condition_durations: update.normalizedDurations,
                    };
                })
            )
        );

        const payload = plans.map((plan) => ({
            token_id: plan.tokenId,
            condition: plan.condition,
            ...(plan.type === 'delta'
                ? { delta: plan.value }
                : { set_to: plan.value }),
            expected_rounds: plan.expected,
        }));

        setBatchProcessing(true);
        setPendingBatchSummary(summaryMessage);
        setBatchSummary(null);

        router.post(
            route('groups.maps.tokens.condition-timers.batch', [group.id, map.id]),
            { adjustments: payload },
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    setPendingBatchSummary(null);
                    setBatchSummary(summaryMessage);
                },
                onError: () => {
                    setPendingBatchSummary(null);
                    setBatchSummary(null);

                    setTokenEdits((drafts) => {
                        const nextDrafts = { ...drafts };

                        Object.entries(draftSnapshots).forEach(([tokenIdKey, snapshot]) => {
                            nextDrafts[Number(tokenIdKey)] = {
                                ...snapshot,
                                status_conditions: [...snapshot.status_conditions],
                                status_condition_durations: {
                                    ...snapshot.status_condition_durations,
                                },
                            };
                        });

                        return nextDrafts;
                    });

                    setLiveTokens((tokensState) =>
                        orderTokens(
                            tokensState.map((token) => {
                                const snapshot = tokenSnapshots[token.id];

                                if (! snapshot) {
                                    return token;
                                }

                                return {
                                    ...snapshot,
                                    status_conditions: [...snapshot.status_conditions],
                                    status_condition_durations: {
                                        ...snapshot.status_condition_durations,
                                    },
                                };
                            })
                        )
                    );
                },
                onFinish: () => {
                    setBatchProcessing(false);
                },
            }
        );
    };

    const handleApplyBatchDelta = (direction: 'increase' | 'decrease') => {
        if (selectedTimerEntries.length === 0) {
            return;
        }

        const parsed = Number(batchDeltaInput);
        const magnitude = Number.isNaN(parsed)
            ? 1
            : Math.min(Math.max(Math.round(Math.abs(parsed)), 1), MAX_CONDITION_DURATION);

        const delta = direction === 'increase' ? magnitude : -magnitude;

        const plans: BatchAdjustmentPlan[] = selectedTimerEntries.map((entry) => ({
            tokenId: entry.tokenId,
            condition: entry.condition,
            type: 'delta',
            value: delta,
            expected: entry.roundsRemaining,
        }));

        const timerLabel = selectedTimerEntries.length === 1 ? 'timer' : 'timers';
        const tokenLabel = selectedTokenCount === 1 ? 'token' : 'tokens';
        const scope = selectedTokenCount > 1 ? ` across ${selectedTokenCount} ${tokenLabel}` : '';
        const summary = `${
            direction === 'increase' ? 'Extended' : 'Reduced'
        } ${selectedTimerEntries.length} ${timerLabel} by ${formatRoundsRemaining(
            magnitude
        )}${scope}`;

        applyBatchAdjustments(plans, summary);
    };

    const handleApplyBatchSet = () => {
        if (selectedTimerEntries.length === 0) {
            return;
        }

        const parsed = Number(batchSetInput);

        if (Number.isNaN(parsed) || parsed <= 0) {
            return;
        }

        const target = Math.min(Math.max(Math.round(parsed), 1), MAX_CONDITION_DURATION);

        const plans: BatchAdjustmentPlan[] = selectedTimerEntries.map((entry) => ({
            tokenId: entry.tokenId,
            condition: entry.condition,
            type: 'set',
            value: target,
            expected: entry.roundsRemaining,
        }));

        const timerLabel = selectedTimerEntries.length === 1 ? 'timer' : 'timers';
        const tokenLabel = selectedTokenCount === 1 ? 'token' : 'tokens';
        const scope = selectedTokenCount > 1 ? ` across ${selectedTokenCount} ${tokenLabel}` : '';
        const summary = `Reset ${selectedTimerEntries.length} ${timerLabel} to ${formatRoundsRemaining(
            target
        )}${scope}`;

        applyBatchAdjustments(plans, summary);
    };

    const hasTimerFiltersApplied =
        tokenFactionFilter !== 'all' ||
        showCriticalTimersOnly ||
        timerSearchQuery.trim().length > 0;

    const displayedTokens =
        tokenFactionFilter === 'all'
            ? liveTokens
            : liveTokens.filter((token) => token.faction === tokenFactionFilter);

    const tokenFilterOptions: { value: 'all' | TokenFaction; label: string; count: number }[] = [
        { value: 'all', label: 'All', count: liveTokens.length },
        ...tokenFactionOptions.map((option) => ({
            value: option.value,
            label: option.label,
            count: tokenFactionCounts[option.value],
        })),
    ];

    const submitFogUpdate = (nextHidden: number[], pendingId: number, previous: number[]) => {
        const normalized = Array.from(new Set(nextHidden)).sort((a, b) => a - b);
        setHiddenTiles(normalized);
        setFogPendingTileId(pendingId);

        router.put(
            route('groups.maps.fog.update', [group.id, map.id]),
            {
                hidden_tile_ids: normalized,
            },
            {
                preserveScroll: true,
                onError: () => {
                    setHiddenTiles(previous);
                },
                onFinish: () => setFogPendingTileId(null),
            }
        );
    };

    const toggleTileVisibility = (tileId: number) => {
        const previous = [...hiddenTiles];
        const nextHidden = isTileHidden(tileId)
            ? previous.filter((id) => id !== tileId)
            : [...previous, tileId];

        submitFogUpdate(nextHidden, tileId, previous);
    };

    const revealAllTiles = () => {
        if (hiddenTiles.length === 0) {
            return;
        }

        const previous = [...hiddenTiles];
        submitFogUpdate([], -1, previous);
    };

    const handleTokenDraftChange = <K extends keyof TokenDraft>(
        tokenId: number,
        field: K,
        value: TokenDraft[K]
    ) => {
        setTokenEdits((drafts) => {
            const current = drafts[tokenId];

            if (!current) {
                return drafts;
            }

            if (field === 'status_conditions') {
                const ordered = orderConditions(value as string[]);

                return {
                    ...drafts,
                    [tokenId]: {
                        ...current,
                        status_conditions: ordered,
                        status_condition_durations: syncDurationDraft(
                            current.status_condition_durations,
                            ordered
                        ),
                    },
                };
            }

            return {
                ...drafts,
                [tokenId]: {
                    ...current,
                    [field]: value,
                },
            };
        });
    };

    const handleTokenDurationDraftChange = (
        tokenId: number,
        condition: string,
        rawValue: string
    ) => {
        setTokenEdits((drafts) => {
            const current = drafts[tokenId];

            if (!current) {
                return drafts;
            }

            const sanitized = rawValue === '' ? '' : Number(rawValue);

            const nextValue =
                sanitized === '' || Number.isNaN(sanitized)
                    ? ''
                    : Math.min(Math.max(Math.round(sanitized), 1), MAX_CONDITION_DURATION);

            return {
                ...drafts,
                [tokenId]: {
                    ...current,
                    status_condition_durations: {
                        ...current.status_condition_durations,
                        [condition]: rawValue === '' ? '' : nextValue,
                    },
                },
            };
        });
    };

    const handleAdjustConditionTimer = (
        tokenId: number,
        condition: string,
        delta: number
    ) => {
        const draft = tokenEdits[tokenId];

        if (!draft) {
            return;
        }

        const orderedConditions = orderConditions(draft.status_conditions);
        const currentValue = draft.status_condition_durations[condition];
        const numericCurrent =
            typeof currentValue === 'number' ? currentValue : Number(currentValue);
        const sanitizedCurrent = Number.isNaN(numericCurrent) ? 0 : numericCurrent;

        let nextValue = sanitizedCurrent + delta;
        const nextDurationDraft = { ...draft.status_condition_durations };

        if (nextValue <= 0) {
            delete nextDurationDraft[condition];
        } else {
            nextValue = Math.min(
                Math.max(Math.round(nextValue), 1),
                MAX_CONDITION_DURATION
            );
            nextDurationDraft[condition] = nextValue;
        }

        const syncedDurations = syncDurationDraft(nextDurationDraft, orderedConditions);
        const serializedDurations = serializeConditionDurations(
            syncedDurations,
            orderedConditions
        );
        const normalizedDurations = normalizeConditionDurations(
            serializedDurations ?? {},
            orderedConditions
        );

        setTokenEdits((drafts) => {
            const current = drafts[tokenId];

            if (!current) {
                return drafts;
            }

            return {
                ...drafts,
                [tokenId]: {
                    ...current,
                    status_conditions: orderedConditions,
                    status_condition_durations: syncedDurations,
                },
            };
        });

        setLiveTokens((tokensState) =>
            orderTokens(
                tokensState.map((token) =>
                    token.id === tokenId
                        ? {
                              ...token,
                              status_conditions: orderedConditions,
                              status_condition_durations: normalizedDurations,
                          }
                        : token,
                ),
            ),
        );

        setUpdatingToken(tokenId);
        router.patch(
            route('groups.maps.tokens.update', [group.id, map.id, tokenId]),
            {
                status_conditions: orderedConditions,
                status_condition_durations: serializedDurations ?? null,
            },
            {
                preserveScroll: true,
                onFinish: () => setUpdatingToken(null),
            },
        );
    };

    const handleClearConditionTimer = (tokenId: number, condition: string) => {
        const draft = tokenEdits[tokenId];

        if (!draft) {
            return;
        }

        const filteredConditions = draft.status_conditions.filter(
            (value) => value !== condition
        );
        const orderedConditions = orderConditions(filteredConditions);
        const nextDurationDraft = { ...draft.status_condition_durations };

        delete nextDurationDraft[condition];

        const syncedDurations = syncDurationDraft(
            nextDurationDraft,
            orderedConditions
        );
        const serializedDurations = serializeConditionDurations(
            syncedDurations,
            orderedConditions
        );
        const normalizedDurations = normalizeConditionDurations(
            serializedDurations ?? {},
            orderedConditions
        );

        setTokenEdits((drafts) => {
            const current = drafts[tokenId];

            if (!current) {
                return drafts;
            }

            return {
                ...drafts,
                [tokenId]: {
                    ...current,
                    status_conditions: orderedConditions,
                    status_condition_durations: syncedDurations,
                },
            };
        });

        setLiveTokens((tokensState) =>
            orderTokens(
                tokensState.map((token) =>
                    token.id === tokenId
                        ? {
                              ...token,
                              status_conditions: orderedConditions,
                              status_condition_durations: normalizedDurations,
                          }
                        : token
                )
            )
        );

        setUpdatingToken(tokenId);
        router.patch(
            route('groups.maps.tokens.update', [group.id, map.id, tokenId]),
            {
                status_conditions: orderedConditions,
                status_condition_durations: serializedDurations ?? null,
            },
            {
                preserveScroll: true,
                onFinish: () => setUpdatingToken(null),
            }
        );
    };

    const handleTokenUpdate = (tokenId: number) => {
        const draft = tokenEdits[tokenId];

        if (!draft) {
            return;
        }

        const orderedConditions = orderConditions(draft.status_conditions);
        const durationPayload = serializeConditionDurations(
            draft.status_condition_durations,
            orderedConditions
        );

        const payload: Record<string, unknown> = {
            name: draft.name,
            x: Number(draft.x),
            y: Number(draft.y),
            color: draft.color.trim() === '' ? null : draft.color,
            size: draft.size,
            faction: draft.faction,
            initiative: draft.initiative === '' ? null : Number(draft.initiative),
            status_effects:
                draft.status_effects.trim() === '' ? null : draft.status_effects,
            status_conditions: orderedConditions,
            status_condition_durations: durationPayload ?? null,
            hit_points: draft.hit_points === '' ? null : Number(draft.hit_points),
            temporary_hit_points:
                draft.temporary_hit_points === '' ? null : Number(draft.temporary_hit_points),
            max_hit_points:
                draft.max_hit_points === '' ? null : Number(draft.max_hit_points),
            z_index: draft.z_index === '' ? 0 : Number(draft.z_index),
            gm_note: draft.gm_note.trim() === '' ? null : draft.gm_note,
        };

        setUpdatingToken(tokenId);
        router.patch(route('groups.maps.tokens.update', [group.id, map.id, tokenId]), payload, {
            preserveScroll: true,
            onFinish: () => setUpdatingToken(null),
        });
    };

    const handleToggleTokenHidden = (token: MapTokenSummary) => {
        setUpdatingToken(token.id);
        router.patch(
            route('groups.maps.tokens.update', [group.id, map.id, token.id]),
            {
                hidden: !token.hidden,
            },
            {
                preserveScroll: true,
                onFinish: () => setUpdatingToken(null),
            }
        );
    };

    const handleRemoveToken = (token: MapTokenSummary) => {
        setRemovingToken(token.id);
        router.delete(route('groups.maps.tokens.destroy', [group.id, map.id, token.id]), {
            preserveScroll: true,
            onFinish: () => setRemovingToken(null),
        });
    };

    const handleTokenCreate = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        tokenForm
            .transform((data) => ({
                ...data,
                color: data.color.trim() === '' ? null : data.color,
                initiative: data.initiative === '' ? null : data.initiative,
                status_effects:
                    data.status_effects.trim() === '' ? null : data.status_effects,
                status_conditions: orderConditions(data.status_conditions),
                status_condition_durations:
                    serializeConditionDurations(
                        data.status_condition_durations,
                        orderConditions(data.status_conditions)
                    ) ?? null,
                hit_points: data.hit_points === '' ? null : Number(data.hit_points),
                temporary_hit_points:
                    data.temporary_hit_points === '' ? null : Number(data.temporary_hit_points),
                max_hit_points:
                    data.max_hit_points === '' ? null : Number(data.max_hit_points),
                z_index: data.z_index === '' ? 0 : data.z_index,
                gm_note: data.gm_note.trim() === '' ? null : data.gm_note,
            }))
            .post(route('groups.maps.tokens.store', [group.id, map.id]), {
                preserveScroll: true,
                onSuccess: () => {
                    tokenForm.reset();
                },
                onFinish: () => {
                    tokenForm.transform((original) => original);
                },
            });
    };

    const handleCreateConditionToggle = (condition: string) => {
        const toggled = toggleCondition(tokenForm.data.status_conditions, condition);

        tokenForm.setData('status_conditions', toggled);
        tokenForm.setData(
            'status_condition_durations',
            syncDurationDraft(tokenForm.data.status_condition_durations, toggled)
        );
    };

    const handleCreateConditionDurationChange = (condition: string, rawValue: string) => {
        const sanitized = rawValue === '' ? '' : Number(rawValue);
        const nextValue =
            sanitized === '' || Number.isNaN(sanitized)
                ? ''
                : Math.min(Math.max(Math.round(sanitized), 1), MAX_CONDITION_DURATION);

        tokenForm.setData('status_condition_durations', {
            ...tokenForm.data.status_condition_durations,
            [condition]: rawValue === '' ? '' : nextValue,
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
                                {liveTiles.length} tiles · {liveTokens.length} tokens ·{' '}
                                {map.gm_only ? 'GM only' : 'Visible to party'}
                            </p>
                            <div className="mt-3 flex flex-wrap items-center gap-3 text-sm text-zinc-400">
                                <span>Hidden tiles: {hiddenTiles.length}</span>
                                <span>Hidden tokens: {liveTokens.filter((token) => token.hidden).length}</span>
                                {hiddenTiles.length > 0 && (
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="outline"
                                        className="border-emerald-400/40 text-emerald-200 hover:text-emerald-100"
                                        disabled={fogBusy}
                                        onClick={revealAllTiles}
                                    >
                                        {fogPendingTileId === -1 ? 'Revealing…' : 'Reveal all to party'}
                                    </Button>
                                )}
                            </div>
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

                <section className="mx-auto w-full space-y-6 rounded-2xl border border-zinc-800 bg-zinc-950/70 p-6 shadow-inner shadow-black/30">
                    <div className="grid gap-6 xl:grid-cols-[260px_1fr_320px]">
                        <aside className="space-y-6">
                            <div>
                                <h2 className="text-sm font-semibold uppercase tracking-wide text-zinc-200">
                                    Canvas tools
                                </h2>
                                <div className="mt-3 flex flex-wrap gap-2">
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant={canvasTool.mode === 'inspect' ? 'secondary' : 'outline'}
                                        className={
                                            canvasTool.mode === 'inspect'
                                                ? 'bg-zinc-800 text-zinc-100 hover:bg-zinc-700'
                                                : 'border-zinc-700 text-zinc-300 hover:text-zinc-100'
                                        }
                                        onClick={() => handleToolSelect('inspect')}
                                    >
                                        Inspect
                                    </Button>
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant={canvasTool.mode === 'terrain' ? 'secondary' : 'outline'}
                                        className={
                                            canvasTool.mode === 'terrain'
                                                ? 'bg-indigo-500/20 text-indigo-100 hover:bg-indigo-500/30'
                                                : 'border-indigo-500/50 text-indigo-200 hover:text-indigo-100'
                                        }
                                        onClick={() => handleToolSelect('terrain', canvasTool.mode === 'terrain' ? canvasTool.templateId : undefined)}
                                    >
                                        Terrain brush
                                    </Button>
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant={canvasTool.mode === 'token' ? 'secondary' : 'outline'}
                                        className={
                                            canvasTool.mode === 'token'
                                                ? 'bg-amber-500/20 text-amber-100 hover:bg-amber-500/30'
                                                : 'border-amber-500/50 text-amber-200 hover:text-amber-100'
                                        }
                                        onClick={() => handleToolSelect('token')}
                                    >
                                        Token dropper
                                    </Button>
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="outline"
                                        className="border-zinc-700 text-zinc-200 hover:text-zinc-50"
                                        onClick={triggerViewportReset}
                                    >
                                        Center map
                                    </Button>
                                </div>
                            </div>

                            <div className="space-y-3">
                                <div className="flex items-center justify-between">
                                    <h3 className="text-xs font-semibold uppercase tracking-wide text-zinc-400">
                                        Terrain templates
                                    </h3>
                                    <span className="text-[11px] uppercase tracking-wide text-zinc-500">Drag or click</span>
                                </div>
                                <ul className="space-y-2">
                                    {tile_templates.length === 0 ? (
                                        <li className="rounded-lg border border-dashed border-zinc-700/60 bg-zinc-900/60 px-3 py-2 text-xs text-zinc-400">
                                            Create tile templates to start painting this map.
                                        </li>
                                    ) : (
                                        tile_templates.map((template) => {
                                            const active =
                                                canvasTool.mode === 'terrain' && canvasTool.templateId === template.id;

                                            return (
                                                <li key={template.id}>
                                                    <button
                                                        type="button"
                                                        className={`flex w-full items-center justify-between rounded-lg border px-3 py-2 text-left text-sm transition ${
                                                            active
                                                                ? 'border-indigo-500/60 bg-indigo-500/15 text-indigo-100 shadow-inner shadow-indigo-900/30'
                                                                : 'border-zinc-700/60 bg-zinc-900/60 text-zinc-200 hover:border-indigo-500/50 hover:text-indigo-100'
                                                        }`}
                                                        draggable
                                                        onDragStart={(event) => {
                                                            event.dataTransfer.setData(
                                                                'application/json',
                                                                JSON.stringify({ kind: 'tile-template', templateId: template.id })
                                                            );
                                                            event.dataTransfer.effectAllowed = 'copy';
                                                        }}
                                                        onClick={() => handleToolSelect('terrain', template.id)}
                                                    >
                                                        <div>
                                                            <p className="font-medium">{template.name}</p>
                                                            <p className="text-xs uppercase tracking-wide text-zinc-400">
                                                                {template.terrain_type}
                                                            </p>
                                                        </div>
                                                        <span
                                                            className="h-6 w-6 rounded-full border border-white/10"
                                                            style={{ backgroundColor: terrainPalette[template.id] }}
                                                        />
                                                    </button>
                                                </li>
                                            );
                                        })
                                    )}
                                </ul>
                            </div>

                            <div className="space-y-3 rounded-xl border border-zinc-800 bg-zinc-950/60 p-4">
                                <h3 className="text-xs font-semibold uppercase tracking-wide text-zinc-300">
                                    Placement defaults
                                </h3>
                                <div className="space-y-2">
                                    <Label htmlFor="placement-elevation" className="text-[11px] uppercase tracking-wide text-zinc-500">
                                        Elevation
                                    </Label>
                                    <Input
                                        id="placement-elevation"
                                        type="number"
                                        value={createForm.data.elevation}
                                        onChange={(event) => createForm.setData('elevation', Number(event.target.value))}
                                        className="h-9 border border-zinc-700 bg-zinc-950 text-sm text-zinc-100"
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="placement-variant" className="text-[11px] uppercase tracking-wide text-zinc-500">
                                        Variant JSON
                                    </Label>
                                    <Textarea
                                        id="placement-variant"
                                        value={createForm.data.variant}
                                        onChange={(event) => createForm.setData('variant', event.target.value)}
                                        rows={2}
                                        className="border border-zinc-700 bg-zinc-950 text-sm text-zinc-100"
                                        placeholder='{"resource":"gold"}'
                                    />
                                </div>
                                <label className="flex items-center gap-2 text-sm text-zinc-300">
                                    <Checkbox
                                        checked={!!createForm.data.locked}
                                        onCheckedChange={(value) => createForm.setData('locked', value === true)}
                                    />
                                    Lock tiles after placement
                                </label>
                            </div>
                        </aside>

                        <div className="rounded-xl border border-zinc-800 bg-zinc-950/60 p-2">
                            <MapCanvasBoard
                                baseLayer={baseLayer}
                                orientation={mapOrientation}
                                tiles={liveTiles}
                                tokens={liveTokens}
                                hiddenTileIds={hiddenTiles}
                                selectedTileId={selectedTileId}
                                selectedTokenId={selectedTokenId}
                                tool={canvasTool.mode === 'terrain' ? { mode: 'terrain', templateId: canvasTool.templateId } : canvasTool}
                                terrainPalette={terrainPalette}
                                onPlaceTerrain={handleCanvasPlaceTerrain}
                                onPlaceToken={handleCanvasPlaceToken}
                                onTokenDrag={handleCanvasTokenDrag}
                                onSelectTile={handleCanvasSelectTile}
                                onSelectToken={handleCanvasSelectToken}
                                onToggleFog={toggleTileVisibility}
                                highlights={aiHighlights}
                                resetSignal={viewportResetSignal}
                            />
                        </div>

                        <aside className="space-y-6">
                            <div className="space-y-4 rounded-xl border border-indigo-500/40 bg-indigo-500/10 p-4 shadow-inner shadow-indigo-900/20">
                                <AiCompanionDrawer
                                    domain="region_map"
                                    title="Summon the cartographer"
                                    description="Describe the vibe, biome, or encounter needs and the steward will draft layout notes for this map."
                                    context={aiContext}
                                    onApply={handleAiPlanApply}
                                />
                                {aiError && (
                                    <p className="text-sm text-rose-300">{aiError}</p>
                                )}
                                {aiPlan ? (
                                    <div className="space-y-3 text-sm text-indigo-100">
                                        <p className="whitespace-pre-line text-indigo-100/90">{aiSummary}</p>
                                        {aiPlan.layoutNotes.length > 0 && (
                                            <div>
                                                <h4 className="text-xs uppercase tracking-wide text-indigo-200/80">Layout notes</h4>
                                                <ul className="mt-1 space-y-1 text-indigo-200/90">
                                                    {aiPlan.layoutNotes.map((note, index) => (
                                                        <li key={`${note}-${index}`} className="flex items-start gap-2">
                                                            <span className="mt-1 h-1.5 w-1.5 rounded-full bg-indigo-300" aria-hidden="true" />
                                                            <span>{note}</span>
                                                        </li>
                                                    ))}
                                                </ul>
                                            </div>
                                        )}
                                        {aiPlan.fogSettings && (
                                            <div className="rounded-lg border border-indigo-500/30 bg-indigo-500/10 px-3 py-2 text-xs text-indigo-200">
                                                <p className="font-semibold uppercase tracking-wide">Fog guidance</p>
                                                <p>Mode: {aiPlan.fogSettings.mode ?? 'unspecified'}</p>
                                                {aiPlan.fogSettings.opacity !== undefined && (
                                                    <p>Opacity: {aiPlan.fogSettings.opacity}</p>
                                                )}
                                                {aiPlan.fogSettings.notes && <p>{aiPlan.fogSettings.notes}</p>}
                                            </div>
                                        )}
                                        {aiPlan.draftTiles.length > 0 && (
                                            <div className="space-y-2">
                                                <h4 className="text-xs uppercase tracking-wide text-indigo-200/80">
                                                    Suggested terrain placements
                                                </h4>
                                                <ul className="space-y-2">
                                                    {aiPlan.draftTiles.map((tile, index) => {
                                                        const template = tile.templateId
                                                            ? tile_templates.find((item) => item.id === tile.templateId)
                                                            : null;

                                                        return (
                                                            <li
                                                                key={`ai-tile-${tile.q}-${tile.r}-${index}`}
                                                                className="flex items-center justify-between gap-3 rounded-lg border border-indigo-500/30 bg-indigo-500/10 px-3 py-2"
                                                            >
                                                                <div>
                                                                    <p className="text-xs uppercase tracking-wide text-indigo-100">
                                                                        q {tile.q}, r {tile.r}
                                                                    </p>
                                                                    <p className="text-sm text-indigo-100/90">
                                                                        {template ? template.name : tile.templateKey ?? 'Any template'}
                                                                    </p>
                                                                </div>
                                                                <Button
                                                                    type="button"
                                                                    size="sm"
                                                                    variant="outline"
                                                                    className="border-indigo-400/50 text-xs uppercase tracking-wide text-indigo-100"
                                                                    onClick={() => handleApplyAiTile(tile)}
                                                                >
                                                                    Place
                                                                </Button>
                                                            </li>
                                                        );
                                                    })}
                                                </ul>
                                            </div>
                                        )}
                                        {aiPlan.draftTokens.length > 0 && (
                                            <div className="space-y-2">
                                                <h4 className="text-xs uppercase tracking-wide text-indigo-200/80">
                                                    Suggested entities
                                                </h4>
                                                <ul className="space-y-2">
                                                    {aiPlan.draftTokens.map((token, index) => (
                                                        <li
                                                            key={`ai-token-${token.x}-${token.y}-${index}`}
                                                            className="flex items-center justify-between gap-3 rounded-lg border border-indigo-500/30 bg-indigo-500/10 px-3 py-2"
                                                        >
                                                            <div>
                                                                <p className="text-sm text-indigo-100/90">
                                                                    {token.name ?? 'Unnamed token'}
                                                                </p>
                                                                <p className="text-xs uppercase tracking-wide text-indigo-200/70">
                                                                    x {token.x}, y {token.y}
                                                                </p>
                                                            </div>
                                                            <Button
                                                                type="button"
                                                                size="sm"
                                                                variant="outline"
                                                                className="border-amber-400/60 text-xs uppercase tracking-wide text-amber-100"
                                                                onClick={() => handleApplyAiToken(token)}
                                                            >
                                                                Drop
                                                            </Button>
                                                        </li>
                                                    ))}
                                                </ul>
                                            </div>
                                        )}
                                        <div className="flex flex-wrap gap-2">
                                            <Button
                                                type="button"
                                                size="sm"
                                                variant="ghost"
                                                className="text-xs uppercase tracking-wide text-indigo-200 hover:text-white"
                                                onClick={clearAiPlan}
                                            >
                                                Clear steward notes
                                            </Button>
                                        </div>
                                    </div>
                                ) : (
                                    <p className="text-sm text-indigo-200/80">
                                        Ask the steward to draft a region slice and apply the suggestions directly onto the board.
                                    </p>
                                )}
                            </div>

                            <div className="space-y-3 rounded-xl border border-zinc-800 bg-zinc-950/60 p-4">
                                <h3 className="text-xs font-semibold uppercase tracking-wide text-zinc-300">
                                    Selection summary
                                </h3>
                                {selectedTile ? (
                                    <div className="space-y-2 rounded-lg border border-zinc-700/60 bg-zinc-900/70 p-3">
                                        <div className="flex items-center justify-between">
                                            <p className="text-sm font-semibold text-zinc-100">{selectedTile.template.name}</p>
                                            <span className="text-xs uppercase tracking-wide text-zinc-400">
                                                q {selectedTile.q}, r {selectedTile.r}
                                            </span>
                                        </div>
                                        <p className="text-xs text-zinc-400">
                                            {selectedTile.template.terrain_type} · Elevation {selectedTile.elevation}
                                        </p>
                                        <div className="flex flex-wrap gap-2">
                                            <Button
                                                type="button"
                                                size="sm"
                                                variant="outline"
                                                className="border-zinc-600 text-xs uppercase tracking-wide text-zinc-200"
                                                onClick={() => toggleTileVisibility(selectedTile.id)}
                                            >
                                                {hiddenTiles.includes(selectedTile.id) ? 'Reveal to party' : 'Hide from party'}
                                            </Button>
                                            <Button
                                                type="button"
                                                size="sm"
                                                variant="outline"
                                                className="border-amber-500/60 text-xs uppercase tracking-wide text-amber-200"
                                                onClick={() => handleToggleLock(selectedTile)}
                                            >
                                                {selectedTile.locked ? 'Unlock' : 'Lock'}
                                            </Button>
                                        </div>
                                    </div>
                                ) : (
                                    <p className="text-sm text-zinc-400">Select a tile on the canvas to review its details.</p>
                                )}

                                {selectedToken ? (
                                    <div className="space-y-2 rounded-lg border border-zinc-700/60 bg-zinc-900/70 p-3">
                                        <div className="flex items-center justify-between">
                                            <p className="text-sm font-semibold text-zinc-100">{selectedToken.name}</p>
                                            <span className="text-xs uppercase tracking-wide text-zinc-400">
                                                x {selectedToken.x}, y {selectedToken.y}
                                            </span>
                                        </div>
                                        <p className="text-xs text-zinc-400">
                                            {tokenFactionLabels[selectedToken.faction]} · Size {selectedToken.size}
                                        </p>
                                        <div className="flex flex-wrap gap-2">
                                            <Button
                                                type="button"
                                                size="sm"
                                                variant="outline"
                                                className="border-amber-500/50 text-xs uppercase tracking-wide text-amber-200"
                                                onClick={() => handleToggleTokenHidden(selectedToken)}
                                            >
                                                {selectedToken.hidden ? 'Reveal token' : 'Hide token'}
                                            </Button>
                                            <Button
                                                type="button"
                                                size="sm"
                                                variant="ghost"
                                                className="text-xs uppercase tracking-wide text-rose-200 hover:text-rose-100"
                                                onClick={() => handleRemoveToken(selectedToken)}
                                            >
                                                Remove
                                            </Button>
                                        </div>
                                    </div>
                                ) : (
                                    <p className="text-sm text-zinc-400">Select a token to tweak faction, visibility, or remove it.</p>
                                )}
                            </div>
                        </aside>
                    </div>
                </section>

                {conditionTimerGroups.length > 0 && (
                    <section className="mx-auto max-w-4xl space-y-3 rounded-xl border border-indigo-500/40 bg-indigo-500/10 p-4 shadow-inner shadow-indigo-900/20">
                        <header className="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                            <div>
                                <h2 className="text-sm font-semibold uppercase tracking-wide text-indigo-100">
                                    Active condition timers
                                </h2>
                                <p className="text-xs text-indigo-200/80">
                                    Tracking {totalActiveConditionCount}{' '}
                                    {totalActiveConditionCount === 1 ? 'timer' : 'timers'} across{' '}
                                    {conditionTimerGroups.length}{' '}
                                    {conditionTimerGroups.length === 1 ? 'token' : 'tokens'}.
                                </p>
                                {hasTimerFiltersApplied && (
                                    <p className="text-xs text-indigo-200/70">
                                        Showing {filteredActiveConditionCount}{' '}
                                        {filteredActiveConditionCount === 1 ? 'timer' : 'timers'} across{' '}
                                        {filteredConditionTimerGroups.length}{' '}
                                        {filteredConditionTimerGroups.length === 1 ? 'token' : 'tokens'} after filters.
                                    </p>
                                )}
                            </div>
                            <div className="flex w-full flex-col gap-2 md:max-w-sm">
                                <div className="flex flex-col gap-1">
                                    <Label htmlFor="timer-search" className="sr-only">
                                        Search timers
                                    </Label>
                                    <Input
                                        id="timer-search"
                                        type="search"
                                        value={timerSearchQuery}
                                        onChange={(event) => setTimerSearchQuery(event.target.value)}
                                        placeholder="Search token or condition"
                                        className="h-9 border border-indigo-500/40 bg-zinc-950/60 text-sm text-indigo-100 placeholder:text-indigo-200/60 focus-visible:ring-indigo-400"
                                    />
                                </div>
                                <div className="flex items-center justify-between gap-3">
                                    <label
                                        htmlFor="critical-timers-toggle"
                                        className="flex items-center gap-2 text-[11px] uppercase tracking-wide text-indigo-200/80"
                                    >
                                        <Checkbox
                                            id="critical-timers-toggle"
                                            checked={showCriticalTimersOnly}
                                            onCheckedChange={(value) => setShowCriticalTimersOnly(value === true)}
                                            className="border-indigo-500/50 data-[state=checked]:border-rose-400 data-[state=checked]:bg-rose-500"
                                        />
                                        Urgent (≤ 3 rounds)
                                    </label>
                                    {(timerSearchQuery.trim().length > 0 || showCriticalTimersOnly) && (
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            className="text-xs uppercase tracking-wide text-indigo-200 hover:text-white"
                                            onClick={() => {
                                                setTimerSearchQuery('');
                                                setShowCriticalTimersOnly(false);
                                            }}
                                        >
                                            Reset filters
                                        </Button>
                                    )}
                                </div>
                            </div>
                        </header>
                        {selectedTimerCount > 0 && (
                            <div className="mt-4 space-y-3 rounded-lg border border-indigo-500/40 bg-indigo-500/10 p-4 shadow-inner shadow-indigo-900/10">
                                <div className="flex flex-wrap items-center justify-between gap-3">
                                    <p className="text-xs uppercase tracking-wide text-indigo-200/80">
                                        Selected {selectedTimerCount}{' '}
                                        {selectedTimerCount === 1 ? 'timer' : 'timers'}
                                        {selectedTokenCount > 1 && (
                                            <>
                                                {' '}across {selectedTokenCount}{' '}
                                                {selectedTokenCount === 1 ? 'token' : 'tokens'}
                                            </>
                                        )}
                                    </p>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        className="text-[11px] uppercase tracking-wide text-indigo-200 hover:text-white"
                                        onClick={clearSelectedTimers}
                                        disabled={batchProcessing}
                                    >
                                        Clear selection
                                    </Button>
                                </div>
                                <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <Label
                                            htmlFor="batch-delta"
                                            className="text-[11px] uppercase tracking-wide text-indigo-200"
                                        >
                                            Adjust by
                                        </Label>
                                        <Input
                                            id="batch-delta"
                                            type="number"
                                            min={1}
                                            max={MAX_CONDITION_DURATION}
                                            value={batchDeltaInput}
                                            onChange={(event) => setBatchDeltaInput(event.target.value)}
                                            className="h-8 w-20 border border-indigo-500/40 bg-zinc-950/70 text-xs text-indigo-100 focus-visible:ring-indigo-400"
                                            disabled={batchProcessing}
                                        />
                                        <div className="flex items-center gap-2">
                                            <Button
                                                type="button"
                                                size="sm"
                                                className="text-xs uppercase tracking-wide"
                                                onClick={() => handleApplyBatchDelta('increase')}
                                                disabled={batchProcessing}
                                            >
                                                Extend
                                            </Button>
                                            <Button
                                                type="button"
                                                size="sm"
                                                variant="outline"
                                                className="border-indigo-500/40 text-xs uppercase tracking-wide text-indigo-100"
                                                onClick={() => handleApplyBatchDelta('decrease')}
                                                disabled={batchProcessing}
                                            >
                                                Reduce
                                            </Button>
                                        </div>
                                    </div>
                                    <div className="flex flex-wrap items-center gap-2">
                                        <Label
                                            htmlFor="batch-set"
                                            className="text-[11px] uppercase tracking-wide text-indigo-200"
                                        >
                                            Reset to
                                        </Label>
                                        <Input
                                            id="batch-set"
                                            type="number"
                                            min={1}
                                            max={MAX_CONDITION_DURATION}
                                            value={batchSetInput}
                                            onChange={(event) => setBatchSetInput(event.target.value)}
                                            className="h-8 w-20 border border-indigo-500/40 bg-zinc-950/70 text-xs text-indigo-100 focus-visible:ring-indigo-400"
                                            disabled={batchProcessing}
                                        />
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant="secondary"
                                            className="text-xs uppercase tracking-wide"
                                            onClick={handleApplyBatchSet}
                                            disabled={batchProcessing || batchSetInput.trim() === ''}
                                        >
                                            Apply
                                        </Button>
                                    </div>
                                </div>
                                {pendingBatchSummary && (
                                    <p className="text-[11px] uppercase tracking-wide text-indigo-200/80">
                                        Applying updates…
                                    </p>
                                )}
                                {batchSummary && !pendingBatchSummary && (
                                    <p className="text-[11px] uppercase tracking-wide text-indigo-100">
                                        {batchSummary}
                                    </p>
                                )}
                            </div>
                        )}
                        {selectedTimerCount === 0 && batchSummary && !pendingBatchSummary && (
                            <div className="mt-4 rounded-lg border border-indigo-500/30 bg-indigo-500/10 px-3 py-2 text-xs text-indigo-200">
                                {batchSummary}
                            </div>
                        )}
                        {filteredConditionTimerGroups.length === 0 ? (
                            <p className="rounded-lg border border-indigo-500/30 bg-indigo-500/5 px-4 py-3 text-sm text-indigo-200">
                                No timers match the current filters. Adjust your search or urgency focus to bring them back into view.
                            </p>
                        ) : (
                            <ul className="space-y-3">
                                {filteredConditionTimerGroups.map((group) => {
                                    const soonest = Math.min(
                                        ...group.timers.map((timer) => timer.roundsRemaining)
                                    );
                                    const factionBadgeClass = tokenFactionStyles[group.faction];

                                    return (
                                        <li
                                            key={group.tokenId}
                                            className="rounded-lg border border-indigo-500/30 bg-zinc-950/70 px-4 py-3 shadow-inner shadow-indigo-900/10"
                                        >
                                            <div className="flex flex-wrap items-center justify-between gap-3">
                                                <div>
                                                    <p className="text-sm font-semibold text-zinc-100">
                                                        {group.tokenName}
                                                    </p>
                                                    <div className="mt-1 flex flex-wrap items-center gap-2 text-xs uppercase tracking-wide text-zinc-400">
                                                        <span
                                                            className={`rounded-full border px-2 py-0.5 text-[11px] ${factionBadgeClass}`}
                                                        >
                                                            {tokenFactionLabels[group.faction]}
                                                        </span>
                                                        <span className="text-zinc-500">
                                                            Soonest ends in{' '}
                                                            <span className={getRoundsAccentClass(soonest)}>
                                                                {formatRoundsRemaining(soonest)}
                                                            </span>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            <ul className="mt-3 flex flex-wrap gap-2">
                                                {group.timers.map((timer) => {
                                                    const conditionLabel =
                                                        conditionLabelMap[timer.condition] ?? timer.condition;
                                                    const selected = isTimerSelected(
                                                        group.tokenId,
                                                        timer.condition
                                                    );
                                                    const selectionClasses = selected
                                                        ? 'border-indigo-400/60 bg-indigo-500/20 shadow-inner shadow-indigo-900/20'
                                                        : 'border-indigo-500/30 bg-indigo-500/10';

                                                    return (
                                                        <li
                                                            key={`${group.tokenId}-${timer.condition}`}
                                                            className={`flex items-center gap-3 rounded-md border px-2 py-1 transition ${selectionClasses}`}
                                                        >
                                                            <Checkbox
                                                                checked={selected}
                                                                onCheckedChange={() =>
                                                                    toggleTimerSelection(
                                                                        group.tokenId,
                                                                        timer.condition
                                                                    )
                                                                }
                                                                aria-label={`Select ${conditionLabel} timer`}
                                                                disabled={batchProcessing}
                                                                className="h-4 w-4 border-indigo-500/60 data-[state=checked]:border-indigo-400 data-[state=checked]:bg-indigo-500"
                                                            />
                                                            <div className="flex flex-1 flex-wrap items-center justify-between gap-2">
                                                                <span className="text-xs font-medium text-indigo-100">
                                                                    {conditionLabel}
                                                                </span>
                                                                <div className="flex items-center gap-1.5">
                                                                    <Button
                                                                        type="button"
                                                                        size="icon"
                                                                        variant="ghost"
                                                                        className="h-7 w-7 rounded-full border border-indigo-500/30 bg-indigo-500/10 text-indigo-100 hover:bg-indigo-500/20 hover:text-white"
                                                                        disabled={
                                                                            batchProcessing ||
                                                                            updatingToken === group.tokenId
                                                                        }
                                                                        onClick={() =>
                                                                            handleAdjustConditionTimer(
                                                                                group.tokenId,
                                                                                timer.condition,
                                                                                -1,
                                                                        )
                                                                    }
                                                                >
                                                                    <Minus className="h-3 w-3" aria-hidden="true" />
                                                                    <span className="sr-only">
                                                                        Decrease {conditionLabel} timer
                                                                    </span>
                                                                </Button>
                                                                <span
                                                                    className={`text-xs font-semibold ${getRoundsAccentClass(timer.roundsRemaining)}`}
                                                                >
                                                                    {formatRoundsRemaining(timer.roundsRemaining)}
                                                                </span>
                                                                    <Button
                                                                        type="button"
                                                                        size="icon"
                                                                        variant="ghost"
                                                                        className="h-7 w-7 rounded-full border border-indigo-500/30 bg-indigo-500/10 text-indigo-100 hover:bg-indigo-500/20 hover:text-white"
                                                                        disabled={
                                                                            batchProcessing ||
                                                                            updatingToken === group.tokenId
                                                                        }
                                                                        onClick={() =>
                                                                            handleAdjustConditionTimer(
                                                                                group.tokenId,
                                                                                timer.condition,
                                                                                1,
                                                                        )
                                                                    }
                                                                >
                                                                    <Plus className="h-3 w-3" aria-hidden="true" />
                                                                    <span className="sr-only">
                                                                        Increase {conditionLabel} timer
                                                                    </span>
                                                                </Button>
                                                                    <Button
                                                                        type="button"
                                                                        size="icon"
                                                                        variant="ghost"
                                                                        className="h-7 w-7 rounded-full border border-rose-500/40 bg-transparent text-rose-200 hover:bg-rose-500/20 hover:text-white"
                                                                        disabled={
                                                                            batchProcessing ||
                                                                            updatingToken === group.tokenId
                                                                        }
                                                                        onClick={() =>
                                                                            handleClearConditionTimer(
                                                                                group.tokenId,
                                                                                timer.condition,
                                                                            )
                                                                        }
                                                                    >
                                                                        <X className="h-3 w-3" aria-hidden="true" />
                                                                        <span className="sr-only">
                                                                            Clear {conditionLabel} timer
                                                                        </span>
                                                                    </Button>
                                                                </div>
                                                            </div>
                                                        </li>
                                                    );
                                                })}
                                            </ul>
                                        </li>
                                    );
                                })}
                            </ul>
                        )}
                    </section>
                )}

                {conditionAlerts.length > 0 && (
                    <section className="mx-auto max-w-4xl space-y-3 rounded-xl border border-amber-500/40 bg-amber-500/10 p-4 shadow-inner shadow-amber-900/30">
                        <header className="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h2 className="text-sm font-semibold uppercase tracking-wide text-amber-100">
                                    Conditions cleared
                                </h2>
                                <p className="text-xs text-amber-200/80">
                                    Timers expired for these tokens during the latest region turn.
                                </p>
                            </div>
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                className="text-xs uppercase tracking-wide text-amber-200 hover:bg-amber-500/20 hover:text-amber-50"
                                onClick={clearConditionAlerts}
                            >
                                Dismiss all
                            </Button>
                        </header>
                        <ul className="space-y-2">
                            {conditionAlerts.map((alert) => (
                                <li
                                    key={`${alert.tokenId}-${alert.createdAt}`}
                                    className="flex items-start justify-between gap-4 rounded-lg border border-amber-500/30 bg-amber-500/15 px-3 py-2 text-amber-50"
                                >
                                    <div>
                                        <p className="text-sm font-semibold leading-5 text-amber-100">
                                            {alert.tokenName}
                                        </p>
                                        <p className="text-xs uppercase tracking-wide text-amber-200">
                                            {alert.conditions
                                                .map((condition) => conditionLabelMap[condition] ?? condition)
                                                .join(', ')}
                                        </p>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <span className="text-[11px] uppercase tracking-wide text-amber-200/80">
                                            Cleared
                                        </span>
                                        <Button
                                            type="button"
                                            size="icon"
                                            variant="ghost"
                                            className="h-6 w-6 rounded-full text-amber-200 hover:bg-amber-500/20 hover:text-amber-50"
                                            onClick={() => dismissConditionAlert(alert.createdAt)}
                                        >
                                            <span aria-hidden>×</span>
                                            <span className="sr-only">Dismiss alert</span>
                                        </Button>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    </section>
                )}

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

                <section className="mx-auto max-w-4xl rounded-xl border border-zinc-800 bg-zinc-950/60 p-6 shadow-inner shadow-black/30">
                    <header className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 className="text-lg font-semibold text-zinc-100">Drop a token</h2>
                            <p className="text-sm text-zinc-400">
                                Mark creature, NPC, and prop positions with optional GM-only notes.
                            </p>
                        </div>
                    </header>

                    <form onSubmit={handleTokenCreate} className="mt-4 grid gap-4 md:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="token-name">Name</Label>
                            <Input
                                id="token-name"
                                value={tokenForm.data.name}
                                onChange={(event) => tokenForm.setData('name', event.target.value)}
                                disabled={tokenForm.processing}
                            />
                            {tokenForm.errors.name && <p className="text-sm text-rose-400">{tokenForm.errors.name}</p>}
                        </div>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="token-x">X</Label>
                                <Input
                                    id="token-x"
                                    type="number"
                                    value={tokenForm.data.x}
                                    onChange={(event) => tokenForm.setData('x', Number(event.target.value))}
                                    disabled={tokenForm.processing}
                                />
                                {tokenForm.errors.x && <p className="text-sm text-rose-400">{tokenForm.errors.x}</p>}
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="token-y">Y</Label>
                                <Input
                                    id="token-y"
                                    type="number"
                                    value={tokenForm.data.y}
                                    onChange={(event) => tokenForm.setData('y', Number(event.target.value))}
                                    disabled={tokenForm.processing}
                                />
                                {tokenForm.errors.y && <p className="text-sm text-rose-400">{tokenForm.errors.y}</p>}
                            </div>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="token-color">Token color</Label>
                            <Input
                                id="token-color"
                                type="color"
                                value={tokenForm.data.color}
                                onChange={(event) => tokenForm.setData('color', event.target.value)}
                                disabled={tokenForm.processing}
                                className="h-10 w-20 cursor-pointer rounded-md border border-zinc-700 bg-zinc-950"
                            />
                            {tokenForm.errors.color && <p className="text-sm text-rose-400">{tokenForm.errors.color}</p>}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="token-size">Size</Label>
                            <select
                                id="token-size"
                                value={tokenForm.data.size}
                                onChange={(event) => tokenForm.setData('size', event.target.value)}
                                className="w-full rounded-md border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-zinc-100 focus:border-indigo-500 focus:outline-none focus:ring-0"
                                disabled={tokenForm.processing}
                            >
                                {tokenSizeOptions.map((option) => (
                                    <option key={option.value} value={option.value}>
                                        {option.label}
                                    </option>
                                ))}
                            </select>
                            {tokenForm.errors.size && <p className="text-sm text-rose-400">{tokenForm.errors.size}</p>}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="token-faction">Faction</Label>
                            <select
                                id="token-faction"
                                value={tokenForm.data.faction}
                                onChange={(event) =>
                                    tokenForm.setData('faction', event.target.value as TokenFaction)
                                }
                                className="w-full rounded-md border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-zinc-100 focus:border-indigo-500 focus:outline-none focus:ring-0"
                                disabled={tokenForm.processing}
                            >
                                {tokenFactionOptions.map((option) => (
                                    <option key={option.value} value={option.value}>
                                        {option.label}
                                    </option>
                                ))}
                            </select>
                            {tokenForm.errors.faction && (
                                <p className="text-sm text-rose-400">{tokenForm.errors.faction}</p>
                            )}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="token-initiative">Initiative (optional)</Label>
                            <Input
                                id="token-initiative"
                                type="number"
                                value={tokenForm.data.initiative === '' ? '' : tokenForm.data.initiative}
                                onChange={(event) =>
                                    tokenForm.setData(
                                        'initiative',
                                        event.target.value === '' ? '' : Number(event.target.value)
                                    )
                                }
                                disabled={tokenForm.processing}
                            />
                            {tokenForm.errors.initiative && (
                                <p className="text-sm text-rose-400">{tokenForm.errors.initiative}</p>
                            )}
                        </div>
                        <div className="grid gap-4 sm:grid-cols-3 md:col-span-2">
                            <div className="space-y-2">
                                <Label htmlFor="token-hit-points">Current HP (optional)</Label>
                                <Input
                                    id="token-hit-points"
                                    type="number"
                                    value={tokenForm.data.hit_points === '' ? '' : tokenForm.data.hit_points}
                                    onChange={(event) =>
                                        tokenForm.setData(
                                            'hit_points',
                                            event.target.value === '' ? '' : Number(event.target.value)
                                        )
                                    }
                                    disabled={tokenForm.processing}
                                />
                                {tokenForm.errors.hit_points && (
                                    <p className="text-sm text-rose-400">{tokenForm.errors.hit_points}</p>
                                )}
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="token-max-hit-points">Max HP (optional)</Label>
                                <Input
                                    id="token-max-hit-points"
                                    type="number"
                                    value={tokenForm.data.max_hit_points === '' ? '' : tokenForm.data.max_hit_points}
                                    onChange={(event) =>
                                        tokenForm.setData(
                                            'max_hit_points',
                                            event.target.value === '' ? '' : Number(event.target.value)
                                        )
                                    }
                                    disabled={tokenForm.processing}
                                />
                                {tokenForm.errors.max_hit_points && (
                                    <p className="text-sm text-rose-400">{tokenForm.errors.max_hit_points}</p>
                                )}
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="token-temp-hit-points">Temp HP (optional)</Label>
                                <Input
                                    id="token-temp-hit-points"
                                    type="number"
                                    value={
                                        tokenForm.data.temporary_hit_points === ''
                                            ? ''
                                            : tokenForm.data.temporary_hit_points
                                    }
                                    onChange={(event) =>
                                        tokenForm.setData(
                                            'temporary_hit_points',
                                            event.target.value === '' ? '' : Number(event.target.value)
                                        )
                                    }
                                    disabled={tokenForm.processing}
                                />
                                {tokenForm.errors.temporary_hit_points && (
                                    <p className="text-sm text-rose-400">{tokenForm.errors.temporary_hit_points}</p>
                                )}
                            </div>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="token-z-index">Layer priority</Label>
                            <Input
                                id="token-z-index"
                                type="number"
                                value={tokenForm.data.z_index === '' ? '' : tokenForm.data.z_index}
                                onChange={(event) =>
                                    tokenForm.setData(
                                        'z_index',
                                        event.target.value === '' ? '' : Number(event.target.value)
                                    )
                                }
                                disabled={tokenForm.processing}
                            />
                            <p className="text-xs text-zinc-500">
                                Higher values sit on top when initiative ties.
                            </p>
                            {tokenForm.errors.z_index && (
                                <p className="text-sm text-rose-400">{tokenForm.errors.z_index}</p>
                            )}
                        </div>
                        <div className="space-y-2 md:col-span-2">
                            <span className="text-sm font-medium text-zinc-200">Condition presets</span>
                            <div className="flex flex-wrap gap-2">
                                {token_conditions.map((option) => {
                                    const active = tokenForm.data.status_conditions.includes(option.value);

                                    return (
                                        <Button
                                            key={option.value}
                                            type="button"
                                            size="sm"
                                            variant="outline"
                                            className={`rounded-full border px-3 py-1 text-xs uppercase tracking-wide transition-colors ${
                                                active
                                                    ? 'border-amber-500/60 bg-amber-500/10 text-amber-200 hover:bg-amber-500/20'
                                                    : 'border-zinc-700 text-zinc-200 hover:border-amber-400/40 hover:text-amber-100'
                                            }`}
                                            onClick={() => handleCreateConditionToggle(option.value)}
                                            disabled={tokenForm.processing}
                                        >
                                            {option.label}
                                        </Button>
                                    );
                                })}
                            </div>
                            {statusConditionsError && (
                                <p className="text-sm text-rose-400">{statusConditionsError}</p>
                            )}
                        </div>
                        {tokenForm.data.status_conditions.length > 0 && (
                            <div className="space-y-2 md:col-span-2">
                                <span className="text-xs uppercase tracking-wide text-zinc-500">
                                    Condition timers (rounds)
                                </span>
                                <div className="grid gap-3 sm:grid-cols-2 md:grid-cols-3">
                                    {tokenForm.data.status_conditions.map((condition) => {
                                        const value = tokenForm.data.status_condition_durations[condition] ?? '';
                                        const error =
                                            (tokenForm.errors as Record<string, string>)[
                                                `status_condition_durations.${condition}`
                                            ] ?? null;

                                        return (
                                            <div key={condition} className="space-y-1">
                                                <Label
                                                    htmlFor={`token-condition-duration-${condition}`}
                                                    className="text-xs uppercase tracking-wide text-zinc-500"
                                                >
                                                    {conditionLabelMap[condition]}
                                                </Label>
                                                <Input
                                                    id={`token-condition-duration-${condition}`}
                                                    type="number"
                                                    min={1}
                                                    max={MAX_CONDITION_DURATION}
                                                    value={value === '' ? '' : value}
                                                    onChange={(event) =>
                                                        handleCreateConditionDurationChange(
                                                            condition,
                                                            event.target.value
                                                        )
                                                    }
                                                    disabled={tokenForm.processing}
                                                />
                                                <p className="text-[11px] text-zinc-500">
                                                    Leave blank to track manually.
                                                </p>
                                                {error && (
                                                    <p className="text-xs text-rose-400">{error}</p>
                                                )}
                                            </div>
                                        );
                                    })}
                                </div>
                            </div>
                        )}
                        <div className="space-y-2 md:col-span-2">
                            <Label htmlFor="token-status">Status effects (optional)</Label>
                            <Textarea
                                id="token-status"
                                value={tokenForm.data.status_effects}
                                onChange={(event) => tokenForm.setData('status_effects', event.target.value)}
                                disabled={tokenForm.processing}
                                placeholder="Blessed, concentrating on web"
                            />
                            {tokenForm.errors.status_effects && (
                                <p className="text-sm text-rose-400">{tokenForm.errors.status_effects}</p>
                            )}
                        </div>
                        <div className="space-y-2 md:col-span-2">
                            <Label htmlFor="token-note">GM note (optional)</Label>
                            <Textarea
                                id="token-note"
                                value={tokenForm.data.gm_note}
                                onChange={(event) => tokenForm.setData('gm_note', event.target.value)}
                                disabled={tokenForm.processing}
                                placeholder="Initiative +2, carrying lantern"
                            />
                            {tokenForm.errors.gm_note && <p className="text-sm text-rose-400">{tokenForm.errors.gm_note}</p>}
                        </div>
                        <div className="flex items-center gap-2 md:col-span-2">
                            <Checkbox
                                id="token-hidden"
                                checked={tokenForm.data.hidden}
                                onChange={(event) => tokenForm.setData('hidden', event.target.checked)}
                                disabled={tokenForm.processing}
                            />
                            <Label htmlFor="token-hidden" className="text-sm text-zinc-300">
                                Start hidden from players
                            </Label>
                        </div>
                        <div className="md:col-span-2 flex items-center gap-3">
                            <Button type="submit" disabled={tokenForm.processing}>
                                Place token
                            </Button>
                            <Button
                                type="button"
                                variant="ghost"
                                disabled={tokenForm.processing}
                                onClick={() => tokenForm.reset()}
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

                                const hidden = isTileHidden(tile.id);

                                return (
                                    <article
                                        key={tile.id}
                                        className={`flex flex-col gap-4 rounded-lg border border-zinc-800 bg-zinc-950/50 p-4 transition-colors md:flex-row md:items-start md:justify-between ${hidden ? 'border-sky-600/40 bg-sky-950/40' : ''}`}
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
                                                {hidden && (
                                                    <span className="rounded-full bg-sky-500/15 px-2 py-0.5 text-[11px] uppercase tracking-wide text-sky-200">
                                                        Hidden from players
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
                                                className={hidden
                                                    ? 'border-emerald-400/40 text-emerald-200 hover:text-emerald-100'
                                                    : 'border-slate-700 text-slate-200 hover:text-slate-100'}
                                                disabled={fogBusy}
                                                onClick={() => toggleTileVisibility(tile.id)}
                                            >
                                                {fogPendingTileId === tile.id
                                                    ? 'Updating…'
                                                    : hidden
                                                    ? 'Reveal to party'
                                                    : 'Hide from party'}
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

                <section className="mx-auto max-w-4xl space-y-4">
                    <header className="space-y-3">
                        <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <h2 className="text-lg font-semibold text-zinc-100">Active tokens</h2>
                            <span className="text-xs uppercase tracking-wide text-zinc-500">
                                {displayedTokens.length} shown · {liveTokens.length} total
                            </span>
                        </div>
                        <div className="flex flex-wrap items-center gap-2">
                            {tokenFilterOptions.map((option) => {
                                const isActive = tokenFactionFilter === option.value;

                                return (
                                    <Button
                                        key={option.value}
                                        type="button"
                                        size="sm"
                                        variant="outline"
                                        className={`border px-3 py-1 text-xs uppercase tracking-wide transition ${
                                            isActive
                                                ? 'border-indigo-400 bg-indigo-500/20 text-indigo-100 hover:bg-indigo-500/30'
                                                : 'border-zinc-700 text-zinc-300 hover:border-zinc-500 hover:text-zinc-100'
                                        }`}
                                        onClick={() => setTokenFactionFilter(option.value)}
                                    >
                                        {option.label}
                                        <span className="ml-1 text-[11px] lowercase text-zinc-400">{option.count}</span>
                                    </Button>
                                );
                            })}
                        </div>
                    </header>

                    {liveTokens.length === 0 ? (
                        <p className="rounded-lg border border-dashed border-zinc-800 bg-zinc-950/40 p-4 text-sm text-zinc-400">
                            No tokens placed yet. Use the form above to drop heroes, monsters, and props onto the scene.
                        </p>
                    ) : displayedTokens.length === 0 ? (
                        <p className="rounded-lg border border-dashed border-indigo-500/40 bg-indigo-950/20 p-4 text-sm text-indigo-200">
                            No tokens match this faction filter. Switch to “All” or another faction to resume editing.
                        </p>
                    ) : (
                        <div className="space-y-4">
                            {displayedTokens.map((token) => {
                                const draft = tokenEdits[token.id] ?? {
                                    name: token.name,
                                    x: token.x,
                                    y: token.y,
                                    color: token.color ?? '#ffffff',
                                    size: token.size,
                                    faction: token.faction,
                                    initiative: token.initiative ?? '',
                                    status_effects: token.status_effects ?? '',
                                    status_conditions: orderConditions(token.status_conditions ?? []),
                                    status_condition_durations: syncDurationDraft(
                                        token.status_condition_durations as Record<string, number | ''>,
                                        orderConditions(token.status_conditions ?? [])
                                    ),
                                    z_index: token.z_index ?? 0,
                                    gm_note: token.gm_note ?? '',
                                };

                                const factionBadgeClass = tokenFactionStyles[token.faction];

                                return (
                                    <article
                                        key={token.id}
                                        className={`flex flex-col gap-4 rounded-lg border border-zinc-800 bg-zinc-950/50 p-4 transition-colors md:flex-row md:items-start md:justify-between ${token.hidden ? 'border-sky-600/40 bg-sky-950/40' : ''}`}
                                    >
                                        <div className="space-y-3">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <h3 className="text-base font-semibold text-zinc-100">{token.name}</h3>
                                                <span className="rounded-full bg-zinc-800 px-2 py-0.5 text-[11px] uppercase tracking-wide text-zinc-400">
                                                    x {token.x}, y {token.y}
                                                </span>
                                                <span className="rounded-full bg-indigo-500/20 px-2 py-0.5 text-[11px] uppercase tracking-wide text-indigo-200">
                                                    {token.size}
                                                </span>
                                                <span
                                                    className={`rounded-full border px-2 py-0.5 text-[11px] uppercase tracking-wide ${factionBadgeClass}`}
                                                >
                                                    {tokenFactionLabels[token.faction]}
                                                </span>
                                                <span className="rounded-full bg-indigo-500/10 px-2 py-0.5 text-[11px] uppercase tracking-wide text-indigo-200">
                                                    Layer {token.z_index}
                                                </span>
                                                {token.initiative !== null && (
                                                    <span className="rounded-full bg-emerald-500/15 px-2 py-0.5 text-[11px] uppercase tracking-wide text-emerald-200">
                                                        Initiative {token.initiative}
                                                    </span>
                                                )}
                                                {(token.hit_points !== null || token.max_hit_points !== null) && (
                                                    <span className="rounded-full bg-rose-500/15 px-2 py-0.5 text-[11px] uppercase tracking-wide text-rose-200">
                                                        HP {token.hit_points ?? '—'}
                                                        {token.max_hit_points !== null ? ` / ${token.max_hit_points}` : ''}
                                                    </span>
                                                )}
                                                {token.temporary_hit_points !== null && token.temporary_hit_points > 0 && (
                                                    <span className="rounded-full bg-amber-500/15 px-2 py-0.5 text-[11px] uppercase tracking-wide text-amber-200">
                                                        Temp {token.temporary_hit_points}
                                                    </span>
                                                )}
                                            {token.hidden && (
                                                <span className="rounded-full bg-sky-500/15 px-2 py-0.5 text-[11px] uppercase tracking-wide text-sky-200">
                                                    Hidden from players
                                                </span>
                                            )}
                                        </div>
                                        {token.status_conditions.length > 0 && (
                                            <div className="flex flex-wrap gap-2">
                                                {token.status_conditions.map((condition) => {
                                                    const duration = token.status_condition_durations?.[condition];

                                                    return (
                                                        <span
                                                            key={condition}
                                                            className="rounded-full bg-amber-500/15 px-2 py-0.5 text-[11px] uppercase tracking-wide text-amber-200"
                                                        >
                                                            {conditionLabelMap[condition] ?? condition}
                                                            {duration !== undefined && (
                                                                <span className="ml-1 text-[10px] lowercase text-amber-100/80">
                                                                    ({duration}r)
                                                                </span>
                                                            )}
                                                        </span>
                                                    );
                                                })}
                                            </div>
                                        )}
                                        {token.status_effects && (
                                            <p className="text-sm text-amber-200">
                                                {token.status_effects}
                                            </p>
                                        )}
                                            <div className="grid gap-3 sm:grid-cols-2">
                                                <div className="space-y-1">
                                                    <Label htmlFor={`token-name-${token.id}`} className="text-xs uppercase tracking-wide text-zinc-500">
                                                        Name
                                                    </Label>
                                                    <Input
                                                        id={`token-name-${token.id}`}
                                                        value={draft.name}
                                                        onChange={(event) =>
                                                            handleTokenDraftChange(token.id, 'name', event.target.value)
                                                        }
                                                        disabled={updatingToken === token.id}
                                                    />
                                                </div>
                                                <div className="space-y-1">
                                                    <Label htmlFor={`token-color-${token.id}`} className="text-xs uppercase tracking-wide text-zinc-500">
                                                        Color
                                                    </Label>
                                                    <Input
                                                        id={`token-color-${token.id}`}
                                                        type="color"
                                                        value={draft.color}
                                                        onChange={(event) =>
                                                            handleTokenDraftChange(token.id, 'color', event.target.value)
                                                        }
                                                        disabled={updatingToken === token.id}
                                                        className="h-10 w-20 cursor-pointer rounded-md border border-zinc-700 bg-zinc-950"
                                                    />
                                                </div>
                                                <div className="space-y-1">
                                                    <Label htmlFor={`token-x-${token.id}`} className="text-xs uppercase tracking-wide text-zinc-500">
                                                        X coordinate
                                                    </Label>
                                                    <Input
                                                        id={`token-x-${token.id}`}
                                                        type="number"
                                                        value={draft.x}
                                                        onChange={(event) =>
                                                            handleTokenDraftChange(
                                                                token.id,
                                                                'x',
                                                                event.target.value === '' ? '' : Number(event.target.value)
                                                            )
                                                        }
                                                        disabled={updatingToken === token.id}
                                                    />
                                                </div>
                                                <div className="space-y-1">
                                                    <Label htmlFor={`token-y-${token.id}`} className="text-xs uppercase tracking-wide text-zinc-500">
                                                        Y coordinate
                                                    </Label>
                                                    <Input
                                                        id={`token-y-${token.id}`}
                                                        type="number"
                                                        value={draft.y}
                                                        onChange={(event) =>
                                                            handleTokenDraftChange(
                                                                token.id,
                                                                'y',
                                                                event.target.value === '' ? '' : Number(event.target.value)
                                                            )
                                                        }
                                                        disabled={updatingToken === token.id}
                                                    />
                                                </div>
                                                <div className="space-y-1">
                                                    <Label htmlFor={`token-size-${token.id}`} className="text-xs uppercase tracking-wide text-zinc-500">
                                                        Size
                                                    </Label>
                                                    <select
                                                        id={`token-size-${token.id}`}
                                                        value={draft.size}
                                                        onChange={(event) =>
                                                            handleTokenDraftChange(token.id, 'size', event.target.value)
                                                        }
                                                        className="w-full rounded-md border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-zinc-100 focus:border-indigo-500 focus:outline-none focus:ring-0"
                                                        disabled={updatingToken === token.id}
                                                    >
                                                        {tokenSizeOptions.map((option) => (
                                                            <option key={option.value} value={option.value}>
                                                                {option.label}
                                                            </option>
                                                        ))}
                                                    </select>
                                                </div>
                                                <div className="space-y-1">
                                                    <Label htmlFor={`token-faction-${token.id}`} className="text-xs uppercase tracking-wide text-zinc-500">
                                                        Faction
                                                    </Label>
                                                    <select
                                                        id={`token-faction-${token.id}`}
                                                        value={draft.faction}
                                                        onChange={(event) =>
                                                            handleTokenDraftChange(
                                                                token.id,
                                                                'faction',
                                                                event.target.value as TokenFaction
                                                            )
                                                        }
                                                        className="w-full rounded-md border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-zinc-100 focus:border-indigo-500 focus:outline-none focus:ring-0"
                                                        disabled={updatingToken === token.id}
                                                    >
                                                        {tokenFactionOptions.map((option) => (
                                                            <option key={option.value} value={option.value}>
                                                                {option.label}
                                                            </option>
                                                        ))}
                                                    </select>
                                                </div>
                                                <div className="space-y-1">
                                                    <Label htmlFor={`token-initiative-${token.id}`} className="text-xs uppercase tracking-wide text-zinc-500">
                                                        Initiative (optional)
                                                    </Label>
                                                    <Input
                                                        id={`token-initiative-${token.id}`}
                                                        type="number"
                                                        value={draft.initiative === '' ? '' : draft.initiative}
                                                        onChange={(event) =>
                                                            handleTokenDraftChange(
                                                                token.id,
                                                                'initiative',
                                                                event.target.value === '' ? '' : Number(event.target.value)
                                                            )
                                                        }
                                                        disabled={updatingToken === token.id}
                                                    />
                                                </div>
                                                <div className="grid gap-3 sm:col-span-2 sm:grid-cols-3">
                                                    <div className="space-y-1">
                                                        <Label htmlFor={`token-hit-points-${token.id}`} className="text-xs uppercase tracking-wide text-zinc-500">
                                                            Current HP (optional)
                                                        </Label>
                                                        <Input
                                                            id={`token-hit-points-${token.id}`}
                                                            type="number"
                                                            value={draft.hit_points === '' ? '' : draft.hit_points}
                                                            onChange={(event) =>
                                                                handleTokenDraftChange(
                                                                    token.id,
                                                                    'hit_points',
                                                                    event.target.value === ''
                                                                        ? ''
                                                                        : Number(event.target.value)
                                                                )
                                                            }
                                                            disabled={updatingToken === token.id}
                                                        />
                                                    </div>
                                                    <div className="space-y-1">
                                                        <Label htmlFor={`token-max-hit-points-${token.id}`} className="text-xs uppercase tracking-wide text-zinc-500">
                                                            Max HP (optional)
                                                        </Label>
                                                        <Input
                                                            id={`token-max-hit-points-${token.id}`}
                                                            type="number"
                                                            value={draft.max_hit_points === '' ? '' : draft.max_hit_points}
                                                            onChange={(event) =>
                                                                handleTokenDraftChange(
                                                                    token.id,
                                                                    'max_hit_points',
                                                                    event.target.value === ''
                                                                        ? ''
                                                                        : Number(event.target.value)
                                                                )
                                                            }
                                                            disabled={updatingToken === token.id}
                                                        />
                                                    </div>
                                                    <div className="space-y-1">
                                                        <Label htmlFor={`token-temp-hit-points-${token.id}`} className="text-xs uppercase tracking-wide text-zinc-500">
                                                            Temp HP (optional)
                                                        </Label>
                                                        <Input
                                                            id={`token-temp-hit-points-${token.id}`}
                                                            type="number"
                                                            value={
                                                                draft.temporary_hit_points === ''
                                                                    ? ''
                                                                    : draft.temporary_hit_points
                                                            }
                                                            onChange={(event) =>
                                                                handleTokenDraftChange(
                                                                    token.id,
                                                                    'temporary_hit_points',
                                                                    event.target.value === ''
                                                                        ? ''
                                                                        : Number(event.target.value)
                                                                )
                                                            }
                                                            disabled={updatingToken === token.id}
                                                        />
                                                    </div>
                                                </div>
                                                <div className="space-y-1">
                                                    <Label htmlFor={`token-z-index-${token.id}`} className="text-xs uppercase tracking-wide text-zinc-500">
                                                        Layer priority
                                                    </Label>
                                                    <Input
                                                        id={`token-z-index-${token.id}`}
                                                        type="number"
                                                        value={draft.z_index === '' ? '' : draft.z_index}
                                                        onChange={(event) =>
                                                            handleTokenDraftChange(
                                                                token.id,
                                                                'z_index',
                                                                event.target.value === '' ? '' : Number(event.target.value)
                                                            )
                                                        }
                                                        disabled={updatingToken === token.id}
                                                    />
                                                    <p className="text-[11px] text-zinc-500">
                                                        Higher values draw above lower ones when initiative matches.
                                                    </p>
                                                </div>
                                                <div className="space-y-1 sm:col-span-2">
                                                    <span className="text-xs uppercase tracking-wide text-zinc-500">
                                                        Condition presets
                                                    </span>
                                                    <div className="flex flex-wrap gap-2">
                                                        {token_conditions.map((option) => {
                                                            const active = draft.status_conditions.includes(option.value);

                                                            return (
                                                                <Button
                                                                    key={option.value}
                                                                    type="button"
                                                                    size="sm"
                                                                    variant="outline"
                                                                    className={`rounded-full border px-2.5 py-0.5 text-[11px] uppercase tracking-wide transition-colors ${
                                                                        active
                                                                            ? 'border-amber-500/60 bg-amber-500/10 text-amber-200 hover:bg-amber-500/20'
                                                                            : 'border-zinc-700 text-zinc-200 hover:border-amber-400/40 hover:text-amber-100'
                                                                    }`}
                                                                    onClick={() =>
                                                                        handleTokenDraftChange(
                                                                            token.id,
                                                                            'status_conditions',
                                                                            toggleCondition(
                                                                                draft.status_conditions,
                                                                                option.value
                                                                            )
                                                                        )
                                                                    }
                                                                    disabled={updatingToken === token.id}
                                                                >
                                                                    {option.label}
                                                                </Button>
                                                            );
                                                        })}
                                                    </div>
                                                    {draft.status_conditions.length > 0 && (
                                                        <div className="mt-2 grid gap-3 sm:grid-cols-2">
                                                            {draft.status_conditions.map((condition) => {
                                                                const value =
                                                                    draft.status_condition_durations[condition] ?? '';

                                                                return (
                                                                    <div key={condition} className="space-y-1">
                                                                        <Label
                                                                            htmlFor={`token-condition-duration-${token.id}-${condition}`}
                                                                            className="text-[11px] uppercase tracking-wide text-zinc-500"
                                                                        >
                                                                            {conditionLabelMap[condition]}
                                                                        </Label>
                                                                        <Input
                                                                            id={`token-condition-duration-${token.id}-${condition}`}
                                                                            type="number"
                                                                            min={1}
                                                                            max={MAX_CONDITION_DURATION}
                                                                            value={value === '' ? '' : value}
                                                                            onChange={(event) =>
                                                                                handleTokenDurationDraftChange(
                                                                                    token.id,
                                                                                    condition,
                                                                                    event.target.value
                                                                                )
                                                                            }
                                                                            disabled={updatingToken === token.id}
                                                                        />
                                                                        <p className="text-[11px] text-zinc-500">
                                                                            Leave blank to track manually.
                                                                        </p>
                                                                    </div>
                                                                );
                                                            })}
                                                        </div>
                                                    )}
                                                </div>
                                                <div className="space-y-1 sm:col-span-2">
                                                    <Label htmlFor={`token-status-${token.id}`} className="text-xs uppercase tracking-wide text-zinc-500">
                                                        Status effects (optional)
                                                    </Label>
                                                    <Textarea
                                                        id={`token-status-${token.id}`}
                                                        value={draft.status_effects}
                                                        onChange={(event) =>
                                                            handleTokenDraftChange(token.id, 'status_effects', event.target.value)
                                                        }
                                                        disabled={updatingToken === token.id}
                                                    />
                                                </div>
                                                <div className="space-y-1 sm:col-span-2">
                                                    <Label htmlFor={`token-note-${token.id}`} className="text-xs uppercase tracking-wide text-zinc-500">
                                                        GM note (optional)
                                                    </Label>
                                                    <Textarea
                                                        id={`token-note-${token.id}`}
                                                        value={draft.gm_note}
                                                        onChange={(event) =>
                                                            handleTokenDraftChange(token.id, 'gm_note', event.target.value)
                                                        }
                                                        disabled={updatingToken === token.id}
                                                    />
                                                </div>
                                            </div>
                                        </div>

                                        <div className="flex flex-col gap-2 md:items-end">
                                            <Button
                                                type="button"
                                                size="sm"
                                                disabled={updatingToken === token.id}
                                                onClick={() => handleTokenUpdate(token.id)}
                                            >
                                                {updatingToken === token.id ? 'Saving…' : 'Save changes'}
                                            </Button>
                                            <Button
                                                type="button"
                                                size="sm"
                                                variant="outline"
                                                className={
                                                    token.hidden
                                                        ? 'border-emerald-400/40 text-emerald-200 hover:text-emerald-100'
                                                        : 'border-slate-700 text-slate-200 hover:text-slate-100'
                                                }
                                                disabled={updatingToken === token.id}
                                                onClick={() => handleToggleTokenHidden(token)}
                                            >
                                                {updatingToken === token.id
                                                    ? 'Updating…'
                                                    : token.hidden
                                                    ? 'Reveal to party'
                                                    : 'Hide from party'}
                                            </Button>
                                            <Button
                                                type="button"
                                                size="sm"
                                                variant="ghost"
                                                className="text-rose-300 hover:text-rose-200"
                                                disabled={removingToken === token.id}
                                                onClick={() => handleRemoveToken(token)}
                                            >
                                                {removingToken === token.id ? 'Removing…' : 'Remove'}
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
