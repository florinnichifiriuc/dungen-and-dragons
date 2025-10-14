import { Head, Link } from '@inertiajs/react';

import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';

type SessionSummary = {
    id: number;
    title: string;
    session_date: string | null;
    location: string | null;
    notes_count: number;
};

type CampaignContext = {
    id: number;
    title: string;
};

type SessionIndexProps = {
    campaign: CampaignContext;
    sessions: SessionSummary[];
};

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

export default function SessionIndex({ campaign, sessions }: SessionIndexProps) {
    return (
        <AppLayout>
            <Head title={`${campaign.title} sessions`} />

            <div className="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 className="text-3xl font-semibold text-zinc-100">{campaign.title} sessions</h1>
                    <p className="mt-2 max-w-2xl text-sm text-zinc-400">
                        Chronicle every gathering, prepare agendas, and log the dice results from your table.
                    </p>
                </div>

                <Button asChild>
                    <Link href={route('campaigns.sessions.create', { campaign: campaign.id })}>Schedule session</Link>
                </Button>
            </div>

            {sessions.length === 0 ? (
                <div className="rounded-xl border border-dashed border-zinc-800 bg-zinc-950/40 p-8 text-center text-sm text-zinc-400">
                    No sessions recorded yet. Plan your next adventure to get started.
                </div>
            ) : (
                <div className="grid gap-4 md:grid-cols-2">
                    {sessions.map((session) => (
                        <article
                            key={session.id}
                            className="rounded-xl border border-zinc-800 bg-zinc-950/70 p-6 shadow-inner shadow-black/40"
                        >
                            <header className="flex items-start justify-between gap-4">
                                <div>
                                    <h2 className="text-xl font-semibold text-zinc-100">{session.title}</h2>
                                    <p className="text-sm text-zinc-400">{formatDateTime(session.session_date)}</p>
                                </div>
                                <span className="rounded-full bg-amber-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-amber-300">
                                    {session.notes_count} notes
                                </span>
                            </header>
                            <dl className="mt-4 grid gap-3 text-sm text-zinc-400">
                                <div>
                                    <dt className="text-xs uppercase tracking-wide text-zinc-500">Location</dt>
                                    <dd>{session.location ?? 'Unspecified'}</dd>
                                </div>
                            </dl>
                            <Button
                                asChild
                                variant="outline"
                                size="sm"
                                className="mt-6 w-full border-zinc-700 text-zinc-200 hover:text-amber-200"
                            >
                                <Link href={route('campaigns.sessions.show', { campaign: campaign.id, session: session.id })}>
                                    Open workspace
                                </Link>
                            </Button>
                        </article>
                    ))}
                </div>
            )}
        </AppLayout>
    );
}
