import { PropsWithChildren } from 'react';

import { Link } from '@inertiajs/react';
import { safeRoute } from '@/lib/route';

export default function GuestLayout({ children }: PropsWithChildren) {
    return (
        <div className="min-h-screen bg-gradient-to-br from-zinc-950 via-stone-950 to-zinc-900 text-zinc-100">
            <div className="flex min-h-screen flex-col items-center justify-center px-4 py-12">
                <div className="mb-8 text-center">
                    <Link href={safeRoute('welcome', '/')} className="inline-flex items-center gap-2 text-2xl font-semibold tracking-wide">
                        <span className="inline-flex h-10 w-10 items-center justify-center rounded-full bg-amber-500/90 text-zinc-950 font-black">
                            D
                        </span>
                        Dungen & Dragons
                    </Link>
                    <p className="mt-2 text-sm text-zinc-400">Collaborate across regions and turns.</p>
                </div>
                <div className="w-full max-w-md rounded-2xl border border-zinc-800/80 bg-zinc-900/70 p-8 shadow-2xl shadow-amber-500/10 backdrop-blur">
                    {children}
                </div>
            </div>
        </div>
    );
}
