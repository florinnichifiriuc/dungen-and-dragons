import { FormEvent, useMemo } from 'react';

import { Head, Link, useForm } from '@inertiajs/react';

import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

type TurnSummary = {
    id: number;
    number: number;
    processed_at: string | null;
    summary: string | null;
    used_ai_fallback: boolean;
    processed_by: { id: number; name: string } | null;
};

type TurnConfigurationPayload = {
    turn_duration_hours: number;
    next_turn_at: string | null;
    last_processed_at: string | null;
    is_due: boolean;
};

type RegionPayload = {
    id: number;
    name: string;
    summary: string | null;
    turn_configuration: TurnConfigurationPayload;
    recent_turns: TurnSummary[];
};

type GroupPayload = {
    id: number;
    name: string;
};

type ProcessTurnProps = {
    group: GroupPayload;
    region: RegionPayload;
};

export default function ProcessTurn({ group, region }: ProcessTurnProps) {
    const { data, setData, post, processing, errors } = useForm({
        summary: '' as string,
        use_ai_fallback: false,
    });

    const upcomingWindow = useMemo(() => {
        if (!region.turn_configuration.next_turn_at) {
            return null;
        }

        const end = new Date(region.turn_configuration.next_turn_at);
        const start = new Date(end.getTime() - region.turn_configuration.turn_duration_hours * 60 * 60 * 1000);

        return { start, end };
    }, [region.turn_configuration]);

    const handleSubmit = (event: FormEvent) => {
        event.preventDefault();
        post(route('groups.regions.turns.store', [group.id, region.id]));
    };

    return (
        <AppLayout>
            <Head title={`Process turn – ${region.name}`} />

            <div className="flex items-center gap-3 text-sm text-zinc-400">
                <Link href={route('groups.show', group.id)} className="text-indigo-300 hover:text-indigo-200">
                    &larr; Back to {group.name}
                </Link>
                <span>/</span>
                <span className="text-zinc-200">Process turn</span>
            </div>

            <div className="mt-6 grid gap-6 lg:grid-cols-[2fr,1fr]">
                <section className="space-y-6 rounded-xl border border-zinc-800 bg-zinc-950/60 p-6 shadow-inner shadow-black/40">
                    <header className="space-y-2">
                        <h1 className="text-2xl font-semibold text-zinc-100">Advance {region.name}</h1>
                        {region.summary ? (
                            <p className="text-sm text-zinc-400">{region.summary}</p>
                        ) : (
                            <p className="text-sm text-zinc-500">No lore yet. Capture a summary after processing.</p>
                        )}
                        <p className="text-xs uppercase tracking-wide text-zinc-500">
                            Turn cadence: {region.turn_configuration.turn_duration_hours}h cadence
                        </p>
                        <p className="text-xs text-zinc-500">
                            {region.turn_configuration.is_due
                                ? 'The scheduled window is ready for completion.'
                                : 'You may process early if you need to adjust pacing.'}
                        </p>
                        {upcomingWindow && (
                            <p className="text-xs text-zinc-500">
                                Scheduled window {upcomingWindow.start.toLocaleString()} → {upcomingWindow.end.toLocaleString()}
                            </p>
                        )}
                    </header>

                    <form onSubmit={handleSubmit} className="space-y-6">
                        <div className="space-y-2">
                            <Label htmlFor="summary" className="text-sm text-zinc-200">
                                Turn summary
                            </Label>
                            <Textarea
                                id="summary"
                                value={data.summary}
                                onChange={(event) => setData('summary', event.target.value)}
                                placeholder="Record major events, map deltas, and faction shifts."
                                className="min-h-[150px] bg-zinc-900 text-sm"
                            />
                            {errors.summary && <p className="text-sm text-rose-300">{errors.summary}</p>}
                        </div>

                        <div className="flex items-center gap-3">
                            <Checkbox
                                id="use_ai_fallback"
                                checked={data.use_ai_fallback}
                                onChange={(event) => setData('use_ai_fallback', event.target.checked)}
                            />
                            <div className="space-y-1">
                                <Label htmlFor="use_ai_fallback" className="text-sm text-zinc-200">
                                    Request AI fallback narrator
                                </Label>
                                <p className="text-xs text-zinc-500">
                                    We&apos;ll auto-generate a chronicle if you leave the summary blank. Great for async worlds.
                                </p>
                            </div>
                        </div>

                        <div className="flex items-center gap-3">
                            <Button type="submit" disabled={processing}>
                                {processing ? 'Processing…' : 'Process turn'}
                            </Button>
                            <Button type="button" variant="outline" className="border-zinc-700" asChild>
                                <Link href={route('groups.show', group.id)}>Cancel</Link>
                            </Button>
                        </div>
                    </form>
                </section>

                <aside className="space-y-4 rounded-xl border border-zinc-800 bg-zinc-950/40 p-5">
                    <h2 className="text-sm font-semibold uppercase tracking-wide text-zinc-400">Recent history</h2>
                    {region.recent_turns.length === 0 ? (
                        <p className="text-sm text-zinc-500">
                            No processed turns yet. The first chronicle is about to be written.
                        </p>
                    ) : (
                        <ul className="space-y-3 text-sm text-zinc-300">
                            {region.recent_turns.map((turn) => (
                                <li key={turn.id} className="rounded-lg border border-zinc-800 bg-zinc-900/40 p-3">
                                    <div className="flex items-center justify-between text-xs text-zinc-500">
                                        <span className="font-semibold text-zinc-200">Turn #{turn.number}</span>
                                        {turn.processed_at && <span>{new Date(turn.processed_at).toLocaleString()}</span>}
                                    </div>
                                    {turn.summary ? (
                                        <p className="mt-2 text-sm text-zinc-300">{turn.summary}</p>
                                    ) : (
                                        <p className="mt-2 text-sm text-zinc-500">Summary pending.</p>
                                    )}
                                    <div className="mt-2 flex flex-wrap items-center gap-3 text-xs text-zinc-500">
                                        <span>
                                            Processed by{' '}
                                            {turn.processed_by
                                                ? turn.processed_by.name
                                                : turn.used_ai_fallback
                                                ? 'AI delegate'
                                                : 'Unknown'}
                                        </span>
                                        {turn.used_ai_fallback && (
                                            <span className="rounded bg-indigo-500/10 px-2 py-0.5 text-indigo-300">AI fallback</span>
                                        )}
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )}
                </aside>
            </div>
        </AppLayout>
    );
}
