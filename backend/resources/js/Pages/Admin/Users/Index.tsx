import { useState } from 'react';

import { Head, Link, router, usePage } from '@inertiajs/react';

import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Badge } from '@/components/ui/badge';
import { formatTimestamp } from '@/lib/utils';

type UserSummary = {
    id: number;
    name: string;
    email: string;
    is_support_admin: boolean;
    created_at: string | null;
};

type PageProps = {
    users: UserSummary[];
};

export default function AdminUserIndex() {
    const { users } = usePage<PageProps>().props;
    const [updating, setUpdating] = useState<number | null>(null);

    const handleToggle = (user: UserSummary, next: boolean) => {
        setUpdating(user.id);

        router.patch(
            route('admin.users.update', user.id),
            { is_support_admin: next },
            {
                preserveScroll: true,
                onFinish: () => setUpdating(null),
            }
        );
    };

    return (
        <AppLayout>
            <Head title="Support administration" />

            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 className="text-3xl font-semibold text-zinc-100">Support administration</h1>
                    <p className="text-sm text-zinc-400">
                        Promote facilitators into support admins so they can triage bug reports and moderate AI outputs.
                    </p>
                </div>
                <Button asChild variant="outline" className="border-zinc-700 text-sm">
                    <Link href={route('admin.bug-reports.index')}>Bug reports</Link>
                </Button>
            </div>

            <section className="mt-8 space-y-4 rounded-xl border border-zinc-800 bg-zinc-950/60 p-6 shadow-inner shadow-black/40">
                <header>
                    <h2 className="text-lg font-semibold text-zinc-100">User roster</h2>
                    <p className="text-sm text-zinc-500">
                        Mark at least one support admin so the platform always has someone who can respond to feedback.
                    </p>
                </header>

                <div className="space-y-3">
                    {users.map((user) => (
                        <article
                            key={user.id}
                            className="flex flex-col gap-3 rounded-lg border border-zinc-800/60 bg-zinc-950/80 p-4 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div>
                                <div className="flex items-center gap-3">
                                    <h3 className="text-base font-semibold text-zinc-100">{user.name}</h3>
                                    {user.is_support_admin && <Badge variant="secondary">Support admin</Badge>}
                                </div>
                                <p className="text-sm text-zinc-400">{user.email}</p>
                                {user.created_at && (
                                    <p className="text-xs text-zinc-500">Joined {formatTimestamp(user.created_at)}</p>
                                )}
                            </div>
                            <div className="flex items-center gap-3">
                                <Checkbox
                                    id={`support-${user.id}`}
                                    checked={user.is_support_admin}
                                    disabled={updating === user.id}
                                    onCheckedChange={(checked) => handleToggle(user, checked === true)}
                                />
                                <label htmlFor={`support-${user.id}`} className="text-sm text-zinc-300">
                                    {updating === user.id
                                        ? 'Updating access…'
                                        : user.is_support_admin
                                          ? 'Remove support access'
                                          : 'Grant support access'}
                                </label>
                            </div>
                        </article>
                    ))}
                </div>
            </section>
        </AppLayout>
    );
}
