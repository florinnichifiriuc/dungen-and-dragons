import { FormEvent } from 'react';

import { Head, Link, useForm } from '@inertiajs/react';

import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

type CampaignContext = {
    id: number;
    title: string;
};

type TurnSummary = {
    id: number;
    number: number;
    processed_at: string | null;
};

type SessionCreateProps = {
    campaign: CampaignContext;
    turns: TurnSummary[];
};

type SessionFormState = {
    title: string;
    session_date: string;
    duration_minutes: string;
    location: string;
    agenda: string;
    summary: string;
    recording_url: string;
    turn_id: string;
};

export default function SessionCreate({ campaign, turns }: SessionCreateProps) {
    const { data, setData, post, processing, errors } = useForm<SessionFormState>({
        title: '',
        session_date: '',
        duration_minutes: '',
        location: '',
        agenda: '',
        summary: '',
        recording_url: '',
        turn_id: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post(route('campaigns.sessions.store', { campaign: campaign.id }));
    };

    return (
        <AppLayout>
            <Head title={`Schedule session for ${campaign.title}`} />

            <div className="mb-8 flex items-center justify-between gap-4">
                <div>
                    <h1 className="text-3xl font-semibold text-zinc-100">Schedule a session</h1>
                    <p className="mt-2 max-w-2xl text-sm text-zinc-400">
                        Anchor the next gathering with agenda, timebox, and optional recording links for the crew.
                    </p>
                </div>

                <Button asChild variant="outline" className="border-zinc-700 text-zinc-200 hover:text-amber-200">
                    <Link href={route('campaigns.sessions.index', { campaign: campaign.id })}>Back to sessions</Link>
                </Button>
            </div>

            <form onSubmit={submit} className="space-y-6">
                <section className="grid gap-6 rounded-xl border border-zinc-800 bg-zinc-950/70 p-6 shadow-inner shadow-black/40">
                    <div className="grid gap-2">
                        <Label htmlFor="title">Title</Label>
                        <Input
                            id="title"
                            value={data.title}
                            onChange={(event) => setData('title', event.target.value)}
                            required
                        />
                        {errors.title && <p className="text-sm text-rose-400">{errors.title}</p>}
                    </div>

                    <div className="grid gap-2 sm:grid-cols-2 sm:gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="session_date">Date &amp; time (UTC)</Label>
                            <Input
                                id="session_date"
                                type="datetime-local"
                                value={data.session_date}
                                onChange={(event) => setData('session_date', event.target.value)}
                            />
                            {errors.session_date && <p className="text-sm text-rose-400">{errors.session_date}</p>}
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="duration_minutes">Duration (minutes)</Label>
                            <Input
                                id="duration_minutes"
                                type="number"
                                min={0}
                                value={data.duration_minutes}
                                onChange={(event) => setData('duration_minutes', event.target.value)}
                            />
                            {errors.duration_minutes && <p className="text-sm text-rose-400">{errors.duration_minutes}</p>}
                        </div>
                    </div>

                    <div className="grid gap-2 sm:grid-cols-2 sm:gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="location">Location / medium</Label>
                            <Input
                                id="location"
                                placeholder="Discord, Candlekeep, astral sea..."
                                value={data.location}
                                onChange={(event) => setData('location', event.target.value)}
                            />
                            {errors.location && <p className="text-sm text-rose-400">{errors.location}</p>}
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="turn_id">Linked turn</Label>
                            <select
                                id="turn_id"
                                value={data.turn_id}
                                onChange={(event) => setData('turn_id', event.target.value)}
                                className="h-10 rounded-md border border-zinc-700 bg-zinc-900/60 px-3 text-sm text-zinc-100 focus:outline-none focus:ring-2 focus:ring-amber-500"
                            >
                                <option value="">No turn link</option>
                                {turns.map((turn) => (
                                    <option key={turn.id} value={turn.id}>
                                        Turn #{turn.number}
                                    </option>
                                ))}
                            </select>
                            {errors.turn_id && <p className="text-sm text-rose-400">{errors.turn_id}</p>}
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="agenda">Agenda</Label>
                        <Textarea
                            id="agenda"
                            rows={4}
                            value={data.agenda}
                            onChange={(event) => setData('agenda', event.target.value)}
                            placeholder="Key beats, encounter prep, lore reveals..."
                        />
                        {errors.agenda && <p className="text-sm text-rose-400">{errors.agenda}</p>}
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="summary">Recap / mission log</Label>
                        <Textarea
                            id="summary"
                            rows={4}
                            value={data.summary}
                            onChange={(event) => setData('summary', event.target.value)}
                        />
                        {errors.summary && <p className="text-sm text-rose-400">{errors.summary}</p>}
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="recording_url">Recording link</Label>
                        <Input
                            id="recording_url"
                            type="url"
                            placeholder="https://..."
                            value={data.recording_url}
                            onChange={(event) => setData('recording_url', event.target.value)}
                        />
                        {errors.recording_url && <p className="text-sm text-rose-400">{errors.recording_url}</p>}
                    </div>
                </section>

                <div className="flex items-center justify-end gap-3">
                    <Button
                        type="button"
                        variant="ghost"
                        className="text-zinc-300 hover:text-amber-200"
                        disabled={processing}
                        onClick={() => history.back()}
                    >
                        Cancel
                    </Button>
                    <Button type="submit" disabled={processing}>
                        Save session
                    </Button>
                </div>
            </form>
        </AppLayout>
    );
}
