import { FormEvent, useMemo, useState } from 'react';

import { Head, Link, router, usePage } from '@inertiajs/react';

import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type CampaignResult = {
    id: number;
    title: string;
    status: string;
    updated_at: string | null;
    group: { id: number; name: string };
    region: { id: number; name: string } | null;
};

type SessionResult = {
    id: number;
    title: string;
    session_date: string | null;
    campaign: { id: number; title: string };
};

type NoteResult = {
    id: number;
    visibility: string;
    content_preview: string;
    updated_at: string | null;
    campaign: { id: number; title: string };
    session: { id: number; title: string } | null;
    author: { id: number | null; name: string | null };
};

type TaskResult = {
    id: number;
    title: string;
    status: string;
    due_turn_number: number | null;
    due_at: string | null;
    campaign: { id: number; title: string };
};

type SearchPageProps = {
    query: string;
    active_scopes: string[];
    available_scopes: string[];
    results: {
        campaigns: CampaignResult[];
        sessions: SessionResult[];
        notes: NoteResult[];
        tasks: TaskResult[];
    };
};

const scopeLabels: Record<string, string> = {
    campaigns: 'Campaigns',
    sessions: 'Sessions',
    notes: 'Session notes',
    tasks: 'Tasks',
};

function formatDateTime(value: string | null): string {
    if (!value) {
        return '—';
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

export default function SearchIndex() {
    const { query, active_scopes, available_scopes, results } = usePage<SearchPageProps>().props;
    const [term, setTerm] = useState(query ?? '');
    const [scopes, setScopes] = useState<string[]>(active_scopes ?? []);

    const activeResultsCount = useMemo(() => {
        return scopes.reduce((count, scope) => count + (results[scope as keyof typeof results]?.length ?? 0), 0);
    }, [results, scopes]);

    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        router.get(
            route('search.index'),
            { q: term, scopes },
            {
                preserveState: true,
                preserveScroll: true,
            }
        );
    };

    const toggleScope = (scope: string) => {
        setScopes((current) => {
            if (current.includes(scope)) {
                return current.filter((value) => value !== scope);
            }

            return [...current, scope];
        });
    };

    return (
        <AppLayout>
            <Head title="Search" />

            <section className="space-y-8">
                <header className="space-y-2">
                    <h1 className="text-3xl font-semibold text-zinc-100">Lorekeeper search</h1>
                    <p className="max-w-3xl text-sm text-zinc-400">
                        Find campaigns, sessions, tasks, and notes you have access to across every realm you steward. Filters let
                        you focus on specific record types when hunting for a detail mid-session.
                    </p>
                </header>

                <form onSubmit={handleSubmit} className="grid gap-6 rounded-xl border border-zinc-800 bg-zinc-950/60 p-6">
                    <div className="space-y-2">
                        <Label htmlFor="search-term">Search phrase</Label>
                        <Input
                            id="search-term"
                            value={term}
                            onChange={(event) => setTerm(event.target.value)}
                            placeholder="Search lore, campaigns, or assignments..."
                            autoFocus
                        />
                    </div>

                    <fieldset className="space-y-3">
                        <legend className="text-sm font-medium text-zinc-200">Scopes</legend>
                        <div className="flex flex-wrap gap-4">
                            {available_scopes.map((scope) => (
                                <label key={scope} className="flex items-center gap-2 text-sm text-zinc-300">
                                    <Checkbox
                                        checked={scopes.includes(scope)}
                                        onChange={() => toggleScope(scope)}
                                        name="scopes[]"
                                        value={scope}
                                    />
                                    {scopeLabels[scope] ?? scope}
                                </label>
                            ))}
                        </div>
                    </fieldset>

                    <div className="flex items-center gap-3">
                        <Button type="submit" disabled={term.trim() === ''}>
                            Search archives
                        </Button>
                        {term.trim() !== '' && (
                            <p className="text-sm text-zinc-400">
                                {activeResultsCount} match{activeResultsCount === 1 ? '' : 'es'} across selected scopes
                            </p>
                        )}
                    </div>
                </form>

                {term.trim() === '' ? (
                    <div className="rounded-xl border border-dashed border-zinc-800 bg-zinc-950/40 p-10 text-center text-sm text-zinc-400">
                        Enter a phrase above to begin searching your worlds.
                    </div>
                ) : (
                    <div className="space-y-6">
                        {scopes.map((scope) => {
                            const scopeResults = results[scope as keyof typeof results] ?? [];

                            if (scopeResults.length === 0) {
                                return (
                                    <section key={scope} className="rounded-xl border border-dashed border-zinc-800 p-6 text-sm text-zinc-400">
                                        <h2 className="text-lg font-semibold text-zinc-200">{scopeLabels[scope] ?? scope}</h2>
                                        <p className="mt-3 text-zinc-500">No matches within this scope.</p>
                                    </section>
                                );
                            }

                            if (scope === 'campaigns') {
                                return (
                                    <section key={scope} className="space-y-4">
                                        <h2 className="text-lg font-semibold text-zinc-200">Campaigns</h2>
                                        <div className="grid gap-4 md:grid-cols-2">
                                            {(scopeResults as CampaignResult[]).map((campaign) => (
                                                <article
                                                    key={campaign.id}
                                                    className="rounded-xl border border-zinc-800 bg-zinc-950/60 p-5 shadow-inner shadow-black/40"
                                                >
                                                    <div className="flex items-start justify-between gap-4">
                                                        <div>
                                                            <h3 className="text-xl font-semibold text-zinc-100">{campaign.title}</h3>
                                                            <p className="text-sm text-zinc-400">Status: {campaign.status}</p>
                                                            <p className="text-xs text-zinc-500">
                                                                {campaign.group.name}
                                                                {campaign.region ? ` • ${campaign.region.name}` : ''}
                                                            </p>
                                                        </div>
                                                        <span className="rounded-full bg-amber-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-amber-300">
                                                            Updated {formatDateTime(campaign.updated_at)}
                                                        </span>
                                                    </div>
                                                    <Button
                                                        asChild
                                                        variant="outline"
                                                        size="sm"
                                                        className="mt-5 border-zinc-700 text-zinc-200 hover:text-amber-200"
                                                    >
                                                        <Link href={route('campaigns.show', campaign.id)}>Open campaign</Link>
                                                    </Button>
                                                </article>
                                            ))}
                                        </div>
                                    </section>
                                );
                            }

                            if (scope === 'sessions') {
                                return (
                                    <section key={scope} className="space-y-4">
                                        <h2 className="text-lg font-semibold text-zinc-200">Sessions</h2>
                                        <div className="grid gap-4 md:grid-cols-2">
                                            {(scopeResults as SessionResult[]).map((session) => (
                                                <article
                                                    key={session.id}
                                                    className="rounded-xl border border-zinc-800 bg-zinc-950/60 p-5 shadow-inner shadow-black/40"
                                                >
                                                    <header className="flex items-start justify-between gap-3">
                                                        <div>
                                                            <h3 className="text-lg font-semibold text-zinc-100">{session.title}</h3>
                                                            <p className="text-sm text-zinc-400">{session.campaign.title}</p>
                                                        </div>
                                                        <span className="rounded-md bg-indigo-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-indigo-200">
                                                            {formatDateTime(session.session_date)}
                                                        </span>
                                                    </header>
                                                    <Button
                                                        asChild
                                                        variant="outline"
                                                        size="sm"
                                                        className="mt-5 border-zinc-700 text-zinc-200 hover:text-amber-200"
                                                    >
                                                        <Link
                                                            href={route('campaigns.sessions.show', {
                                                                campaign: session.campaign.id,
                                                                session: session.id,
                                                            })}
                                                        >
                                                            Jump to workspace
                                                        </Link>
                                                    </Button>
                                                </article>
                                            ))}
                                        </div>
                                    </section>
                                );
                            }

                            if (scope === 'notes') {
                                return (
                                    <section key={scope} className="space-y-4">
                                        <h2 className="text-lg font-semibold text-zinc-200">Session notes</h2>
                                        <div className="space-y-3">
                                            {(scopeResults as NoteResult[]).map((note) => (
                                                <article
                                                    key={note.id}
                                                    className="rounded-xl border border-zinc-800 bg-zinc-950/60 p-5 shadow-inner shadow-black/40"
                                                >
                                                    <div className="flex flex-col gap-2 text-sm text-zinc-300">
                                                        <div className="flex flex-wrap items-center gap-2 text-xs uppercase tracking-wide text-zinc-500">
                                                            <span className="rounded-full bg-zinc-800/80 px-2 py-0.5 text-zinc-300">
                                                                {note.visibility === 'gm' ? 'GM only' : note.visibility === 'public' ? 'Public' : 'Players'}
                                                            </span>
                                                            <span>{formatDateTime(note.updated_at)}</span>
                                                            {note.author.name && <span>by {note.author.name}</span>}
                                                        </div>
                                                        <p className="text-sm text-zinc-200">{note.content_preview}</p>
                                                        <p className="text-xs text-zinc-500">
                                                            {note.campaign.title}
                                                            {note.session ? ` • ${note.session.title}` : ''}
                                                        </p>
                                                    </div>
                                                    <Button
                                                        asChild
                                                        variant="outline"
                                                        size="sm"
                                                        className="mt-5 border-zinc-700 text-zinc-200 hover:text-amber-200"
                                                    >
                                                        <Link
                                                            href={note.session
                                                                ? route('campaigns.sessions.show', {
                                                                      campaign: note.campaign.id,
                                                                      session: note.session.id,
                                                                  })
                                                                : route('campaigns.show', note.campaign.id)}
                                                        >
                                                            View in context
                                                        </Link>
                                                    </Button>
                                                </article>
                                            ))}
                                        </div>
                                    </section>
                                );
                            }

                            if (scope === 'tasks') {
                                return (
                                    <section key={scope} className="space-y-4">
                                        <h2 className="text-lg font-semibold text-zinc-200">Tasks</h2>
                                        <div className="grid gap-4 md:grid-cols-2">
                                            {(scopeResults as TaskResult[]).map((task) => (
                                                <article
                                                    key={task.id}
                                                    className="rounded-xl border border-zinc-800 bg-zinc-950/60 p-5 shadow-inner shadow-black/40"
                                                >
                                                    <header className="flex items-start justify-between gap-3">
                                                        <div>
                                                            <h3 className="text-lg font-semibold text-zinc-100">{task.title}</h3>
                                                            <p className="text-sm text-zinc-400">{task.campaign.title}</p>
                                                        </div>
                                                        <span className="rounded-full bg-emerald-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-emerald-300">
                                                            {task.status}
                                                        </span>
                                                    </header>
                                                    <dl className="mt-3 space-y-2 text-xs text-zinc-400">
                                                        <div className="flex items-center justify-between">
                                                            <dt>Due turn</dt>
                                                            <dd>{task.due_turn_number ?? '—'}</dd>
                                                        </div>
                                                        <div className="flex items-center justify-between">
                                                            <dt>Due at</dt>
                                                            <dd>{formatDateTime(task.due_at)}</dd>
                                                        </div>
                                                    </dl>
                                                    <Button
                                                        asChild
                                                        variant="outline"
                                                        size="sm"
                                                        className="mt-5 border-zinc-700 text-zinc-200 hover:text-amber-200"
                                                    >
                                                        <Link href={route('campaigns.tasks.index', task.campaign.id)}>Open task board</Link>
                                                    </Button>
                                                </article>
                                            ))}
                                        </div>
                                    </section>
                                );
                            }

                            return null;
                        })}
                    </div>
                )}
            </section>
        </AppLayout>
    );
}
