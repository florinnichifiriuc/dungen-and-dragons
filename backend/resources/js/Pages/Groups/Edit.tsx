import { FormEventHandler } from 'react';

import { Head, Link, useForm } from '@inertiajs/react';

import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { InputError } from '@/components/InputError';

type GroupPayload = {
    id: number;
    name: string;
    description: string | null;
};

type GroupEditProps = {
    group: GroupPayload;
};

export default function GroupEdit({ group }: GroupEditProps) {
    const { data, setData, put, processing, errors } = useForm({
        name: group.name,
        description: group.description ?? '',
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        put(route('groups.update', group.id));
    };

    return (
        <AppLayout>
            <Head title={`Edit ${group.name}`} />

            <div className="mb-6">
                <h1 className="text-3xl font-semibold text-zinc-100">Update party details</h1>
                <p className="mt-2 text-sm text-zinc-400">
                    Keep lore, hooks, and ownership information current as your table evolves.
                </p>
            </div>

            <form onSubmit={submit} className="space-y-6">
                <div className="space-y-2">
                    <Label htmlFor="name">Group name</Label>
                    <Input
                        id="name"
                        value={data.name}
                        onChange={(event) => setData('name', event.target.value)}
                        required
                    />
                    <InputError message={errors.name} />
                </div>

                <div className="space-y-2">
                    <Label htmlFor="description">Primer</Label>
                    <textarea
                        id="description"
                        value={data.description}
                        onChange={(event) => setData('description', event.target.value)}
                        className="min-h-[120px] w-full rounded-md border border-zinc-800 bg-zinc-950/70 px-3 py-2 text-sm text-zinc-100 placeholder:text-zinc-500 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/40"
                    />
                    <InputError message={errors.description} />
                </div>

                <div className="flex items-center justify-between">
                    <Button type="submit" disabled={processing}>
                        Save changes
                    </Button>

                    <Link href={route('groups.show', group.id)} className="text-sm text-zinc-400 hover:text-zinc-200">
                        Back to dashboard
                    </Link>
                </div>
            </form>
        </AppLayout>
    );
}
