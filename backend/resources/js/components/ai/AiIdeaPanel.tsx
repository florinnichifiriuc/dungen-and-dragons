import { useState } from 'react';

import { usePage } from '@inertiajs/react';

import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';

export type AiIdeaResult = {
    text: string;
    structured?: Record<string, unknown> | null;
};

export type AiIdeaAction = {
    label: string;
    onApply: (result: AiIdeaResult) => void;
};

type AiIdeaPanelProps = {
    domain: string;
    title: string;
    description?: string;
    placeholder?: string;
    context?: Record<string, unknown>;
    className?: string;
    actions?: AiIdeaAction[];
    defaultPrompt?: string;
    endpoint: string;
};

type PageProps = {
    csrf_token?: string;
};

export function AiIdeaPanel({
    domain,
    title,
    description,
    placeholder,
    context,
    className,
    actions = [],
    defaultPrompt = '',
    endpoint,
}: AiIdeaPanelProps) {
    const { props } = usePage<PageProps>();
    const csrfToken = props.csrf_token ?? document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

    const [prompt, setPrompt] = useState(defaultPrompt);
    const [result, setResult] = useState<AiIdeaResult | null>(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const handleGenerate = async () => {
        setLoading(true);
        setError(null);

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    Accept: 'application/json',
                },
                body: JSON.stringify({
                    domain,
                    prompt,
                    ...(context ? { context } : {}),
                }),
            });

            if (!response.ok) {
                throw new Error('AI assistant is unavailable right now.');
            }

            const data = (await response.json()) as { idea: string; structured?: Record<string, unknown> | null };
            setResult({ text: data.idea, structured: data.structured ?? null });
        } catch (exception) {
            setError(exception instanceof Error ? exception.message : 'Failed to contact the AI assistant.');
        } finally {
            setLoading(false);
        }
    };

    const handleCopy = (value: string) => {
        navigator.clipboard?.writeText(value).catch(() => {
            setError('Copy to clipboard failed—select and copy manually.');
        });
    };

    return (
        <section className={cn('space-y-4 rounded-xl border border-indigo-700/40 bg-indigo-950/40 p-6', className)}>
            <header className="space-y-1">
                <h2 className="text-lg font-semibold text-indigo-100">{title}</h2>
                {description && <p className="text-sm text-indigo-200/80">{description}</p>}
            </header>

            <div className="space-y-2">
                <label htmlFor={`${domain}-prompt`} className="text-xs uppercase tracking-wide text-indigo-200/70">
                    Give the AI a nudge (optional)
                </label>
                <Textarea
                    id={`${domain}-prompt`}
                    value={prompt}
                    onChange={(event) => setPrompt(event.target.value)}
                    placeholder={placeholder ?? 'A few mood words, objectives, or constraints...'}
                    rows={3}
                    className="border-indigo-700/40 bg-indigo-950/70 text-sm text-indigo-100 placeholder:text-indigo-300/60 focus:border-amber-400 focus:ring-amber-400/40"
                />
            </div>

            <div className="flex flex-wrap items-center gap-3">
                <Button type="button" onClick={handleGenerate} disabled={loading} className="bg-amber-500/20 text-amber-100 hover:bg-amber-500/30">
                    {loading ? 'Summoning ideas…' : 'Summon AI idea'}
                </Button>
                {result && (
                    <Button
                        type="button"
                        variant="outline"
                        className="border-indigo-500/40 text-indigo-100 hover:bg-indigo-900/40"
                        onClick={() => handleCopy(result.text)}
                    >
                        Copy raw response
                    </Button>
                )}
                {actions.map((action) => (
                    <Button
                        key={action.label}
                        type="button"
                        variant="outline"
                        disabled={!result}
                        className="border-emerald-500/40 text-emerald-100 hover:bg-emerald-500/20 disabled:opacity-60"
                        onClick={() => result && action.onApply(result)}
                    >
                        {action.label}
                    </Button>
                ))}
            </div>

            {error && <p className="text-sm text-rose-300">{error}</p>}

            {result && (
                <div className="space-y-4 rounded-lg border border-indigo-700/40 bg-indigo-950/60 p-4">
                    <div>
                        <h3 className="text-sm font-semibold uppercase tracking-wide text-indigo-200">AI suggestion</h3>
                        <p className="mt-2 whitespace-pre-line text-sm text-indigo-100/90">{result.text}</p>
                    </div>

                    {result.structured && (
                        <div className="space-y-3">
                            <h4 className="text-xs uppercase tracking-wide text-indigo-200/80">Structured fields</h4>
                            <div className="space-y-2 text-sm text-indigo-100/90">
                                {Object.entries(result.structured).map(([key, value]) => (
                                    <div key={key} className="rounded-md border border-indigo-700/30 bg-indigo-950/80 p-3">
                                        <div className="flex items-center justify-between gap-4">
                                            <span className="text-xs uppercase tracking-wide text-indigo-300">{key}</span>
                                            {typeof value === 'string' && (
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    className="h-7 text-xs text-amber-200 hover:text-amber-100"
                                                    onClick={() => handleCopy(value)}
                                                >
                                                    Copy
                                                </Button>
                                            )}
                                        </div>
                                        <pre className="mt-2 whitespace-pre-wrap break-words font-sans text-sm leading-5 text-indigo-100/90">
                                            {formatValue(value)}
                                        </pre>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}

                    {result.structured?.image_prompt && typeof result.structured.image_prompt === 'string' && (
                        <div className="rounded-md border border-amber-500/40 bg-amber-500/10 p-3 text-sm text-amber-100">
                            <div className="flex items-center justify-between">
                                <span className="font-semibold">A1111 image prompt</span>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    className="h-7 text-xs text-amber-100 hover:text-amber-50"
                                    onClick={() => handleCopy(String(result.structured?.image_prompt))}
                                >
                                    Copy prompt
                                </Button>
                            </div>
                            <p className="mt-2 whitespace-pre-wrap text-amber-100/90">
                                {String(result.structured.image_prompt)}
                            </p>
                            <p className="mt-2 text-xs uppercase tracking-wide text-amber-200/80">
                                Ready for 512x512 renders in Automatic1111.
                            </p>
                        </div>
                    )}
                </div>
            )}
        </section>
    );
}

function formatValue(value: unknown): string {
    if (typeof value === 'string') {
        return value;
    }

    if (Array.isArray(value)) {
        return value.map((entry) => (typeof entry === 'string' ? `• ${entry}` : JSON.stringify(entry))).join('\n');
    }

    if (typeof value === 'object' && value !== null) {
        return JSON.stringify(value, null, 2);
    }

    return String(value ?? '');
}

export default AiIdeaPanel;
