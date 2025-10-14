import { Head, Link } from '@inertiajs/react';

import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';

export default function Dashboard() {
    return (
        <AppLayout>
            <Head title="Dashboard" />

            <section className="space-y-6">
                <header>
                    <h1 className="text-3xl font-semibold">Campaign control center</h1>
                    <p className="mt-2 text-base text-zinc-400">Monitor worlds, upcoming turns, and outstanding tasks from one timeline-aware view.</p>
                </header>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <article className="rounded-xl border border-zinc-800 bg-zinc-900/70 p-6 shadow-inner shadow-black/40">
                        <h2 className="text-lg font-semibold text-zinc-100">Next scheduled turn</h2>
                        <p className="mt-2 text-sm text-zinc-400">Turn processing automation arrives in Task 5. Configure durations from campaign settings.</p>
                        <Button variant="outline" size="sm" className="mt-4">View scheduler</Button>
                    </article>
                    <article className="rounded-xl border border-zinc-800 bg-zinc-900/70 p-6 shadow-inner shadow-black/40">
                        <h2 className="text-lg font-semibold text-zinc-100">Groups & regions</h2>
                        <p className="mt-2 text-sm text-zinc-400">Invite new parties, assign dungeon masters, and configure turn cadence for each realm.</p>
                        <Button asChild variant="outline" size="sm" className="mt-4">
                            <Link href={route('groups.index')}>Open groups</Link>
                        </Button>
                    </article>
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
