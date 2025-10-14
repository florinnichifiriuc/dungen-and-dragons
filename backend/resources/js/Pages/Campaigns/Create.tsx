import { FormEventHandler, useMemo } from 'react';

import { Head, Link, useForm } from '@inertiajs/react';

import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { InputError } from '@/components/InputError';

type GroupOption = {
    id: number;
    name: string;
    regions: { id: number; name: string }[];
};

type CampaignCreateProps = {
    groups: GroupOption[];
    available_statuses: string[];
};

const timezoneOptions = ['UTC', 'America/New_York', 'Europe/London', 'Asia/Tokyo', 'Australia/Sydney'];

export default function CampaignCreate({ groups }: CampaignCreateProps) {
    const { data, setData, post, processing, errors } = useForm({
        group_id: groups.length > 0 ? groups[0].id.toString() : '',
        region_id: '',
        title: '',
        synopsis: '',
        default_timezone: 'UTC',
        start_date: '',
        end_date: '',
        turn_hours: 24,
    });

    const regions = useMemo(() => {
        const group = groups.find((option) => option.id.toString() === data.group_id);
        return group?.regions ?? [];
    }, [groups, data.group_id]);

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        post(route('campaigns.store'));
    };

    return (
        <AppLayout>
            <Head title="Create campaign" />

            <div className="mb-6">
                <h1 className="text-3xl font-semibold text-zinc-100">Launch a new campaign</h1>
                <p className="mt-2 text-sm text-zinc-400">
                    Define your party&apos;s next chronicle with an optional region focus and cadence. Invitations and role assignments come next.
                </p>
            </div>

            <form onSubmit={submit} className="space-y-6">
                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="space-y-2">
                        <Label htmlFor="group_id">Owning group</Label>
                        <select
                            id="group_id"
                            className="w-full rounded-md border border-zinc-800 bg-zinc-950/70 px-3 py-2 text-sm text-zinc-100 focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-500/40"
                            value={data.group_id}
                            onChange={(event) => {
                                setData('group_id', event.target.value);
                                setData('region_id', '');
                            }}
                            required
                        >
                            <option value="" disabled>
                                Select a group
                            </option>
                            {groups.map((group) => (
                                <option key={group.id} value={group.id}>
                                    {group.name}
                                </option>
                            ))}
                        </select>
                        <InputError message={errors.group_id} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="region_id">Region focus</Label>
                        <select
                            id="region_id"
                            className="w-full rounded-md border border-zinc-800 bg-zinc-950/70 px-3 py-2 text-sm text-zinc-100 focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-500/40"
                            value={data.region_id}
                            onChange={(event) => setData('region_id', event.target.value)}
                        >
                            <option value="">Unassigned</option>
                            {regions.map((region) => (
                                <option key={region.id} value={region.id}>
                                    {region.name}
                                </option>
                            ))}
                        </select>
                        <InputError message={errors.region_id} />
                    </div>
                </div>

                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="space-y-2">
                        <Label htmlFor="title">Campaign title</Label>
                        <Input
                            id="title"
                            value={data.title}
                            onChange={(event) => setData('title', event.target.value)}
                            required
                            autoFocus
                        />
                        <InputError message={errors.title} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="default_timezone">Default timezone</Label>
                        <select
                            id="default_timezone"
                            className="w-full rounded-md border border-zinc-800 bg-zinc-950/70 px-3 py-2 text-sm text-zinc-100 focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-500/40"
                            value={data.default_timezone}
                            onChange={(event) => setData('default_timezone', event.target.value)}
                            required
                        >
                            {timezoneOptions.map((zone) => (
                                <option key={zone} value={zone}>
                                    {zone}
                                </option>
                            ))}
                        </select>
                        <InputError message={errors.default_timezone} />
                    </div>
                </div>

                <div className="space-y-2">
                    <Label htmlFor="synopsis">Synopsis</Label>
                    <textarea
                        id="synopsis"
                        value={data.synopsis}
                        onChange={(event) => setData('synopsis', event.target.value)}
                        className="min-h-[140px] w-full rounded-md border border-zinc-800 bg-zinc-950/70 px-3 py-2 text-sm text-zinc-100 placeholder:text-zinc-500 focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-500/40"
                    />
                    <InputError message={errors.synopsis} />
                </div>

                <div className="grid gap-4 sm:grid-cols-3">
                    <div className="space-y-2">
                        <Label htmlFor="start_date">Start date</Label>
                        <Input
                            id="start_date"
                            type="date"
                            value={data.start_date}
                            onChange={(event) => setData('start_date', event.target.value)}
                        />
                        <InputError message={errors.start_date} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="end_date">End date</Label>
                        <Input
                            id="end_date"
                            type="date"
                            value={data.end_date}
                            onChange={(event) => setData('end_date', event.target.value)}
                        />
                        <InputError message={errors.end_date} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="turn_hours">Turn cadence (hours)</Label>
                        <Input
                            id="turn_hours"
                            type="number"
                            min={1}
                            max={168}
                            value={data.turn_hours}
                            onChange={(event) => setData('turn_hours', Number(event.target.value))}
                        />
                        <InputError message={errors.turn_hours} />
                    </div>
                </div>

                <div className="flex items-center justify-between">
                    <Button type="submit" disabled={processing}>
                        Create campaign
                    </Button>
                    <Link href={route('campaigns.index')} className="text-sm text-zinc-400 hover:text-zinc-200">
                        Cancel
                    </Link>
                </div>
            </form>
        </AppLayout>
    );
}
