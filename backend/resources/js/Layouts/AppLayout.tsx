import { PropsWithChildren } from 'react';

import { Link, usePage } from '@inertiajs/react';

import { Button } from '@/components/ui/button';

export default function AppLayout({ children }: PropsWithChildren) {
    const { props } = usePage();
    const flash = props.flash as { success?: string; error?: string } | undefined;
    const user = props.auth?.user;

    return (
        <div className="min-h-screen bg-zinc-950 text-zinc-100">
            <header className="border-b border-zinc-800 bg-zinc-900/80 backdrop-blur">
                <div className="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
                    <Link href={route('dashboard')} className="text-lg font-semibold tracking-wide">
                        Dungen & Dragons
                    </Link>
                    <div className="flex items-center gap-3 text-sm text-zinc-300">
                        {user && <span className="text-zinc-400">{user.name}</span>}
                        <form method="post" action={route('logout')}>
                            <input
                                type="hidden"
                                name="_token"
                                value={typeof props.csrf_token === 'string' ? props.csrf_token : ''}
                            />
                            <Button variant="ghost" size="sm" className="text-zinc-300 hover:text-amber-300" type="submit">
                                Log out
                            </Button>
                        </form>
                    </div>
                </div>
            </header>
            <main className="mx-auto max-w-7xl px-6 py-10 space-y-6">
                {flash?.success && (
                    <div className="rounded-lg border border-emerald-500/40 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200">
                        {flash.success}
                    </div>
                )}
                {flash?.error && (
                    <div className="rounded-lg border border-rose-500/40 bg-rose-500/10 px-4 py-3 text-sm text-rose-200">
                        {flash.error}
                    </div>
                )}
                <div>{children}</div>
            </main>
        </div>
    );
}
