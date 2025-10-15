import { FormEvent } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';

import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export default function GroupsJoin() {
    const form = useForm({
        code: '',
    });

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        form.post(route('groups.join.store'), {
            preserveScroll: true,
        });
    }

    return (
        <AppLayout>
            <Head title="Join a party" />

            <div className="mx-auto max-w-xl rounded-xl border border-zinc-800 bg-zinc-950/70 p-8 shadow-lg shadow-black/40">
                <h1 className="text-3xl font-semibold text-zinc-100">Join an adventuring party</h1>
                <p className="mt-2 text-sm text-zinc-400">
                    Enter the join code shared by a Game Master or dungeon master to step into their realm. You will be added as an
                    adventurer, and they can promote you once you have proven your worth.
                </p>

                <form onSubmit={submit} className="mt-6 space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="code" className="text-zinc-300">
                            Join code
                        </Label>
                        <Input
                            id="code"
                            name="code"
                            value={form.data.code}
                            onChange={(event) => form.setData('code', event.target.value.toUpperCase())}
                            placeholder="DRAGON12"
                            className="uppercase tracking-[0.3em]"
                            autoFocus
                        />
                        {form.errors.code && <p className="text-sm text-rose-400">{form.errors.code}</p>}
                    </div>

                    <div className="flex items-center gap-3">
                        <Button type="submit" disabled={form.processing}>
                            Join party
                        </Button>
                        <Button asChild variant="outline" className="border-zinc-700" disabled={form.processing}>
                            <Link href={route('groups.index')}>Back to parties</Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
