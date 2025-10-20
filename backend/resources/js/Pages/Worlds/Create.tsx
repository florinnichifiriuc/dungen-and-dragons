import { FormEventHandler } from 'react';

import { Head, Link, useForm } from '@inertiajs/react';

import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { InputError } from '@/components/InputError';
import AiIdeaPanel, { AiIdeaResult } from '@/components/ai/AiIdeaPanel';

type WorldCreateProps = {
    group: {
        id: number;
        name: string;
    };
    defaults: {
        default_turn_duration_hours: number;
    };
};

type FormData = {
    name: string;
    summary: string;
    description: string;
    default_turn_duration_hours: number;
};

export default function WorldCreate({ group, defaults }: WorldCreateProps) {
    const { data, setData, post, processing, errors } = useForm<FormData>({
        name: '',
        summary: '',
        description: '',
        default_turn_duration_hours: defaults.default_turn_duration_hours,
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        post(route('groups.worlds.store', group.id));
    };

    const applyIdea = (result: AiIdeaResult) => {
        const structured = result.structured ?? {};
        const fields = (structured.fields ?? {}) as Record<string, unknown>;

        if (typeof fields.name === 'string') {
            setData('name', fields.name);
        }

        const summary = typeof structured.summary === 'string' ? structured.summary : fields.summary;
        if (typeof summary === 'string') {
            setData('summary', summary);
        }

        const description = typeof structured.description === 'string' ? structured.description : fields.description;
        if (typeof description === 'string') {
            setData('description', description);
        }

        const cadence = fields.default_turn_duration_hours ?? fields.turn_duration_hours;
        if (typeof cadence === 'number' && Number.isFinite(cadence)) {
            setData('default_turn_duration_hours', cadence);
        }
    };

    return (
        <AppLayout>
            <Head title={`Found a world · ${group.name}`} />

            <div className="mb-6">
                <h1 className="text-3xl font-semibold text-zinc-100">Found a new world</h1>
                <p className="mt-2 text-sm text-zinc-400">
                    Sketch the broad strokes of a shared realm and define how quickly its regions tick through turns.
                </p>
            </div>

            <div className="grid gap-8 lg:grid-cols-[1.8fr_1fr]">
                <form onSubmit={submit} className="space-y-6">
                    <div className="space-y-2">
                        <Label htmlFor="name">World name</Label>
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
                            placeholder="Shared dreamscape of the Sapphire Archive"
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
                            Create world
                        </Button>

                        <Link href={route('groups.show', group.id)} className="text-sm text-zinc-400 hover:text-zinc-200">
                            Cancel
                        </Link>
                    </div>
                </form>

                <AiIdeaPanel
                    domain="world"
                    endpoint={route('groups.ai.worlds', group.id)}
                    title="Summon a world seed"
                    description="Feed the mentor a few themes or keywords. It will propose a name, summary, cadence, and art prompt you can apply instantly."
                    context={{
                        group_name: group.name,
                        name: data.name,
                        summary: data.summary,
                        description: data.description,
                        turn_duration: data.default_turn_duration_hours,
                    }}
                    actions={[
                        {
                            label: 'Apply to form',
                            onApply: applyIdea,
                        },
                        {
                            label: 'Use as summary',
                            onApply: (result: AiIdeaResult) => {
                                const structuredSummary = result.structured?.summary;
                                setData('summary', typeof structuredSummary === 'string' ? structuredSummary : result.text);
                            },
                        },
                    ]}
                />
            </div>
        </AppLayout>
    );
}
