import { ChangeEvent, FormEvent, useEffect, useRef, useState } from 'react';

import { Head, Link, router, useForm, usePage } from '@inertiajs/react';

import AppLayout from '@/Layouts/AppLayout';
import PlayerConditionTimerSummaryPanel, {
    ConditionTimerSummaryResource,
} from '@/components/condition-timers/PlayerConditionTimerSummaryPanel';
import { MobileConditionTimerRecapWidget } from '@/components/condition-timers/MobileConditionTimerRecapWidget';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { getEcho } from '@/lib/realtime';
import { recordAnalyticsEventSync } from '@/lib/analytics';
import {
    applyAcknowledgementToSummary,
    type ConditionAcknowledgementPayload,
} from '@/lib/conditionAcknowledgements';
import { useConditionTimerSummaryCache } from '@/hooks/useConditionTimerSummaryCache';

function formatDateTime(value: string | null): string {
    if (!value) {
        return 'TBD';
    }

    try {
        const date = new Date(value);
        return new Intl.DateTimeFormat('en-US', {
            dateStyle: 'medium',
            timeStyle: 'short',
        }).format(date);
    } catch (error) {
        return value;
    }
}

type CampaignContext = {
    id: number;
    title: string;
    group: { id: number; name: string };
};

type SessionDetail = {
    id: number;
    title: string;
    agenda: string | null;
    summary: string | null;
    session_date: string | null;
    duration_minutes: number | null;
    location: string | null;
    recording_url: string | null;
    stored_recording: StoredRecording | null;
    turn: { id: number; number: number; window_started_at: string | null } | null;
    creator: { id: number; name: string };
};

type StoredRecording = {
    download_url: string;
    filename: string;
};

type SessionNoteResource = {
    id: number;
    content: string;
    visibility: string;
    is_pinned: boolean;
    author: { id: number; name: string };
    created_at: string | null;
};

type DiceRollResource = {
    id: number;
    expression: string;
    result_total: number;
    result_breakdown: { rolls?: number[]; modifier?: number } | null;
    roller: { id: number; name: string };
    created_at: string | null;
};

type InitiativeEntryResource = {
    id: number;
    name: string;
    dexterity_mod: number;
    initiative: number;
    is_current: boolean;
    order_index: number;
};

type AttendanceStatus = 'yes' | 'maybe' | 'no';

type AttendanceResponse = {
    id: number;
    status: AttendanceStatus;
    note: string | null;
    responded_at: string | null;
    user: { id: number; name: string };
};

type AttendanceSummary = {
    responses: AttendanceResponse[];
    counts: Record<AttendanceStatus, number>;
    current_user: { status: AttendanceStatus; note: string | null } | null;
};

type SessionRecapResource = {
    id: number;
    title: string | null;
    body: string;
    created_at: string | null;
    author: { id: number; name: string };
    can_delete: boolean;
};

type SessionRewardResource = {
    id: number;
    reward_type: string;
    title: string;
    quantity: number | null;
    awarded_to: string | null;
    notes: string | null;
    recorded_at: string | null;
    recorder: { id: number; name: string };
    can_delete: boolean;
};

function resolveAnalyticsRole(role?: string | null): string {
    if (!role) {
        return 'member';
    }

    if (role === 'owner' || role === 'dungeon-master') {
        return 'gm';
    }

    if (role === 'player') {
        return 'player';
    }

    return role;
}

type SessionShowProps = {
    campaign: CampaignContext;
    session: SessionDetail;
    notes: SessionNoteResource[];
    dice_rolls: DiceRollResource[];
    initiative: InitiativeEntryResource[];
    attendance: AttendanceSummary;
    recaps: SessionRecapResource[];
    rewards: SessionRewardResource[];
    note_visibilities: string[];
    permissions: {
        can_manage: boolean;
        can_delete: boolean;
        can_upload_recording: boolean;
        can_rsvp: boolean;
        can_share_recap: boolean;
        can_log_reward: boolean;
    };
    ai_dialogues: AiDialogueEntry[];
    condition_timer_summary: ConditionTimerSummaryResource;
    condition_timer_summary_share_url: string;
    viewer_role?: string | null;
};

type SessionNoteEventPayload = {
    note: Partial<SessionNoteResource> & { id: number; visibility?: string };
};

type DiceRollEventPayload = {
    roll: Partial<DiceRollResource> & { id: number };
};

type InitiativeEventPayload = {
    entry: Partial<InitiativeEntryResource> & { id: number };
    entries: InitiativeEntryResource[];
};

type AiDialogueEntry = {
    id: string;
    npc_name: string | null;
    tone: string | null;
    prompt: string;
    reply: string | null;
    status: string;
    created_at: string | null;
};

type ConditionTimerSummaryEventPayload = {
    summary: ConditionTimerSummaryResource;
};

const parseIsoTimestamp = (value: string | null): number => {
    if (!value) {
        return 0;
    }

    const parsed = Date.parse(value);

    return Number.isNaN(parsed) ? 0 : parsed;
};

const orderNotes = (items: SessionNoteResource[]): SessionNoteResource[] =>
    [...items].sort((a, b) => {
        if (a.is_pinned !== b.is_pinned) {
            return a.is_pinned ? -1 : 1;
        }

        return parseIsoTimestamp(b.created_at ?? null) - parseIsoTimestamp(a.created_at ?? null);
    });

const orderDiceRolls = (items: DiceRollResource[]): DiceRollResource[] =>
    [...items].sort((a, b) => parseIsoTimestamp(b.created_at ?? null) - parseIsoTimestamp(a.created_at ?? null));

const orderInitiativeEntries = (items: InitiativeEntryResource[]): InitiativeEntryResource[] =>
    [...items].sort((a, b) => a.order_index - b.order_index);

const visibilityLabels: Record<string, string> = {
    gm: 'GM only',
    players: 'Players',
    public: 'Public',
};

const defaultDiceExpression = '1d20';

const attendanceOptions: Array<{ value: AttendanceStatus; label: string; description: string }> = [
    { value: 'yes', label: 'Joining', description: 'Count me in for the full session.' },
    { value: 'maybe', label: 'Tentative', description: 'I might be late or need to confirm.' },
    { value: 'no', label: 'Unavailable', description: 'I cannot attend this gathering.' },
];

const attendanceStatusLabels: Record<AttendanceStatus, string> = {
    yes: 'Joining',
    maybe: 'Tentative',
    no: 'Unavailable',
};

const attendanceStatusStyles: Record<AttendanceStatus, string> = {
    yes: 'border-emerald-500/40 bg-emerald-500/10 text-emerald-200',
    maybe: 'border-amber-500/40 bg-amber-500/10 text-amber-200',
    no: 'border-rose-500/40 bg-rose-500/10 text-rose-200',
};

const attendanceStatusOrder: AttendanceStatus[] = ['yes', 'maybe', 'no'];

const rewardTypeLabels: Record<string, string> = {
    loot: 'Loot',
    experience: 'Experience',
    boon: 'Boon',
    note: 'Log Entry',
};

const rewardTypeStyles: Record<string, string> = {
    loot: 'border-amber-500/40 bg-amber-500/10 text-amber-200',
    experience: 'border-indigo-500/40 bg-indigo-500/10 text-indigo-200',
    boon: 'border-emerald-500/40 bg-emerald-500/10 text-emerald-200',
    note: 'border-slate-500/40 bg-slate-500/10 text-slate-200',
};

export default function SessionShow({
    campaign,
    session,
    notes,
    dice_rolls: diceRolls,
    initiative,
    attendance,
    recaps,
    rewards,
    note_visibilities: noteVisibilities,
    permissions,
    ai_dialogues: aiDialogues,
    condition_timer_summary: conditionTimerSummary,
    condition_timer_summary_share_url: conditionTimerSummaryShareUrl,
    viewer_role: viewerRole,
}: SessionShowProps) {
    const page = usePage();
    const currentUserId = (page.props.auth?.user?.id as number | undefined) ?? null;
    const defaultVisibility = noteVisibilities.includes('players')
        ? 'players'
        : noteVisibilities[0] ?? 'players';
    const summaryStorageKey = `condition-summary:group-${campaign.group.id}`;

    const {
        summary: conditionSummary,
        updateSummary: updateConditionSummary,
    } = useConditionTimerSummaryCache({
        storageKey: summaryStorageKey,
        initialSummary: conditionTimerSummary,
    });

    const conditionSummaryRef = useRef(conditionSummary);

    useEffect(() => {
        conditionSummaryRef.current = conditionSummary;
    }, [conditionSummary]);

    const analyticsRole = resolveAnalyticsRole(viewerRole);
    const [isSummaryVisible, setIsSummaryVisible] = useState(true);

    const handleDismissSummary = (source: 'session_panel' | 'mobile_widget') => {
        setIsSummaryVisible(false);
        recordAnalyticsEventSync({
            key: 'timer_summary.dismissed',
            groupId: campaign.group.id,
            payload: {
                group_id: campaign.group.id,
                user_role: analyticsRole,
                source,
                reason: 'temporary',
            },
        });
    };

    const handleRestoreSummary = () => {
        setIsSummaryVisible(true);
    };

    const [noteFeed, setNoteFeed] = useState<SessionNoteResource[]>(() => orderNotes(notes));
    const [diceLog, setDiceLog] = useState<DiceRollResource[]>(() => orderDiceRolls(diceRolls));
    const [initiativeFeed, setInitiativeFeed] = useState<InitiativeEntryResource[]>(() => orderInitiativeEntries(initiative));
    const [npcDialogueLog, setNpcDialogueLog] = useState<AiDialogueEntry[]>(() => [...aiDialogues]);
    const [npcForm, setNpcForm] = useState({
        npcName: '',
        tone: '',
        prompt: '',
    });
    const [npcSubmitting, setNpcSubmitting] = useState(false);
    const [npcError, setNpcError] = useState<string | null>(null);
    const rewardTypeOptions = Object.entries(rewardTypeLabels);

    useEffect(() => {
        setNoteFeed(orderNotes(notes));
    }, [notes]);

    useEffect(() => {
        setDiceLog(orderDiceRolls(diceRolls));
    }, [diceRolls]);

    useEffect(() => {
        setInitiativeFeed(orderInitiativeEntries(initiative));
    }, [initiative]);

    useEffect(() => {
        setNpcDialogueLog(aiDialogues);
    }, [aiDialogues]);

    const attendanceForm = useForm({
        status: attendance.current_user?.status ?? 'yes',
        note: attendance.current_user?.note ?? '',
    });

    useEffect(() => {
        attendanceForm.setData('status', attendance.current_user?.status ?? 'yes');
        attendanceForm.setData('note', attendance.current_user?.note ?? '');
    }, [attendance.current_user?.status, attendance.current_user?.note]);

    const recapForm = useForm({
        title: '',
        body: '',
    });

    const rewardForm = useForm({
        reward_type: 'loot',
        title: '',
        quantity: '',
        awarded_to: '',
        notes: '',
    });

    const noteForm = useForm({
        content: '',
        visibility: defaultVisibility,
        is_pinned: false,
    });

    const diceForm = useForm({
        expression: defaultDiceExpression,
    });

    const initiativeForm = useForm({
        name: '',
        dexterity_mod: '0',
        initiative: '',
        is_current: false,
    });

    const recordingForm = useForm<{ recording: File | null }>({
        recording: null,
    });

    const handleRecordingChange = (event: ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0] ?? null;
        recordingForm.setData('recording', file);
        if (file) {
            recordingForm.clearErrors('recording');
        }
    };

    const handleNpcSubmit = async (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (npcSubmitting) {
            return;
        }

        if (!npcForm.npcName || !npcForm.prompt) {
            setNpcError('Provide an NPC name and a player prompt.');
            return;
        }

        setNpcError(null);
        setNpcSubmitting(true);

        try {
            const csrf = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content ?? '';
            const response = await fetch(route('api.campaigns.sessions.npc-dialogue', [campaign.id, session.id]), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({
                    npc_name: npcForm.npcName,
                    prompt: npcForm.prompt,
                    tone: npcForm.tone || undefined,
                }),
            });

            if (!response.ok) {
                const error = await response.json().catch(() => null);
                const message =
                    (error && typeof error.message === 'string' && error.message) ||
                    'The NPC could not be reached. Try again shortly.';

                throw new Error(message);
            }

            const payload = (await response.json()) as {
                request_id: string;
                status: string;
                reply: string;
                created_at: string | null;
            };

            const entry: AiDialogueEntry = {
                id: payload.request_id,
                npc_name: npcForm.npcName,
                tone: npcForm.tone || null,
                prompt: npcForm.prompt,
                reply: payload.reply,
                status: payload.status,
                created_at: payload.created_at,
            };

            setNpcDialogueLog((current) => [entry, ...current].slice(0, 10));
            setNpcForm({ npcName: '', tone: '', prompt: '' });
        } catch (error) {
            setNpcError(error instanceof Error ? error.message : 'Unable to contact the NPC right now.');
        } finally {
            setNpcSubmitting(false);
        }
    };

    const handleRewardSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        rewardForm
            .transform((data) => ({
                ...data,
                quantity: data.quantity ? Number(data.quantity) : null,
                awarded_to: data.awarded_to || null,
                notes: data.notes || null,
            }))
            .post(
            route('campaigns.sessions.rewards.store', {
                campaign: campaign.id,
                session: session.id,
            }),
            {
                preserveScroll: true,
                onSuccess: () => rewardForm.reset('title', 'quantity', 'awarded_to', 'notes'),
            },
        );
    };

    useEffect(() => {
        const echo = getEcho();

        if (!echo) {
            return;
        }

        const baseChannel = `campaigns.${campaign.id}.sessions.${session.id}.workspace`;
        const channel = echo.private(baseChannel);

        const handleNoteUpsert = (payload: SessionNoteEventPayload) => {
            if (!payload.note || payload.note.content === undefined) {
                return;
            }

            const incoming = payload.note as SessionNoteResource;

            setNoteFeed((current) =>
                orderNotes([
                    ...current.filter((noteItem) => noteItem.id !== incoming.id),
                    incoming,
                ]),
            );
        };

        const handleNoteDelete = (payload: SessionNoteEventPayload) => {
            setNoteFeed((current) => current.filter((noteItem) => noteItem.id !== payload.note.id));
        };

        channel.listen('.session-note.created', handleNoteUpsert);
        channel.listen('.session-note.updated', handleNoteUpsert);
        channel.listen('.session-note.deleted', handleNoteDelete);

        const handleDiceCreated = (payload: DiceRollEventPayload) => {
            if (!payload.roll || payload.roll.expression === undefined) {
                return;
            }

            const incoming = payload.roll as DiceRollResource;

            setDiceLog((current) =>
                orderDiceRolls([
                    ...current.filter((roll) => roll.id !== incoming.id),
                    incoming,
                ]),
            );
        };

        const handleDiceDeleted = (payload: DiceRollEventPayload) => {
            setDiceLog((current) => current.filter((roll) => roll.id !== payload.roll.id));
        };

        channel.listen('.dice-roll.created', handleDiceCreated);
        channel.listen('.dice-roll.deleted', handleDiceDeleted);

        const handleInitiative = (payload: InitiativeEventPayload) => {
            if (Array.isArray(payload.entries)) {
                setInitiativeFeed(orderInitiativeEntries(payload.entries));
            }
        };

        channel.listen('.initiative-entry.created', handleInitiative);
        channel.listen('.initiative-entry.updated', handleInitiative);
        channel.listen('.initiative-entry.deleted', handleInitiative);

        let gmChannel: ReturnType<typeof echo.private> | null = null;

        if (permissions.can_manage) {
            gmChannel = echo.private(`${baseChannel}.gms`);
            gmChannel.listen('.session-note.created', handleNoteUpsert);
            gmChannel.listen('.session-note.updated', handleNoteUpsert);
            gmChannel.listen('.session-note.deleted', handleNoteDelete);
        }

        const conditionChannel = echo.private(`groups.${campaign.group.id}.condition-timers`);

        const handleConditionSummary = (payload: ConditionTimerSummaryEventPayload) => {
            if (!payload.summary) {
                return;
            }

            updateConditionSummary(payload.summary);
        };

        conditionChannel.listen('.condition-timer-summary.updated', handleConditionSummary);

        const handleConditionAcknowledgement = (payload: ConditionAcknowledgementPayload) => {
            const nextSummary = applyAcknowledgementToSummary(
                conditionSummaryRef.current,
                payload,
                currentUserId,
            );

            updateConditionSummary(nextSummary, { allowStale: true });
        };

        conditionChannel.listen('.condition-timer-acknowledgement.recorded', handleConditionAcknowledgement);

        return () => {
            channel.stopListening('.session-note.created');
            channel.stopListening('.session-note.updated');
            channel.stopListening('.session-note.deleted');
            channel.stopListening('.dice-roll.created');
            channel.stopListening('.dice-roll.deleted');
            channel.stopListening('.initiative-entry.created');
            channel.stopListening('.initiative-entry.updated');
            channel.stopListening('.initiative-entry.deleted');
            echo.leave(baseChannel);

            if (gmChannel) {
                gmChannel.stopListening('.session-note.created');
                gmChannel.stopListening('.session-note.updated');
                gmChannel.stopListening('.session-note.deleted');
                echo.leave(`${baseChannel}.gms`);
            }

            conditionChannel.stopListening('.condition-timer-summary.updated');
            conditionChannel.stopListening('.condition-timer-acknowledgement.recorded');
            echo.leave(`groups.${campaign.group.id}.condition-timers`);
        };
    }, [
        campaign.id,
        campaign.group.id,
        session.id,
        permissions.can_manage,
        summaryStorageKey,
        updateConditionSummary,
        currentUserId,
    ]);

    const submitNote = (event: FormEvent) => {
        event.preventDefault();
        noteForm.post(route('campaigns.sessions.notes.store', { campaign: campaign.id, session: session.id }), {
            preserveScroll: true,
            onSuccess: () => {
                noteForm.reset('content');
            },
        });
    };

    const submitDice = (event: FormEvent) => {
        event.preventDefault();
        diceForm.post(route('campaigns.sessions.dice-rolls.store', { campaign: campaign.id, session: session.id }), {
            preserveScroll: true,
            onSuccess: () => diceForm.reset('expression'),
        });
    };

    const submitInitiative = (event: FormEvent) => {
        event.preventDefault();
        initiativeForm
            .transform((data) => ({
                ...data,
                dexterity_mod: data.dexterity_mod === '' ? null : Number(data.dexterity_mod),
                initiative: data.initiative === '' ? null : Number(data.initiative),
            }))
            .post(route('campaigns.sessions.initiative.store', { campaign: campaign.id, session: session.id }), {
                preserveScroll: true,
                onSuccess: () => initiativeForm.reset(),
                onFinish: () => initiativeForm.transform((data) => data),
            });
    };

    const submitAttendance = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        attendanceForm.post(
            route('campaigns.sessions.attendance.store', { campaign: campaign.id, session: session.id }),
            { preserveScroll: true },
        );
    };

    const submitRecap = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        recapForm.post(
            route('campaigns.sessions.recaps.store', { campaign: campaign.id, session: session.id }),
            {
                preserveScroll: true,
                onSuccess: () => {
                    recapForm.reset();
                },
            },
        );
    };

    const submitRecording = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (!recordingForm.data.recording) {
            recordingForm.setError('recording', 'Select a file to upload.');
            return;
        }

        const formElement = event.currentTarget;

        recordingForm.post(
            route('campaigns.sessions.recording.store', { campaign: campaign.id, session: session.id }),
            {
                forceFormData: true,
                preserveScroll: true,
                onSuccess: () => {
                    formElement.reset();
                },
                onFinish: () => {
                    recordingForm.setData('recording', null);
                },
            },
        );
    };

    const handleAttendanceClear = () => {
        router.delete(
            route('campaigns.sessions.attendance.destroy', { campaign: campaign.id, session: session.id }),
            { preserveScroll: true },
        );
    };

    const handleRecapDelete = (recapId: number) => {
        router.delete(
            route('campaigns.sessions.recaps.destroy', {
                campaign: campaign.id,
                session: session.id,
                recap: recapId,
            }),
            { preserveScroll: true },
        );
    };

    const handleRewardDelete = (rewardId: number) => {
        router.delete(
            route('campaigns.sessions.rewards.destroy', {
                campaign: campaign.id,
                session: session.id,
                reward: rewardId,
            }),
            { preserveScroll: true },
        );
    };

    const handleNoteDelete = (noteId: number) => {
        router.delete(route('campaigns.sessions.notes.destroy', { campaign: campaign.id, session: session.id, note: noteId }), {
            preserveScroll: true,
        });
    };

    const handleDiceDelete = (rollId: number) => {
        router.delete(
            route('campaigns.sessions.dice-rolls.destroy', {
                campaign: campaign.id,
                session: session.id,
                roll: rollId,
            }),
            { preserveScroll: true },
        );
    };

    const handleInitiativeDelete = (entryId: number) => {
        router.delete(
            route('campaigns.sessions.initiative.destroy', {
                campaign: campaign.id,
                session: session.id,
                entry: entryId,
            }),
            { preserveScroll: true },
        );
    };

    const handleInitiativePromote = (entryId: number) => {
        router.patch(
            route('campaigns.sessions.initiative.update', {
                campaign: campaign.id,
                session: session.id,
                entry: entryId,
            }),
            { is_current: true },
            { preserveScroll: true },
        );
    };

    const handleInitiativeBump = (entry: InitiativeEntryResource, direction: number) => {
        router.patch(
            route('campaigns.sessions.initiative.update', {
                campaign: campaign.id,
                session: session.id,
                entry: entry.id,
            }),
            { order_index: Math.max(0, entry.order_index + direction) },
            { preserveScroll: true },
        );
    };

    const handleRecordingRemove = () => {
        if (!confirm('Remove the stored recording from this session?')) {
            return;
        }

        router.delete(
            route('campaigns.sessions.recording.destroy', { campaign: campaign.id, session: session.id }),
            { preserveScroll: true },
        );
    };

    const diceBreakdown = (roll: DiceRollResource): string => {
        const parts: string[] = [];
        const rolls = roll.result_breakdown?.rolls;
        if (rolls && rolls.length > 0) {
            parts.push(rolls.join(' + '));
        }

        const modifier = roll.result_breakdown?.modifier ?? 0;
        if (modifier > 0) {
            parts.push(`+ ${modifier}`);
        } else if (modifier < 0) {
            parts.push(`- ${Math.abs(modifier)}`);
        }

        return parts.length > 0 ? parts.join(' ') : roll.expression;
    };

    const markdownExportUrl = route('campaigns.sessions.exports.markdown', {
        campaign: campaign.id,
        session: session.id,
    });

    const pdfExportUrl = route('campaigns.sessions.exports.pdf', {
        campaign: campaign.id,
        session: session.id,
    });

    return (
        <AppLayout>
            <Head title={`${session.title} workspace`} />

            <div className="flex flex-col gap-4 pb-8 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 className="text-3xl font-semibold text-zinc-100">{session.title}</h1>
                    <p className="text-sm text-zinc-400">
                        Hosted by {session.creator.name} • {formatDateTime(session.session_date)} •{' '}
                        {session.duration_minutes ? `${session.duration_minutes} min` : 'open length'}
                    </p>
                </div>

                <div className="flex flex-wrap items-center justify-end gap-3">
                    <Button variant="outline" asChild className="border-zinc-700 text-zinc-200 hover:text-amber-200">
                        <Link href={route('campaigns.sessions.index', { campaign: campaign.id })}>Back to sessions</Link>
                    </Button>
                    <Button
                        variant="outline"
                        asChild
                        className="border-zinc-700 text-zinc-200 hover:text-amber-200"
                    >
                        <a href={markdownExportUrl} download>
                            Download Markdown
                        </a>
                    </Button>
                    <Button
                        variant="outline"
                        asChild
                        className="border-zinc-700 text-zinc-200 hover:text-amber-200"
                    >
                        <a href={pdfExportUrl} download>
                            Download PDF
                        </a>
                    </Button>
                    {permissions.can_manage && (
                        <Button asChild>
                            <Link href={route('campaigns.sessions.edit', { campaign: campaign.id, session: session.id })}>
                                Edit session
                            </Link>
                        </Button>
                    )}
                    {permissions.can_delete && (
                        <Button
                            type="button"
                            variant="ghost"
                            className="text-rose-300 hover:text-rose-400"
                            onClick={() => {
                                if (confirm('Archive this session?')) {
                                    router.delete(
                                        route('campaigns.sessions.destroy', {
                                            campaign: campaign.id,
                                            session: session.id,
                                        }),
                                    );
                                }
                            }}
                        >
                            Archive session
                        </Button>
                    )}
                </div>
            </div>

            <div className="grid gap-6 lg:grid-cols-[2fr,1fr]">
                <section className="grid gap-6">
                    <div className="rounded-xl border border-zinc-800 bg-zinc-950/60 p-6 shadow-inner shadow-black/40">
                        <h2 className="text-lg font-semibold text-zinc-100">Session briefing</h2>
                        <dl className="mt-4 grid gap-3 text-sm text-zinc-400 md:grid-cols-2">
                            <div>
                                <dt className="text-xs uppercase tracking-wide text-zinc-500">Location</dt>
                                <dd>{session.location ?? 'Unspecified'}</dd>
                            </div>
                            <div>
                                <dt className="text-xs uppercase tracking-wide text-zinc-500">Linked turn</dt>
                                <dd>
                                    {session.turn ? (
                                        <span>
                                            Turn #{session.turn.number} (processed {formatDateTime(session.turn.window_started_at)})
                                        </span>
                                    ) : (
                                        'Not linked'
                                    )}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-xs uppercase tracking-wide text-zinc-500">Recording</dt>
                                <dd className="space-y-2">
                                    {session.recording_url && (
                                        <a
                                            href={session.recording_url}
                                            className="block text-amber-300 underline decoration-dotted underline-offset-4"
                                            target="_blank"
                                            rel="noreferrer"
                                        >
                                            External link
                                        </a>
                                    )}
                                    {session.stored_recording ? (
                                        <div className="flex flex-wrap items-center gap-2 text-sm text-zinc-300">
                                            <a
                                                href={session.stored_recording.download_url}
                                                className="text-amber-300 underline decoration-dotted underline-offset-4"
                                                download
                                            >
                                                Download {session.stored_recording.filename}
                                            </a>
                                            {permissions.can_upload_recording && (
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    className="text-xs text-rose-300 hover:text-rose-400"
                                                    onClick={handleRecordingRemove}
                                                >
                                                    Remove stored copy
                                                </Button>
                                            )}
                                        </div>
                                    ) : (
                                        <p className="text-sm text-zinc-500">
                                            {session.recording_url
                                                ? 'No stored upload yet.'
                                                : 'No recording yet.'}
                                        </p>
                                    )}
                                </dd>
                            </div>
                        </dl>

                        {permissions.can_upload_recording && (
                            <form
                                onSubmit={submitRecording}
                                className="mt-4 flex flex-wrap items-center gap-3 rounded-lg border border-zinc-800/70 bg-zinc-950/70 p-4"
                            >
                                <div className="flex-1 min-w-[200px]">
                                    <Label htmlFor="recording-upload" className="text-xs uppercase tracking-wide text-zinc-500">
                                        Upload recording
                                    </Label>
                                    <Input
                                        id="recording-upload"
                                        type="file"
                                        accept="audio/*,video/*"
                                        onChange={handleRecordingChange}
                                        className="mt-1 border-zinc-700 bg-zinc-900/60 text-sm text-zinc-100"
                                    />
                                    <p className="mt-1 text-xs text-zinc-500">Up to 500 MB audio or video.</p>
                                    {recordingForm.errors.recording && (
                                        <p className="text-sm text-rose-400">{recordingForm.errors.recording}</p>
                                    )}
                                </div>
                                <Button
                                    type="submit"
                                    size="sm"
                                    disabled={recordingForm.processing || !recordingForm.data.recording}
                                >
                                    {recordingForm.processing ? 'Uploading…' : 'Upload'}
                                </Button>
                            </form>
                        )}

                        <div className="mt-6 grid gap-6">
                            <div>
                                <h3 className="text-sm font-semibold uppercase tracking-wide text-zinc-400">Agenda</h3>
                                <p className="mt-2 whitespace-pre-wrap text-sm text-zinc-300">
                                    {session.agenda ?? 'No agenda logged yet.'}
                                </p>
                            </div>
                            <div>
                                <h3 className="text-sm font-semibold uppercase tracking-wide text-zinc-400">Summary</h3>
                                <p className="mt-2 whitespace-pre-wrap text-sm text-zinc-300">
                                    {session.summary ?? 'Summaries will appear here once logged.'}
                                </p>
                            </div>
                        </div>
                    </div>

                    {isSummaryVisible ? (
                        <>
                            <MobileConditionTimerRecapWidget
                                summary={conditionSummary}
                                shareUrl={conditionTimerSummaryShareUrl}
                                className="md:hidden"
                                source="mobile_widget"
                                viewerRole={viewerRole}
                                onDismiss={() => handleDismissSummary('mobile_widget')}
                            />
                            <PlayerConditionTimerSummaryPanel
                                summary={conditionSummary}
                                shareUrl={conditionTimerSummaryShareUrl}
                                className="hidden md:block"
                                source="session_panel"
                                viewerRole={viewerRole}
                                onDismiss={() => handleDismissSummary('session_panel')}
                            />
                        </>
                    ) : (
                        <div className="rounded-xl border border-zinc-800 bg-zinc-950/60 p-4 text-sm text-zinc-300 shadow-inner shadow-black/30">
                            <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                <p>Condition summary hidden for now. Reopen it when you need a tactical refresher.</p>
                                <button
                                    type="button"
                                    onClick={handleRestoreSummary}
                                    className="inline-flex items-center justify-center rounded-md border border-amber-500/60 px-3 py-1 text-xs font-semibold text-amber-300 transition hover:border-amber-400 hover:text-amber-200"
                                >
                                    Show summary
                                </button>
                            </div>
                        </div>
                    )}

                    <div className="grid gap-6 md:grid-cols-2">
                        <section className="rounded-xl border border-zinc-800 bg-zinc-950/60 p-6 shadow-inner shadow-black/40">
                            <header className="mb-4 flex items-center justify-between">
                                <div>
                                    <h2 className="text-lg font-semibold text-zinc-100">Rewards & loot ledger</h2>
                                    <p className="text-xs text-zinc-500">
                                        Track treasure, boons, and experience shared during the session.
                                    </p>
                                </div>
                            </header>

                            {permissions.can_log_reward ? (
                                <form onSubmit={handleRewardSubmit} className="space-y-4">
                                    <div className="grid gap-4 md:grid-cols-2">
                                        <div className="grid gap-2">
                                            <Label htmlFor="reward-type">Type</Label>
                                            <select
                                                id="reward-type"
                                                value={rewardForm.data.reward_type}
                                                onChange={(event) => rewardForm.setData('reward_type', event.target.value)}
                                                className="h-9 w-full rounded-md border border-zinc-700 bg-zinc-900/60 px-2 text-sm text-zinc-100 focus:outline-none focus:ring-2 focus:ring-amber-500"
                                            >
                                                {rewardTypeOptions.map(([value, label]) => (
                                                    <option key={value} value={value}>
                                                        {label}
                                                    </option>
                                                ))}
                                            </select>
                                            {rewardForm.errors.reward_type && (
                                                <p className="text-sm text-rose-400">{rewardForm.errors.reward_type}</p>
                                            )}
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="reward-title">Title</Label>
                                            <Input
                                                id="reward-title"
                                                value={rewardForm.data.title}
                                                onChange={(event) => rewardForm.setData('title', event.target.value)}
                                                placeholder="Jeweled chalice"
                                                required
                                            />
                                            {rewardForm.errors.title && (
                                                <p className="text-sm text-rose-400">{rewardForm.errors.title}</p>
                                            )}
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="reward-quantity">Quantity</Label>
                                            <Input
                                                id="reward-quantity"
                                                type="number"
                                                min={1}
                                                value={rewardForm.data.quantity}
                                                onChange={(event) => rewardForm.setData('quantity', event.target.value)}
                                                placeholder="1"
                                            />
                                            {rewardForm.errors.quantity && (
                                                <p className="text-sm text-rose-400">{rewardForm.errors.quantity}</p>
                                            )}
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="reward-awarded">Awarded to</Label>
                                            <Input
                                                id="reward-awarded"
                                                value={rewardForm.data.awarded_to}
                                                onChange={(event) => rewardForm.setData('awarded_to', event.target.value)}
                                                placeholder="Party stash"
                                            />
                                            {rewardForm.errors.awarded_to && (
                                                <p className="text-sm text-rose-400">{rewardForm.errors.awarded_to}</p>
                                            )}
                                        </div>

                                        <div className="grid gap-2 md:col-span-2">
                                            <Label htmlFor="reward-notes">Notes</Label>
                                            <Textarea
                                                id="reward-notes"
                                                rows={3}
                                                value={rewardForm.data.notes}
                                                onChange={(event) => rewardForm.setData('notes', event.target.value)}
                                                placeholder="Identified as feycraft; keep away from cold iron."
                                            />
                                            {rewardForm.errors.notes && (
                                                <p className="text-sm text-rose-400">{rewardForm.errors.notes}</p>
                                            )}
                                        </div>
                                    </div>

                                    <div className="flex flex-wrap items-center gap-3">
                                        <Button type="submit" size="sm" disabled={rewardForm.processing}>
                                            {rewardForm.processing ? 'Logging…' : 'Log reward'}
                                        </Button>
                                    </div>
                                </form>
                            ) : (
                                <p className="text-sm text-zinc-500">
                                    Only campaign members may log session rewards.
                                </p>
                            )}

                            <div className="mt-6 space-y-4">
                                {rewards.length === 0 ? (
                                    <p className="text-sm text-zinc-500">No rewards logged yet. Tally the haul when you do!</p>
                                ) : (
                                    rewards.map((reward) => (
                                        <article
                                            key={reward.id}
                                            className="rounded-lg border border-zinc-800/80 bg-zinc-950/80 p-4 text-sm text-zinc-300"
                                        >
                                            <header className="flex flex-wrap items-start justify-between gap-3">
                                                <div className="space-y-1">
                                                    <div className="flex flex-wrap items-center gap-2">
                                                        <p className="text-base font-semibold text-zinc-100">
                                                            {reward.title}
                                                            {reward.quantity ? ` ×${reward.quantity}` : ''}
                                                        </p>
                                                        <span
                                                            className={`rounded-full border px-2 py-0.5 text-xs ${
                                                                rewardTypeStyles[reward.reward_type] ?? 'border-zinc-600 bg-zinc-800/80 text-zinc-200'
                                                            }`}
                                                        >
                                                            {rewardTypeLabels[reward.reward_type] ?? reward.reward_type}
                                                        </span>
                                                    </div>
                                                    <p className="text-xs text-zinc-500">
                                                        Logged by {reward.recorder.name} • {formatDateTime(reward.recorded_at)}
                                                        {reward.awarded_to ? ` • Awarded to ${reward.awarded_to}` : ''}
                                                    </p>
                                                </div>
                                                {reward.can_delete && (
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="sm"
                                                        className="text-xs text-rose-300 hover:text-rose-400"
                                                        onClick={() => handleRewardDelete(reward.id)}
                                                    >
                                                        Remove
                                                    </Button>
                                                )}
                                            </header>

                                            {reward.notes && <p className="mt-3 whitespace-pre-wrap">{reward.notes}</p>}
                                        </article>
                                    ))
                                )}
                            </div>
                        </section>

                        <section className="rounded-xl border border-zinc-800 bg-zinc-950/60 p-6 shadow-inner shadow-black/40">
                            <header className="mb-4 flex items-center justify-between">
                                <div>
                                    <h2 className="text-lg font-semibold text-zinc-100">Session recaps</h2>
                                    <p className="text-xs text-zinc-500">
                                        Chronicle the adventure with quick highlights and reflections.
                                    </p>
                                </div>
                            </header>

                            {permissions.can_share_recap ? (
                                <form onSubmit={submitRecap} className="space-y-4">
                                    <div className="grid gap-4 md:grid-cols-2">
                                        <div className="grid gap-2">
                                            <Label htmlFor="recap-title">Title (optional)</Label>
                                            <Input
                                                id="recap-title"
                                                value={recapForm.data.title}
                                                onChange={(event) => recapForm.setData('title', event.target.value)}
                                                placeholder="Aftermath at the Ember Gate"
                                            />
                                            {recapForm.errors.title && (
                                                <p className="text-sm text-rose-400">{recapForm.errors.title}</p>
                                            )}
                                        </div>
                                        <div className="grid gap-2 md:col-span-2">
                                            <Label htmlFor="recap-body">Recap</Label>
                                            <Textarea
                                                id="recap-body"
                                                rows={4}
                                                value={recapForm.data.body}
                                                onChange={(event) => recapForm.setData('body', event.target.value)}
                                                placeholder="Summarize the big beats, clutch rolls, or cliffhanger."
                                                required
                                            />
                                            {recapForm.errors.body && (
                                                <p className="text-sm text-rose-400">{recapForm.errors.body}</p>
                                            )}
                                        </div>
                                    </div>

                                    <div className="flex flex-wrap items-center gap-3">
                                        <Button type="submit" size="sm" disabled={recapForm.processing}>
                                            {recapForm.processing ? 'Sharing…' : 'Share recap'}
                                        </Button>
                                    </div>
                                </form>
                            ) : (
                                <p className="text-sm text-zinc-500">
                                    Only campaign members may add recaps for this session.
                                </p>
                            )}

                            <div className="mt-6 space-y-4">
                                {recaps.length === 0 ? (
                                    <p className="text-sm text-zinc-500">No recaps yet. Capture the tale while it’s fresh!</p>
                                ) : (
                                    recaps.map((recap) => (
                                        <article
                                            key={recap.id}
                                            className="rounded-lg border border-zinc-800/80 bg-zinc-950/80 p-4 text-sm text-zinc-300"
                                        >
                                            <header className="flex items-start justify-between gap-3">
                                                <div>
                                                    <p className="text-base font-semibold text-zinc-100">
                                                        {recap.title ?? `${recap.author.name}'s recap`}
                                                    </p>
                                                    <p className="text-xs text-zinc-500">
                                                        {recap.author.name} • {formatDateTime(recap.created_at)}
                                                    </p>
                                                </div>
                                                {recap.can_delete && (
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="sm"
                                                        className="text-xs text-rose-300 hover:text-rose-400"
                                                        onClick={() => handleRecapDelete(recap.id)}
                                                    >
                                                        Remove
                                                    </Button>
                                                )}
                                            </header>
                                            <p className="mt-3 whitespace-pre-wrap">{recap.body}</p>
                                        </article>
                                    ))
                                )}
                            </div>
                        </section>

                        <section className="rounded-xl border border-zinc-800 bg-zinc-950/60 p-6 shadow-inner shadow-black/40">
                            <header className="mb-4 flex items-center justify-between">
                                <div>
                                    <h2 className="text-lg font-semibold text-zinc-100">Notes</h2>
                                    <p className="text-xs text-zinc-500">Capture discoveries, NPC quotes, and tactical plans.</p>
                                </div>
                            </header>

                            <form onSubmit={submitNote} className="space-y-3">
                                <div className="grid gap-2">
                                    <Label htmlFor="note-content">New note</Label>
                                    <Textarea
                                        id="note-content"
                                        rows={3}
                                        value={noteForm.data.content}
                                        onChange={(event) => noteForm.setData('content', event.target.value)}
                                        required
                                    />
                                    {noteForm.errors.content && (
                                        <p className="text-sm text-rose-400">{noteForm.errors.content}</p>
                                    )}
                                </div>

                                <div className="flex flex-wrap items-center gap-3 text-sm text-zinc-400">
                                    <label className="flex items-center gap-2">
                                        <span className="text-xs uppercase tracking-wide text-zinc-500">Visibility</span>
                                        <select
                                            value={noteForm.data.visibility}
                                            onChange={(event) => noteForm.setData('visibility', event.target.value)}
                                            className="h-8 rounded-md border border-zinc-700 bg-zinc-900/60 px-2 text-sm text-zinc-100 focus:outline-none focus:ring-2 focus:ring-amber-500"
                                        >
                                            {noteVisibilities.map((visibility) => (
                                                <option
                                                    key={visibility}
                                                    value={visibility}
                                                    disabled={!permissions.can_manage && visibility === 'gm'}
                                                >
                                                    {visibilityLabels[visibility] ?? visibility}
                                                </option>
                                            ))}
                                        </select>
                                    </label>

                                    <label className="flex items-center gap-2 text-xs uppercase tracking-wide text-zinc-500">
                                        <Checkbox
                                            checked={noteForm.data.is_pinned}
                                            onChange={(event) => noteForm.setData('is_pinned', event.target.checked)}
                                            disabled={!permissions.can_manage}
                                        />
                                        Pin
                                    </label>
                                </div>

                                {noteForm.errors.visibility && (
                                    <p className="text-sm text-rose-400">{noteForm.errors.visibility}</p>
                                )}

                                <Button type="submit" size="sm" disabled={noteForm.processing}>
                                    Add note
                                </Button>
                            </form>

                            <div className="mt-6 space-y-4">
                                {noteFeed.length === 0 ? (
                                    <p className="text-sm text-zinc-500">No notes yet. Start chronicling the tale!</p>
                                ) : (
                                    noteFeed.map((note) => (
                                        <article
                                            key={note.id}
                                            className="rounded-lg border border-zinc-800/80 bg-zinc-950/80 p-4 text-sm text-zinc-300"
                                        >
                                            <header className="mb-2 flex items-start justify-between gap-3">
                                                <div>
                                                    <p className="font-medium text-zinc-100">{note.author.name}</p>
                                                    <p className="text-xs text-zinc-500">
                                                        {formatDateTime(note.created_at)} • {visibilityLabels[note.visibility] ?? note.visibility}
                                                    </p>
                                                </div>
                                                <div className="flex gap-2">
                                                    {note.is_pinned && (
                                                        <span className="rounded-full bg-amber-500/10 px-2 py-0.5 text-xs text-amber-300">
                                                            Pinned
                                                        </span>
                                                    )}
                                                    {(permissions.can_manage || note.author.id === currentUserId) && (
                                                        <Button
                                                            type="button"
                                                            variant="ghost"
                                                            size="sm"
                                                            className="text-xs text-rose-300 hover:text-rose-400"
                                                            onClick={() => handleNoteDelete(note.id)}
                                                        >
                                                            Remove
                                                        </Button>
                                                    )}
                                                </div>
                                            </header>
                                            <p className="whitespace-pre-wrap">{note.content}</p>
                                        </article>
                                    ))
                                )}
                            </div>
                        </section>

                        <section className="rounded-xl border border-zinc-800 bg-zinc-950/60 p-6 shadow-inner shadow-black/40">
                            <header className="mb-4 flex items-center justify-between">
                                <div>
                                    <h2 className="text-lg font-semibold text-zinc-100">Dice log</h2>
                                    <p className="text-xs text-zinc-500">Roll directly from the workspace to share results.</p>
                                </div>
                            </header>

                            <form onSubmit={submitDice} className="flex flex-wrap items-end gap-3">
                                <div className="flex-1 min-w-[150px]">
                                    <Label htmlFor="dice-expression" className="text-xs uppercase tracking-wide text-zinc-500">
                                        Expression
                                    </Label>
                                    <Input
                                        id="dice-expression"
                                        value={diceForm.data.expression}
                                        onChange={(event) => diceForm.setData('expression', event.target.value)}
                                        placeholder="2d6+3"
                                        required
                                    />
                                    {diceForm.errors.expression && (
                                        <p className="text-sm text-rose-400">{diceForm.errors.expression}</p>
                                    )}
                                </div>

                                <Button type="submit" size="sm" disabled={diceForm.processing}>
                                    Roll
                                </Button>
                            </form>

                            <div className="mt-6 space-y-3">
                                {diceLog.length === 0 ? (
                                    <p className="text-sm text-zinc-500">No rolls yet. Cast the first die!</p>
                                ) : (
                                    diceLog.map((roll) => (
                                        <div
                                            key={roll.id}
                                            className="rounded-lg border border-zinc-800/80 bg-zinc-950/80 p-4 text-sm text-zinc-300"
                                        >
                                            <div className="flex items-start justify-between gap-3">
                                                <div>
                                                    <p className="font-medium text-zinc-100">
                                                        {roll.expression} → {roll.result_total}
                                                    </p>
                                                    <p className="text-xs text-zinc-500">
                                                        {diceBreakdown(roll)} • {roll.roller.name} • {formatDateTime(roll.created_at)}
                                                    </p>
                                                </div>
                                                {(permissions.can_manage || roll.roller.id === currentUserId) && (
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="sm"
                                                        className="text-xs text-rose-300 hover:text-rose-400"
                                                        onClick={() => handleDiceDelete(roll.id)}
                                                    >
                                                        Remove
                                                    </Button>
                                                )}
                                            </div>
                                        </div>
                                    ))
                                )}
                            </div>
                        </section>

                        <section className="rounded-xl border border-zinc-800 bg-zinc-950/60 p-6 shadow-inner shadow-black/40">
                            <header className="mb-4 flex items-center justify-between">
                                <div>
                                    <h2 className="text-lg font-semibold text-zinc-100">Attendance roster</h2>
                                    <p className="text-xs text-zinc-500">Signal your plans so the table can prepare.</p>
                                </div>
                            </header>

                            {permissions.can_rsvp ? (
                                <form onSubmit={submitAttendance} className="space-y-4">
                                    <div className="grid gap-2 sm:grid-cols-3">
                                        {attendanceOptions.map((option) => {
                                            const isActive = attendanceForm.data.status === option.value;

                                            return (
                                                <Button
                                                    key={option.value}
                                                    type="button"
                                                    size="sm"
                                                    variant={isActive ? 'default' : 'outline'}
                                                    className={`flex flex-col items-start gap-1 border-zinc-700 text-left transition ${
                                                        isActive
                                                            ? 'border-amber-500/60 bg-amber-500/20 text-amber-100'
                                                            : 'text-zinc-200 hover:text-amber-200'
                                                    }`}
                                                    disabled={attendanceForm.processing}
                                                    onClick={() => {
                                                        attendanceForm.setData('status', option.value);
                                                        attendanceForm.clearErrors('status');
                                                    }}
                                                >
                                                    <span className="font-semibold">{option.label}</span>
                                                    <span className="text-xs text-zinc-400">{option.description}</span>
                                                </Button>
                                            );
                                        })}
                                    </div>

                                    {attendanceForm.errors.status && (
                                        <p className="text-sm text-rose-400">{attendanceForm.errors.status}</p>
                                    )}

                                    <div className="grid gap-2">
                                        <Label htmlFor="attendance-note">Optional note</Label>
                                        <Textarea
                                            id="attendance-note"
                                            rows={2}
                                            value={attendanceForm.data.note}
                                            onChange={(event) => attendanceForm.setData('note', event.target.value)}
                                            placeholder="Share arrival plans, remote dial-in details, or requests."
                                        />
                                        {attendanceForm.errors.note && (
                                            <p className="text-sm text-rose-400">{attendanceForm.errors.note}</p>
                                        )}
                                    </div>

                                    <div className="flex flex-wrap items-center gap-3">
                                        <Button type="submit" size="sm" disabled={attendanceForm.processing}>
                                            {attendanceForm.processing ? 'Saving…' : 'Save RSVP'}
                                        </Button>
                                        {attendance.current_user && (
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                className="text-xs text-zinc-300 hover:text-amber-200"
                                                onClick={handleAttendanceClear}
                                                disabled={attendanceForm.processing}
                                            >
                                                Clear response
                                            </Button>
                                        )}
                                    </div>
                                </form>
                            ) : (
                                <p className="text-sm text-zinc-500">
                                    Only campaign members may RSVP to this session.
                                </p>
                            )}

                            <div className="mt-6 space-y-4">
                                <div>
                                    <h3 className="text-xs uppercase tracking-wide text-zinc-500">Party status</h3>
                                    <ul className="mt-2 space-y-1 text-sm text-zinc-300">
                                        {attendanceStatusOrder.map((status) => (
                                            <li
                                                key={status}
                                                className="flex items-center justify-between rounded-md border border-zinc-800/70 bg-zinc-950/70 px-3 py-2"
                                            >
                                                <span>{attendanceStatusLabels[status]}</span>
                                                <span className="font-semibold text-zinc-100">{attendance.counts[status]}</span>
                                            </li>
                                        ))}
                                    </ul>
                                </div>

                                <div>
                                    <h3 className="text-xs uppercase tracking-wide text-zinc-500">Responses</h3>
                                    <div className="mt-2 space-y-3 text-sm">
                                        {attendance.responses.length === 0 ? (
                                            <p className="text-zinc-500">No RSVPs yet. Share your plans!</p>
                                        ) : (
                                            attendance.responses.map((response) => (
                                                <div
                                                    key={response.id}
                                                    className={`rounded-lg border px-3 py-2 ${attendanceStatusStyles[response.status]}`}
                                                >
                                                    <div className="flex flex-wrap items-center justify-between gap-2">
                                                        <span className="font-medium">{response.user.name}</span>
                                                        <span className="text-xs uppercase tracking-wide text-zinc-200">
                                                            {attendanceStatusLabels[response.status]}
                                                        </span>
                                                    </div>
                                                    {response.note && (
                                                        <p className="mt-1 text-xs text-zinc-200">{response.note}</p>
                                                    )}
                                                    <p className="mt-1 text-[11px] text-zinc-400">
                                                        {formatDateTime(response.responded_at)}
                                                    </p>
                                                </div>
                                            ))
                                        )}
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>
                </section>

                <section className="rounded-xl border border-zinc-800 bg-zinc-950/60 p-6 shadow-inner shadow-black/40">
                    <header className="mb-4 flex items-center justify-between">
                        <div>
                            <h2 className="text-lg font-semibold text-zinc-100">Initiative tracker</h2>
                            <p className="text-xs text-zinc-500">Keep combat order crystal clear for the whole party.</p>
                        </div>
                    </header>

                    {permissions.can_manage && (
                        <form onSubmit={submitInitiative} className="space-y-3">
                            <div className="grid gap-2">
                                <Label htmlFor="initiative-name">Participant</Label>
                                <Input
                                    id="initiative-name"
                                    value={initiativeForm.data.name}
                                    onChange={(event) => initiativeForm.setData('name', event.target.value)}
                                    required
                                />
                                {initiativeForm.errors.name && (
                                    <p className="text-sm text-rose-400">{initiativeForm.errors.name}</p>
                                )}
                            </div>

                            <div className="flex flex-wrap gap-3">
                                <div className="flex-1 min-w-[120px]">
                                    <Label htmlFor="dexterity_mod" className="text-xs uppercase tracking-wide text-zinc-500">
                                        Dex mod
                                    </Label>
                                    <Input
                                        id="dexterity_mod"
                                        value={initiativeForm.data.dexterity_mod}
                                        onChange={(event) => initiativeForm.setData('dexterity_mod', event.target.value)}
                                    />
                                </div>
                                <div className="flex-1 min-w-[120px]">
                                    <Label htmlFor="initiative" className="text-xs uppercase tracking-wide text-zinc-500">
                                        Initiative (optional)
                                    </Label>
                                    <Input
                                        id="initiative"
                                        value={initiativeForm.data.initiative}
                                        onChange={(event) => initiativeForm.setData('initiative', event.target.value)}
                                        placeholder="Auto-roll if blank"
                                    />
                                </div>
                            </div>

                            <label className="flex items-center gap-2 text-xs uppercase tracking-wide text-zinc-500">
                                <Checkbox
                                    checked={initiativeForm.data.is_current}
                                    onChange={(event) => initiativeForm.setData('is_current', event.target.checked)}
                                />
                                Mark as current turn
                            </label>

                            <Button type="submit" size="sm" disabled={initiativeForm.processing}>
                                Add to order
                            </Button>
                        </form>
                    )}

                    <div className="mt-6 space-y-3">
                        {initiativeFeed.length === 0 ? (
                            <p className="text-sm text-zinc-500">No participants queued yet.</p>
                        ) : (
                            initiativeFeed.map((entry) => (
                                <div
                                    key={entry.id}
                                    className={`rounded-lg border p-4 text-sm ${
                                        entry.is_current
                                            ? 'border-amber-500/60 bg-amber-500/10 text-amber-200'
                                            : 'border-zinc-800/80 bg-zinc-950/80 text-zinc-300'
                                    }`}
                                >
                                    <div className="flex items-start justify-between gap-3">
                                        <div>
                                            <p className="font-semibold">{entry.name}</p>
                                            <p className="text-xs text-zinc-500">
                                                Initiative {entry.initiative} • Dex {entry.dexterity_mod >= 0 ? `+${entry.dexterity_mod}` : entry.dexterity_mod}
                                            </p>
                                        </div>
                                        {permissions.can_manage && (
                                            <div className="flex gap-2">
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    className="text-xs text-amber-200 hover:text-amber-300"
                                                    onClick={() => handleInitiativePromote(entry.id)}
                                                >
                                                    Set current
                                                </Button>
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    className="text-xs text-zinc-300 hover:text-amber-200"
                                                    onClick={() => handleInitiativeBump(entry, -1)}
                                                >
                                                    ▲
                                                </Button>
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    className="text-xs text-zinc-300 hover:text-amber-200"
                                                    onClick={() => handleInitiativeBump(entry, 1)}
                                                >
                                                    ▼
                                                </Button>
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    className="text-xs text-rose-300 hover:text-rose-400"
                                                    onClick={() => handleInitiativeDelete(entry.id)}
                                                >
                                                    Remove
                                                </Button>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            ))
                        )}
                    </div>
                </section>
            </div>

            <section className="mt-6 rounded-xl border border-zinc-800 bg-zinc-950/60 p-6 shadow-inner shadow-black/40">
                <header className="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                    <div>
                        <h2 className="text-lg font-semibold text-zinc-100">NPC guide (Gemma3)</h2>
                        <p className="text-xs text-zinc-500">
                            Summon a quick in-character response from the Ollama-powered narrator to keep scenes moving.
                        </p>
                    </div>
                </header>

                <form onSubmit={handleNpcSubmit} className="mt-4 grid gap-4 md:grid-cols-[220px,1fr]">
                    <div className="space-y-3">
                        <div className="space-y-2">
                            <Label htmlFor="npc-name" className="text-xs uppercase tracking-wide text-zinc-500">
                                NPC name
                            </Label>
                            <Input
                                id="npc-name"
                                value={npcForm.npcName}
                                onChange={(event) => setNpcForm((current) => ({ ...current, npcName: event.target.value }))}
                                placeholder="E.g., Captain Mirela"
                                required
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="npc-tone" className="text-xs uppercase tracking-wide text-zinc-500">
                                Tone (optional)
                            </Label>
                            <Input
                                id="npc-tone"
                                value={npcForm.tone}
                                onChange={(event) => setNpcForm((current) => ({ ...current, tone: event.target.value }))}
                                placeholder="Stoic, reverent, frantic..."
                            />
                        </div>
                        <Button type="submit" disabled={npcSubmitting}>
                            {npcSubmitting ? 'Consulting Gemma…' : 'Ask the NPC'}
                        </Button>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="npc-prompt" className="text-xs uppercase tracking-wide text-zinc-500">
                            Player prompt
                        </Label>
                        <Textarea
                            id="npc-prompt"
                            value={npcForm.prompt}
                            onChange={(event) => setNpcForm((current) => ({ ...current, prompt: event.target.value }))}
                            placeholder="Share what the players say or ask."
                            rows={6}
                            required
                        />
                    </div>
                </form>

                {npcError && <p className="mt-3 text-sm text-rose-300">{npcError}</p>}

                <div className="mt-6 space-y-4">
                    {npcDialogueLog.length === 0 ? (
                        <p className="text-sm text-zinc-500">
                            No NPC conversations yet. Summon a voice to guide your players.
                        </p>
                    ) : (
                        npcDialogueLog.map((entry) => (
                            <article
                                key={entry.id}
                                className="rounded-lg border border-zinc-800/80 bg-zinc-950/70 p-4 text-sm text-zinc-300"
                            >
                                <header className="mb-2 flex items-start justify-between gap-3">
                                    <div>
                                        <p className="font-semibold text-zinc-100">
                                            {entry.npc_name ?? 'Unnamed NPC'}
                                        </p>
                                        <p className="text-xs text-zinc-500">
                                            {formatDateTime(entry.created_at)}
                                            {entry.tone ? ` • Tone: ${entry.tone}` : ''}
                                        </p>
                                    </div>
                                    <span className="rounded-full bg-indigo-500/10 px-2 py-0.5 text-[11px] uppercase tracking-wide text-indigo-300">
                                        {entry.status}
                                    </span>
                                </header>
                                <p className="text-xs text-zinc-500">Player prompt: {entry.prompt}</p>
                                <p className="mt-3 whitespace-pre-wrap text-zinc-200">{entry.reply ?? 'Awaiting response...'}</p>
                            </article>
                        ))
                    )}
                </div>
            </section>
        </AppLayout>
    );
}
