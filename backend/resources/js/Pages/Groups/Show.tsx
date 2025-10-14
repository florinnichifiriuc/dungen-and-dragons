import { Head, Link } from '@inertiajs/react';

import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';

type GroupMember = {
    id: number;
    name: string;
    email: string;
    role: string;
};

type RegionSummary = {
    id: number;
    name: string;
    summary: string | null;
    dungeon_master: { id: number; name: string } | null;
    turn_configuration: {
        turn_duration_hours: number;
        next_turn_at: string | null;
        last_processed_at: string | null;
        is_due: boolean;
    } | null;
    recent_turns: {
        id: number;
        number: number;
        processed_at: string | null;
        summary: string | null;
        used_ai_fallback: boolean;
        processed_by: { id: number; name: string } | null;
    }[];
    can_process_turn: boolean;
};

type GroupPayload = {
    id: number;
    name: string;
    description: string | null;
    members: GroupMember[];
    regions: RegionSummary[];
    campaigns: { id: number; title: string; status: string }[];
};

type GroupShowProps = {
    group: GroupPayload;
};

const roleLabels: Record<string, string> = {
    owner: 'Game Master',
    'dungeon-master': 'Dungeon Master',
    player: 'Adventurer',
};

export default function GroupShow({ group }: GroupShowProps) {
    return (
        <AppLayout>
            <Head title={group.name} />

            <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 className="text-3xl font-semibold text-zinc-100">{group.name}</h1>
                    {group.description ? (
                        <p className="mt-2 max-w-3xl text-sm text-zinc-400">{group.description}</p>
                    ) : (
                        <p className="mt-2 text-sm text-zinc-500">No primer yet. Share the party&apos;s legend soon.</p>
                    )}
                </div>

                <div className="flex items-center gap-3">
                    <Button asChild variant="outline" className="border-zinc-700 text-sm">
                        <Link href={route('groups.edit', group.id)}>Edit group</Link>
                    </Button>
                    <Button asChild>
                        <Link href={route('groups.regions.create', group.id)}>Assign region</Link>
                    </Button>
                </div>
            </div>

            <div className="mt-10 grid gap-8 lg:grid-cols-2">
                <section className="rounded-xl border border-zinc-800 bg-zinc-950/60 p-6 shadow-inner shadow-black/40">
                    <header className="flex items-center justify-between">
                        <h2 className="text-lg font-semibold text-zinc-100">Party roster</h2>
                        <span className="text-xs uppercase tracking-wide text-zinc-500">{group.members.length} members</span>
                    </header>

                    <ul className="mt-4 space-y-3">
                        {group.members.map((member) => (
                            <li
                                key={member.id}
                                className="flex items-center justify-between rounded-lg border border-zinc-800 bg-zinc-900/60 px-4 py-3"
                            >
                                <div>
                                    <p className="font-medium text-zinc-100">{member.name}</p>
                                    <p className="text-xs text-zinc-500">{member.email}</p>
                                </div>
                                <span className="text-xs font-semibold uppercase tracking-wide text-indigo-300">
                                    {roleLabels[member.role] ?? member.role}
                                </span>
                            </li>
                        ))}
                    </ul>
                </section>

                <section className="rounded-xl border border-zinc-800 bg-zinc-950/60 p-6 shadow-inner shadow-black/40">
                    <header className="flex items-center justify-between">
                        <h2 className="text-lg font-semibold text-zinc-100">World regions</h2>
                        <span className="text-xs uppercase tracking-wide text-zinc-500">{group.regions.length} tracked</span>
                    </header>

                    <div className="mt-4 space-y-4">
                        {group.regions.length === 0 ? (
                            <p className="rounded-lg border border-dashed border-zinc-800 bg-zinc-900/40 p-4 text-sm text-zinc-400">
                                No regions assigned yet. Deploy a DM to begin mapping encounters and turn cadence.
                            </p>
                        ) : (
                            group.regions.map((region) => (
                                <article key={region.id} id={`region-${region.id}`} className="rounded-lg border border-zinc-800 bg-zinc-900/60 p-4">
                                        <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between sm:gap-4">
                                            <div>
                                                <h3 className="text-base font-semibold text-zinc-100">{region.name}</h3>
                                                {region.summary && <p className="mt-1 text-sm text-zinc-400">{region.summary}</p>}
                                            </div>
                                            <div className="flex items-center gap-2">
                                                <Button asChild variant="outline" size="sm" className="border-zinc-700 text-xs">
                                                    <Link href={route('groups.regions.edit', [group.id, region.id])}>Configure</Link>
                                                </Button>
                                                {region.can_process_turn && (
                                                    <Button asChild size="sm" className="text-xs">
                                                        <Link href={route('groups.regions.turns.create', [group.id, region.id])}>
                                                            Process turn
                                                        </Link>
                                                    </Button>
                                                )}
                                            </div>
                                        </div>

                                        <dl className="mt-3 grid gap-2 text-sm text-zinc-400 sm:grid-cols-2">
                                            <div>
                                                <dt className="text-xs uppercase tracking-wide text-zinc-500">Dungeon master</dt>
                                                <dd>{region.dungeon_master ? region.dungeon_master.name : 'Unassigned'}</dd>
                                            </div>
                                            <div>
                                                <dt className="text-xs uppercase tracking-wide text-zinc-500">Turn cadence</dt>
                                                <dd>
                                                    {region.turn_configuration
                                                        ? `${region.turn_configuration.turn_duration_hours}h`
                                                        : 'Not configured'}
                                                </dd>
                                            </div>
                                            <div className="sm:col-span-2">
                                                <dt className="text-xs uppercase tracking-wide text-zinc-500">Next scheduled turn</dt>
                                                <dd>
                                                    {region.turn_configuration?.next_turn_at
                                                        ? new Date(region.turn_configuration.next_turn_at).toLocaleString()
                                                        : 'Awaiting schedule'}
                                                </dd>
                                            </div>
                                            <div className="sm:col-span-2">
                                                <dt className="text-xs uppercase tracking-wide text-zinc-500">Status</dt>
                                                <dd>
                                                    {region.turn_configuration
                                                        ? region.turn_configuration.is_due
                                                            ? 'Turn ready for processing'
                                                            : `Advances ${region.turn_configuration.next_turn_at ? new Date(region.turn_configuration.next_turn_at).toLocaleString() : ''}`
                                                        : 'Configure cadence to begin scheduling'}
                                                </dd>
                                            </div>
                                            <div className="sm:col-span-2">
                                                <dt className="text-xs uppercase tracking-wide text-zinc-500">Last processed</dt>
                                                <dd>
                                                    {region.turn_configuration?.last_processed_at
                                                        ? new Date(region.turn_configuration.last_processed_at).toLocaleString()
                                                        : 'No turns processed yet'}
                                                </dd>
                                            </div>
                                        </dl>

                                        {region.recent_turns.length > 0 && (
                                            <div className="mt-4 space-y-2">
                                                <h4 className="text-xs font-semibold uppercase tracking-wide text-zinc-500">
                                                    Recent turns
                                                </h4>
                                                <ul className="space-y-2 text-sm text-zinc-300">
                                                    {region.recent_turns.map((turn) => (
                                                        <li
                                                            key={turn.id}
                                                            className="rounded-lg border border-zinc-800 bg-zinc-900/40 p-3"
                                                        >
                                                            <div className="flex flex-wrap items-center justify-between gap-2 text-xs text-zinc-400">
                                                                <span className="font-semibold text-zinc-200">Turn #{turn.number}</span>
                                                                {turn.processed_at && (
                                                                    <span>{new Date(turn.processed_at).toLocaleString()}</span>
                                                                )}
                                                            </div>
                                                            {turn.summary ? (
                                                                <p className="mt-2 text-sm text-zinc-300">{turn.summary}</p>
                                                            ) : (
                                                                <p className="mt-2 text-sm text-zinc-500">
                                                                    Awaiting summary details.
                                                                </p>
                                                            )}
                                                            <div className="mt-2 flex flex-wrap items-center gap-3 text-xs text-zinc-500">
                                                                <span>
                                                                    Processed by{' '}
                                                                    {turn.processed_by
                                                                        ? turn.processed_by.name
                                                                        : turn.used_ai_fallback
                                                                        ? 'AI delegate'
                                                                        : 'Unknown'}
                                                                </span>
                                                                {turn.used_ai_fallback && (
                                                                    <span className="rounded bg-indigo-500/10 px-2 py-0.5 text-indigo-300">
                                                                        AI fallback
                                                                    </span>
                                                                )}
                                                            </div>
                                                        </li>
                                                    ))}
                                                </ul>
                                            </div>
                                        )}
                                    </article>
                                ))
                            )}
                        </div>
                </section>

                <section className="rounded-xl border border-zinc-800 bg-zinc-950/60 p-6 shadow-inner shadow-black/40 lg:col-span-2">
                    <header className="flex items-center justify-between">
                        <h2 className="text-lg font-semibold text-zinc-100">Campaign arcs</h2>
                        <span className="text-xs uppercase tracking-wide text-zinc-500">{group.campaigns.length} active</span>
                    </header>

                    <div className="mt-4 grid gap-4 md:grid-cols-2">
                        {group.campaigns.length === 0 ? (
                            <p className="rounded-lg border border-dashed border-zinc-800 bg-zinc-900/40 p-4 text-sm text-zinc-400">
                                No campaigns launched yet. Spin up a new saga to rally your adventurers.
                            </p>
                        ) : (
                            group.campaigns.map((campaign) => (
                                <article key={campaign.id} className="rounded-lg border border-zinc-800 bg-zinc-900/60 p-4">
                                    <div className="flex items-center justify-between">
                                        <div>
                                            <h3 className="text-base font-semibold text-zinc-100">{campaign.title}</h3>
                                            <p className="text-xs uppercase tracking-wide text-indigo-300">{campaign.status}</p>
                                        </div>
                                        <Button asChild variant="outline" size="sm" className="border-zinc-700 text-xs">
                                            <Link href={route('campaigns.show', campaign.id)}>View</Link>
                                        </Button>
                                    </div>
                                </article>
                            ))
                        )}
                    </div>
                </section>
            </div>
        </AppLayout>
    );
}
