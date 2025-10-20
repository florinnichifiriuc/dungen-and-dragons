import { Head, Link, usePage } from '@inertiajs/react';
import type { PageProps as InertiaPageProps } from '@inertiajs/core';

import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';

type DashboardPageProps = InertiaPageProps & {
    permissions?: {
        can_view_campaigns?: boolean;
        can_view_groups?: boolean;
    };
};

export default function Dashboard() {
    const { props } = usePage<DashboardPageProps>();

    const canViewCampaigns = props.permissions?.can_view_campaigns ?? false;
    const canViewGroups = props.permissions?.can_view_groups ?? false;
    const user = props.auth?.user ?? null;
    const accountRole = user?.account_role ?? 'player';
    const isAdmin = accountRole === 'admin' || Boolean(user?.is_support_admin);

    return (
        <AppLayout>
            <Head title="Dashboard" />

            <section className="space-y-6">
                <header>
                    <h1 className="text-3xl font-semibold">Campaign control center</h1>
                    <p className="mt-2 text-base text-zinc-400">Monitor worlds, upcoming turns, and outstanding tasks from one timeline-aware view.</p>
                </header>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <article className="rounded-xl border border-amber-500/30 bg-amber-500/10 p-6 text-amber-100 shadow-inner shadow-amber-900/30">
                        <h2 className="text-lg font-semibold">Your mantle</h2>
                        <p className="mt-2 text-sm text-amber-100/80">
                            You&apos;re signed in as a <span className="font-semibold lowercase">{accountRole}</span>. This mantle controls which panels appear across the realm.
                        </p>
                        <div className="mt-4 flex flex-wrap items-center gap-3">
                            <Badge variant="outline" className="border-amber-300/60 bg-transparent text-xs uppercase tracking-wide text-amber-100">
                                {accountRole}
                            </Badge>
                            {user?.is_support_admin && (
                                <Badge variant="secondary" className="bg-indigo-500/20 text-indigo-100">
                                    Support admin
                                </Badge>
                            )}
                        </div>
                        {isAdmin && (
                            <Button
                                asChild
                                size="sm"
                                variant="outline"
                                className="mt-4 border-amber-400/60 text-amber-100 hover:bg-amber-500/20"
                            >
                                <Link href={route('admin.users.index')}>Open role management</Link>
                            </Button>
                        )}
                    </article>
                    <article className="rounded-xl border border-zinc-800 bg-zinc-900/70 p-6 shadow-inner shadow-black/40">
                        <h2 className="text-lg font-semibold text-zinc-100">Next scheduled turn</h2>
                        <p className="mt-2 text-sm text-zinc-400">Turn processing automation arrives in Task 5. Configure durations from campaign settings.</p>
                        <Button variant="outline" size="sm" className="mt-4">View scheduler</Button>
                    </article>
                    {canViewCampaigns && (
                        <article className="rounded-xl border border-zinc-800 bg-zinc-900/70 p-6 shadow-inner shadow-black/40">
                            <h2 className="text-lg font-semibold text-zinc-100">Campaigns</h2>
                            <p className="mt-2 text-sm text-zinc-400">Spin up new arcs, manage invitations, and steer narrative cadence.</p>
                            <Button asChild variant="outline" size="sm" className="mt-4">
                                <Link href={route('campaigns.index')}>Manage campaigns</Link>
                            </Button>
                        </article>
                    )}
                    {canViewGroups && (
                        <article className="rounded-xl border border-zinc-800 bg-zinc-900/70 p-6 shadow-inner shadow-black/40">
                            <h2 className="text-lg font-semibold text-zinc-100">Groups & regions</h2>
                            <p className="mt-2 text-sm text-zinc-400">Invite new parties, assign dungeon masters, and configure turn cadence for each realm.</p>
                            <Button asChild variant="outline" size="sm" className="mt-4">
                                <Link href={route('groups.index')}>Open groups</Link>
                            </Button>
                        </article>
                    )}
                    <article className="rounded-xl border border-zinc-800 bg-zinc-900/70 p-6 shadow-inner shadow-black/40">
                        <h2 className="text-lg font-semibold text-zinc-100">Session log</h2>
                        <p className="mt-2 text-sm text-zinc-400">Live workspace with notes, initiative, dice, and map uploads lands in Week 3.</p>
                        <Button variant="outline" size="sm" className="mt-4">Review roadmap</Button>
                    </article>
                </div>
            </section>
        </AppLayout>
    );
}
