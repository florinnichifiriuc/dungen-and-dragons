import { useState } from 'react';

import { usePage } from '@inertiajs/react';

import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';

export type AiIdeaResult = {
    summary: string;
    fields: Record<string, unknown>;
    tips?: string[];
    image_prompt?: string | null;
};

type AiIdeaPanelProps = {
    endpoint: string;
    title: string;
    description: string;
    placeholder?: string;
    submitLabel?: string;
    applyLabel?: string;
    onApply?: (fields: Record<string, unknown>, result: AiIdeaResult) => void;
};

export function AiIdeaPanel({
    endpoint,
    title,
    description,
    placeholder = 'Share a few keywords, tone, or constraints for the AI mentor…',
    submitLabel = 'Summon idea',
    applyLabel = 'Apply to form',
    onApply,
}: AiIdeaPanelProps) {
    const { props } = usePage<{ csrf_token?: string }>();
    const token = typeof props.csrf_token === 'string' ? props.csrf_token : undefined;

    const [prompt, setPrompt] = useState('');
    const [result, setResult] = useState<AiIdeaResult | null>(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const requestIdea = async () => {
        if (loading) {
            return;
        }

        setLoading(true);
        setError(null);

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    ...(token ? { 'X-CSRF-TOKEN': token } : {}),
                },
                body: JSON.stringify({ prompt }),
            });

            if (!response.ok) {
                throw new Error(`AI request failed (${response.status})`);
            }

            const payload = (await response.json()) as { summary: string; fields: Record<string, unknown>; tips?: string[]; image_prompt?: string | null };

            setResult({
                summary: payload.summary,
                fields: payload.fields ?? {},
                tips: payload.tips ?? [],
                image_prompt: payload.image_prompt ?? null,
            });
        } catch (exception) {
            console.error(exception);
            setError(exception instanceof Error ? exception.message : 'Unexpected error');
        } finally {
            setLoading(false);
        }
    };

    const handleApply = () => {
        if (result && onApply) {
            onApply(result.fields, result);
        }
    };

    return (
        <section className="rounded-xl border border-indigo-700/60 bg-indigo-950/40 p-4 text-sm text-indigo-100 shadow-inner shadow-indigo-900/40">
            <header className="flex items-start justify-between gap-3">
                <div>
                    <h3 className="text-base font-semibold text-indigo-100">{title}</h3>
                    <p className="mt-1 text-xs text-indigo-200/80">{description}</p>
                </div>
                <Button
                    type="button"
                    variant="outline"
                    className="border-indigo-500/70 text-xs text-indigo-200 hover:bg-indigo-500/20"
                    onClick={requestIdea}
                    disabled={loading}
                >
                    {loading ? 'Consulting oracles…' : submitLabel}
                </Button>
            </header>

            <div className="mt-4 space-y-3">
                <label className="block text-xs uppercase tracking-wide text-indigo-200" htmlFor={`${title}-prompt`}>
                    Guidance prompt
                </label>
                <Textarea
                    id={`${title}-prompt`}
                    value={prompt}
                    onChange={(event) => setPrompt(event.target.value)}
                    placeholder={placeholder}
                    className="min-h-[80px] border-indigo-700/60 bg-indigo-950/60 text-sm text-indigo-100 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/40"
                />
                {error && <p className="text-xs text-rose-300">{error}</p>}
            </div>

            {result && (
                <div className="mt-4 space-y-3 rounded-lg border border-indigo-700/40 bg-indigo-900/40 p-4">
                    <p className="whitespace-pre-wrap text-sm text-indigo-100">{result.summary}</p>
                    {result.tips && result.tips.length > 0 && (
                        <ul className="list-disc space-y-1 pl-5 text-xs text-indigo-200/90">
                            {result.tips.map((tip, index) => (
                                <li key={index}>{tip}</li>
                            ))}
                        </ul>
                    )}
                    {result.image_prompt && (
                        <div className="rounded-md border border-indigo-700/50 bg-indigo-950/40 p-3 text-xs text-indigo-200/80">
                            <p className="font-semibold uppercase tracking-wide text-indigo-200">Image prompt</p>
                            <p className="mt-1 whitespace-pre-wrap">{result.image_prompt}</p>
                        </div>
                    )}
                    {onApply && (
                        <Button
                            type="button"
                            className="bg-indigo-500/20 text-xs text-indigo-100 hover:bg-indigo-500/30"
                            onClick={handleApply}
                        >
                            {applyLabel}
                        </Button>
                    )}
                </div>
            )}
        </section>
    );
}
