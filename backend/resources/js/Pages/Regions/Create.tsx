import { FormEventHandler } from 'react';

import { Head, Link, useForm } from '@inertiajs/react';

import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { InputError } from '@/components/InputError';

type DungeonMasterOption = {
    id: number;
    name: string;
    role: string;
};

type RegionCreateProps = {
    group: {
        id: number;
        name: string;
    };
    dungeonMasters: DungeonMasterOption[];
};

type FormData = {
    name: string;
    summary: string;
    description: string;
    dungeon_master_id: string;
    turn_duration_hours: string;
    next_turn_at: string;
};

export default function RegionCreate({ group, dungeonMasters }: RegionCreateProps) {
    const { data, setData, post, processing, errors } = useForm<FormData>({
        name: '',
        summary: '',
        description: '',
        dungeon_master_id: '',
        turn_duration_hours: '24',
        next_turn_at: '',
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        post(route('groups.regions.store', group.id));
    };

    return (
        <AppLayout>
            <Head title={`Assign region · ${group.name}`} />

            <div className="mb-6">
                <h1 className="text-3xl font-semibold text-zinc-100">Assign a region</h1>
                <p className="mt-2 text-sm text-zinc-400">
                    Pair a DM with a realm and determine how often turns should resolve.
                </p>
            </div>

            <form onSubmit={submit} className="space-y-6">
                <div className="space-y-2">
                    <Label htmlFor="name">Region name</Label>
                    <Input
                        id="name"
                        value={data.name}
                        onChange={(event) => setData('name', event.target.value)}
                        required
                        autoFocus
                    />
                    <InputError message={errors.name} />
                </div>

                <div className="space-y-2">
                    <Label htmlFor="summary">Summary</Label>
                    <Input
                        id="summary"
                        value={data.summary}
                        onChange={(event) => setData('summary', event.target.value)}
                        placeholder="Shifting desert of clockwork ruins"
                    />
                    <InputError message={errors.summary} />
                </div>

                <div className="space-y-2">
                    <Label htmlFor="description">Notes</Label>
                    <textarea
                        id="description"
                        value={data.description}
                        onChange={(event) => setData('description', event.target.value)}
                        className="min-h-[120px] w-full rounded-md border border-zinc-800 bg-zinc-950/70 px-3 py-2 text-sm text-zinc-100 placeholder:text-zinc-500 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/40"
                    />
                    <InputError message={errors.description} />
                </div>

                <div className="grid gap-6 sm:grid-cols-2">
                    <div className="space-y-2">
                        <Label htmlFor="dungeon_master_id">Dungeon master</Label>
                        <select
                            id="dungeon_master_id"
                            value={data.dungeon_master_id}
                            onChange={(event) => setData('dungeon_master_id', event.target.value)}
                            className="w-full rounded-md border border-zinc-800 bg-zinc-950/70 px-3 py-2 text-sm text-zinc-100 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/40"
                        >
                            <option value="">Unassigned</option>
                            {dungeonMasters.map((dm) => (
                                <option key={dm.id} value={dm.id}>
                                    {dm.name} · {dm.role.replace('-', ' ')}
                                </option>
                            ))}
                        </select>
                        <InputError message={errors.dungeon_master_id} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="turn_duration_hours">Turn cadence</Label>
                        <select
                            id="turn_duration_hours"
                            value={data.turn_duration_hours}
                            onChange={(event) => setData('turn_duration_hours', event.target.value)}
                            className="w-full rounded-md border border-zinc-800 bg-zinc-950/70 px-3 py-2 text-sm text-zinc-100 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/40"
                        >
                            <option value="6">6 hours</option>
                            <option value="24">24 hours</option>
                        </select>
                        <InputError message={errors.turn_duration_hours} />
                    </div>
                </div>

                <div className="space-y-2">
                    <Label htmlFor="next_turn_at">Next turn (UTC)</Label>
                    <Input
                        id="next_turn_at"
                        type="datetime-local"
                        value={data.next_turn_at}
                        onChange={(event) => setData('next_turn_at', event.target.value)}
                    />
                    <InputError message={errors.next_turn_at} />
                </div>

                <div className="flex items-center justify-between">
                    <Button type="submit" disabled={processing}>
                        Save region
                    </Button>

                    <Link href={route('groups.show', group.id)} className="text-sm text-zinc-400 hover:text-zinc-200">
                        Cancel
                    </Link>
                </div>
            </form>
        </AppLayout>
    );
}
