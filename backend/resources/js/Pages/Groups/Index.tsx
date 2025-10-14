import { Head, Link } from '@inertiajs/react';

import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';

type GroupSummary = {
    id: number;
    name: string;
    slug: string;
    member_count: number;
};

type GroupsIndexProps = {
    groups: GroupSummary[];
};

export default function GroupsIndex({ groups }: GroupsIndexProps) {
    return (
        <AppLayout>
            <Head title="Groups" />

            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-3xl font-semibold text-zinc-100">Adventuring parties</h1>
                    <p className="mt-2 text-sm text-zinc-400">
                        Organize players, dungeon masters, and the worlds they explore.
                    </p>
                </div>

                <Button asChild>
                    <Link href={route('groups.create')}>Create group</Link>
                </Button>
            </div>

            <div className="mt-8 grid gap-4 sm:grid-cols-2">
                {groups.length === 0 ? (
                    <p className="col-span-full rounded-xl border border-dashed border-zinc-800 bg-zinc-950/60 p-6 text-sm text-zinc-400">
                        No groups yet. Summon a new party to begin planning your next adventure.
                    </p>
                ) : (
                    groups.map((group) => (
                        <Link
                            key={group.id}
                            href={route('groups.show', group.id)}
                            className="group rounded-xl border border-zinc-800 bg-zinc-900/70 p-6 transition hover:border-indigo-500/70 hover:shadow-lg hover:shadow-indigo-500/10"
                        >
                            <div className="flex items-start justify-between">
                                <div>
                                    <h2 className="text-lg font-semibold text-zinc-100 group-hover:text-indigo-200">
                                        {group.name}
                                    </h2>
                                    <p className="mt-1 text-xs uppercase tracking-wide text-zinc-500">/{group.slug}</p>
                                </div>
                                <span className="rounded-full bg-indigo-500/10 px-3 py-1 text-xs font-medium text-indigo-300">
                                    {group.member_count} members
                                </span>
                            </div>
                        </Link>
                    ))
                )}
            </div>
        </AppLayout>
    );
}
