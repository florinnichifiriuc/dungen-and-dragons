import { FormEvent } from 'react';

import { Head, Link, router, useForm, usePage } from '@inertiajs/react';

import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

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
    turn: { id: number; number: number; window_started_at: string | null } | null;
    creator: { id: number; name: string };
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

type SessionShowProps = {
    campaign: CampaignContext;
    session: SessionDetail;
    notes: SessionNoteResource[];
    dice_rolls: DiceRollResource[];
    initiative: InitiativeEntryResource[];
    note_visibilities: string[];
    permissions: {
        can_manage: boolean;
        can_delete: boolean;
    };
};

const visibilityLabels: Record<string, string> = {
    gm: 'GM only',
    players: 'Players',
    public: 'Public',
};

const defaultDiceExpression = '1d20';

export default function SessionShow({
    campaign,
    session,
    notes,
    dice_rolls: diceRolls,
    initiative,
    note_visibilities: noteVisibilities,
    permissions,
}: SessionShowProps) {
    const page = usePage();
    const currentUserId = (page.props.auth?.user?.id as number | undefined) ?? null;
    const defaultVisibility = noteVisibilities.includes('players')
        ? 'players'
        : noteVisibilities[0] ?? 'players';

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

                <div className="flex gap-3">
                    <Button variant="outline" asChild className="border-zinc-700 text-zinc-200 hover:text-amber-200">
                        <Link href={route('campaigns.sessions.index', { campaign: campaign.id })}>Back to sessions</Link>
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
                                <dd>
                                    {session.recording_url ? (
                                        <a
                                            href={session.recording_url}
                                            className="text-amber-300 underline decoration-dotted underline-offset-4"
                                            target="_blank"
                                            rel="noreferrer"
                                        >
                                            Open recording
                                        </a>
                                    ) : (
                                        'No recording yet'
                                    )}
                                </dd>
                            </div>
                        </dl>

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

                    <div className="grid gap-6 md:grid-cols-2">
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
                                {notes.length === 0 ? (
                                    <p className="text-sm text-zinc-500">No notes yet. Start chronicling the tale!</p>
                                ) : (
                                    notes.map((note) => (
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
                                {diceRolls.length === 0 ? (
                                    <p className="text-sm text-zinc-500">No rolls yet. Cast the first die!</p>
                                ) : (
                                    diceRolls.map((roll) => (
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
                        {initiative.length === 0 ? (
                            <p className="text-sm text-zinc-500">No participants queued yet.</p>
                        ) : (
                            initiative.map((entry) => (
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
        </AppLayout>
    );
}
