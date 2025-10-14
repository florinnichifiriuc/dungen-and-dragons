import { Head, Link } from '@inertiajs/react';

import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';

type CampaignSummary = {
    id: number;
    title: string;
    status: string;
    group: { id: number; name: string };
    region: { id: number; name: string } | null;
};

type CampaignIndexProps = {
    campaigns: CampaignSummary[];
};

const statusLabels: Record<string, string> = {
    planning: 'Planning',
    active: 'Active',
    completed: 'Completed',
    archived: 'Archived',
};

export default function CampaignIndex({ campaigns }: CampaignIndexProps) {
    return (
        <AppLayout>
            <Head title="Campaigns" />

            <div className="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 className="text-3xl font-semibold text-zinc-100">Campaign compendium</h1>
                    <p className="mt-2 max-w-2xl text-sm text-zinc-400">
                        Track every ongoing saga across your worlds. Assign dungeon masters, manage regions, and keep turns flowing.
                    </p>
                </div>

                <Button asChild>
                    <Link href={route('campaigns.create')}>Launch new campaign</Link>
                </Button>
            </div>

            {campaigns.length === 0 ? (
                <div className="rounded-xl border border-dashed border-zinc-800 bg-zinc-950/50 p-8 text-center text-sm text-zinc-400">
                    No campaigns yet. Gather your party and create a new arc to begin the chronicle.
                </div>
            ) : (
                <div className="grid gap-4 md:grid-cols-2">
                    {campaigns.map((campaign) => (
                        <article key={campaign.id} className="rounded-xl border border-zinc-800 bg-zinc-950/60 p-6 shadow-inner shadow-black/40">
                            <header className="flex items-start justify-between gap-4">
                                <div>
                                    <h2 className="text-xl font-semibold text-zinc-100">{campaign.title}</h2>
                                    <p className="text-sm text-zinc-400">{campaign.group.name}</p>
                                </div>
                                <span className="rounded-full bg-amber-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-amber-300">
                                    {statusLabels[campaign.status] ?? campaign.status}
                                </span>
                            </header>
                            <dl className="mt-4 grid gap-3 text-sm text-zinc-400">
                                <div>
                                    <dt className="text-xs uppercase tracking-wide text-zinc-500">Region focus</dt>
                                    <dd>{campaign.region ? campaign.region.name : 'Unassigned'}</dd>
                                </div>
                            </dl>
                            <Button asChild variant="outline" size="sm" className="mt-6 border-zinc-700">
                                <Link href={route('campaigns.show', campaign.id)}>Open dashboard</Link>
                            </Button>
                        </article>
                    ))}
                </div>
            )}
        </AppLayout>
    );
}
