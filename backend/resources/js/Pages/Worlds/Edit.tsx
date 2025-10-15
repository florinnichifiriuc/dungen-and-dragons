import { FormEventHandler } from 'react';

import { Head, Link, useForm } from '@inertiajs/react';

import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { InputError } from '@/components/InputError';

type WorldPayload = {
    id: number;
    name: string;
    summary: string | null;
    description: string | null;
    default_turn_duration_hours: number;
};

type WorldEditProps = {
    group: {
        id: number;
        name: string;
    };
    world: WorldPayload;
};

type FormData = {
    name: string;
    summary: string;
    description: string;
    default_turn_duration_hours: number;
};

export default function WorldEdit({ group, world }: WorldEditProps) {
    const { data, setData, put, processing, errors } = useForm<FormData>({
        name: world.name,
        summary: world.summary ?? '',
        description: world.description ?? '',
        default_turn_duration_hours: world.default_turn_duration_hours,
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        put(route('groups.worlds.update', [group.id, world.id]));
    };

    return (
        <AppLayout>
            <Head title={`Tend world · ${world.name}`} />

            <div className="mb-6">
                <h1 className="text-3xl font-semibold text-zinc-100">Tend world</h1>
                <p className="mt-2 text-sm text-zinc-400">
                    Refresh lore, adjust pacing defaults, or refine summaries before sharing with your dungeon masters.
                </p>
            </div>

            <form onSubmit={submit} className="space-y-6">
                <div className="space-y-2">
                    <Label htmlFor="name">World name</Label>
                    <Input
                        id="name"
                        value={data.name}
                        onChange={(event) => setData('name', event.target.value)}
                        required
                    />
                    <InputError message={errors.name} />
                </div>

                <div className="space-y-2">
                    <Label htmlFor="summary">Summary</Label>
                    <Input
                        id="summary"
                        value={data.summary}
                        onChange={(event) => setData('summary', event.target.value)}
                    />
                    <InputError message={errors.summary} />
                </div>

                <div className="space-y-2">
                    <Label htmlFor="description">Lore notes</Label>
                    <textarea
                        id="description"
                        value={data.description}
                        onChange={(event) => setData('description', event.target.value)}
                        className="min-h-[140px] w-full rounded-md border border-zinc-800 bg-zinc-950/70 px-3 py-2 text-sm text-zinc-100 placeholder:text-zinc-500 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/40"
                    />
                    <InputError message={errors.description} />
                </div>

                <div className="space-y-2">
                    <Label htmlFor="default_turn_duration_hours">Default turn cadence (hours)</Label>
                    <Input
                        id="default_turn_duration_hours"
                        type="number"
                        min={1}
                        max={168}
                        value={data.default_turn_duration_hours}
                        onChange={(event) => setData('default_turn_duration_hours', Number(event.target.value))}
                        required
                    />
                    <InputError message={errors.default_turn_duration_hours} />
                </div>

                <div className="flex items-center justify-between">
                    <Button type="submit" disabled={processing}>
                        Save world
                    </Button>

                    <Link href={route('groups.show', group.id)} className="text-sm text-zinc-400 hover:text-zinc-200">
                        Back to group
                    </Link>
                </div>
            </form>
        </AppLayout>
    );
}
