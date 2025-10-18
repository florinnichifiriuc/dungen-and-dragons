import { Head, router, usePage } from '@inertiajs/react';

import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';

type ManagedUser = {
    id: number;
    name: string;
    email: string;
    account_role: string;
    is_support_admin: boolean;
    created_at: string | null;
};

type PageProps = {
    users: ManagedUser[];
    roles: string[];
};

export default function AdminUsersIndex() {
    const { users, roles } = usePage<PageProps>().props;

    const handleRoleChange = (user: ManagedUser, nextRole: string) => {
        if (nextRole === user.account_role) {
            return;
        }

        router.patch(
            route('admin.users.update', user.id),
            { account_role: nextRole },
            {
                preserveScroll: true,
            }
        );
    };

    return (
        <AppLayout>
            <Head title="User roles" />

            <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 className="text-3xl font-semibold text-zinc-100">User access roles</h1>
                    <p className="mt-1 text-sm text-zinc-400">
                        Promote facilitators into guides or administrators so they can empower every table.
                    </p>
                </div>
                <Button asChild variant="outline" className="border-zinc-700 text-sm">
                    <a href={route('dashboard')}>Back to dashboard</a>
                </Button>
            </div>

            <section className="mt-8 rounded-xl border border-zinc-800 bg-zinc-950/70">
                <header className="flex items-center justify-between border-b border-zinc-800 px-6 py-4">
                    <h2 className="text-lg font-semibold text-zinc-100">Roster</h2>
                    <span className="text-xs uppercase tracking-wide text-zinc-500">{users.length} accounts</span>
                </header>
                <div className="overflow-x-auto px-6 py-4">
                    <table className="min-w-full divide-y divide-zinc-800 text-sm">
                        <thead>
                            <tr className="text-left uppercase tracking-wide text-zinc-500">
                                <th className="px-3 py-2">Name</th>
                                <th className="px-3 py-2">Email</th>
                                <th className="px-3 py-2">Role</th>
                                <th className="px-3 py-2">Support</th>
                                <th className="px-3 py-2">Member since</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-zinc-900">
                            {users.map((user) => (
                                <tr key={user.id} className="text-zinc-200">
                                    <td className="px-3 py-3 font-medium text-zinc-100">{user.name}</td>
                                    <td className="px-3 py-3 text-zinc-400">{user.email}</td>
                                    <td className="px-3 py-3">
                                        <label className="sr-only" htmlFor={`role-${user.id}`}>
                                            Role for {user.name}
                                        </label>
                                        <select
                                            id={`role-${user.id}`}
                                            className="w-full rounded-md border border-zinc-700 bg-zinc-900/80 px-2 py-1 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500/40"
                                            value={user.account_role}
                                            onChange={(event) => handleRoleChange(user, event.target.value)}
                                        >
                                            {roles.map((role) => (
                                                <option key={role} value={role}>
                                                    {role.replace('-', ' ')}
                                                </option>
                                            ))}
                                        </select>
                                    </td>
                                    <td className="px-3 py-3">
                                        {user.is_support_admin ? (
                                            <span className="inline-flex items-center rounded-full bg-emerald-500/20 px-2 py-1 text-xs font-semibold text-emerald-200">
                                                Support
                                            </span>
                                        ) : (
                                            <span className="text-xs text-zinc-500">—</span>
                                        )}
                                    </td>
                                    <td className="px-3 py-3 text-xs text-zinc-500">
                                        {user.created_at ? new Date(user.created_at).toLocaleDateString() : '—'}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </section>
        </AppLayout>
    );
}
