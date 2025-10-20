import { useState } from 'react';

import { Head, Link, router, usePage } from '@inertiajs/react';

import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { formatTimestamp } from '@/lib/utils';

type UserSummary = {
    id: number;
    name: string;
    email: string;
    account_role: string;
    is_support_admin: boolean;
    created_at: string | null;
};

type PageProps = {
    users: UserSummary[];
    roles: string[];
};

export default function AdminUserIndex() {
    const { users, roles } = usePage<PageProps>().props;
    const [updating, setUpdating] = useState<number | null>(null);

    const handleRoleChange = (user: UserSummary, nextRole: string) => {
        if (nextRole === user.account_role) {
            return;
        }

        setUpdating(user.id);

        router.patch(
            route('admin.users.update', user.id),
            { account_role: nextRole },
            {
                preserveScroll: true,
                onFinish: () => setUpdating(null),
            }
        );
    };

    return (
        <AppLayout>
            <Head title="User roles" />

            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 className="text-3xl font-semibold text-zinc-100">User access roles</h1>
                    <p className="text-sm text-zinc-400">
                        Promote facilitators into guides or administrators so they can empower every table.
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
                        Assign roles to clarify who can configure campaign infrastructure or assist with support tasks.
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
                                    <Badge variant="outline" className="text-zinc-300">
                                        {user.account_role}
                                    </Badge>
                                </div>
                                <p className="text-sm text-zinc-400">{user.email}</p>
                                {user.created_at && (
                                    <p className="text-xs text-zinc-500">Joined {formatTimestamp(user.created_at)}</p>
                                )}
                            </div>
                            <div className="flex items-center gap-3">
                                <label htmlFor={`role-${user.id}`} className="text-sm text-zinc-300">
                                    Role
                                </label>
                                <select
                                    id={`role-${user.id}`}
                                    className="rounded-md border border-zinc-800 bg-zinc-950/70 px-3 py-2 text-sm text-zinc-100 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500/40"
                                    value={user.account_role}
                                    disabled={updating === user.id}
                                    onChange={(event) => handleRoleChange(user, event.target.value)}
                                >
                                    {roles.map((role) => (
                                        <option key={role} value={role}>
                                            {role}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        </article>
                    ))}
                </div>
            </section>
        </AppLayout>
    );
}
