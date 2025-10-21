import { useMemo, useState } from 'react';

import { usePage } from '@inertiajs/react';

import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { recordAnalyticsEvent } from '@/lib/analytics';

export type CompanionMessage = {
    id: string;
    role: 'user' | 'assistant';
    content: string;
    structured?: Record<string, unknown> | null;
    createdAt: string;
};

export type CompanionPreset = {
    label: string;
    prompt: string;
};

export type AiCompanionDrawerProps = {
    domain: string;
    title: string;
    description?: string;
    context?: Record<string, unknown>;
    presets?: CompanionPreset[];
    onApply?: (result: { text: string; structured?: Record<string, unknown> | null }) => void;
    className?: string;
};

type PageProps = {
    csrf_token?: string;
};

export function AiCompanionDrawer({
    domain,
    title,
    description,
    context = {},
    presets = [],
    onApply,
    className,
}: AiCompanionDrawerProps) {
    const { props } = usePage<PageProps>();
    const csrfToken = props.csrf_token ?? document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

    const [open, setOpen] = useState(false);
    const [messages, setMessages] = useState<CompanionMessage[]>([]);
    const [prompt, setPrompt] = useState('');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [lastPreset, setLastPreset] = useState<string | null>(null);

    const latestAssistantMessage = useMemo(() => {
        return [...messages].reverse().find((message) => message.role === 'assistant') ?? null;
    }, [messages]);

    const endpoint = route('ai.assist');

    const sendPrompt = async (overridePrompt?: string) => {
        const messageText = overridePrompt ?? prompt;
        const trimmed = messageText.trim();

        if (trimmed === '') {
            setError('Share a question or choose a preset before summoning the steward.');
            return;
        }

        setLoading(true);
        setError(null);

        const userMessage: CompanionMessage = {
            id: `user-${Date.now()}`,
            role: 'user',
            content: trimmed,
            createdAt: new Date().toISOString(),
        };

        const history = [...messages, userMessage].map((entry) => ({ role: entry.role, content: entry.content }));

        try {
            setMessages((current) => [...current, userMessage]);

            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    Accept: 'application/json',
                },
                body: JSON.stringify({
                    domain,
                    prompt: trimmed,
                    context: {
                        ...context,
                        history,
                        preset: lastPreset,
                    },
                }),
            });

            if (!response.ok) {
                throw new Error('The companion is busy tending another realm. Try again shortly.');
            }

            const data = (await response.json()) as { idea: string; structured?: Record<string, unknown> | null };
            const assistantMessage: CompanionMessage = {
                id: `assistant-${Date.now()}`,
                role: 'assistant',
                content: data.idea,
                structured: data.structured ?? null,
                createdAt: new Date().toISOString(),
            };

            setMessages((current) => [...current, assistantMessage]);
            setPrompt('');
        } catch (exception) {
            setError(exception instanceof Error ? exception.message : 'Something disrupted the ritual.');
        } finally {
            setLoading(false);
        }
    };

    const handlePreset = (preset: CompanionPreset) => {
        setLastPreset(preset.label);
        setPrompt(preset.prompt);
        void sendPrompt(preset.prompt);
    };

    const handleApply = () => {
        if (!latestAssistantMessage) {
            return;
        }

        onApply?.({
            text: latestAssistantMessage.content,
            structured: latestAssistantMessage.structured ?? undefined,
        });
    };

    const handleFeedback = async (sentiment: 'up' | 'down') => {
        if (!latestAssistantMessage) {
            return;
        }

        await recordAnalyticsEvent({
            key: 'ai_companion.feedback',
            payload: {
                domain,
                sentiment,
                message: latestAssistantMessage.content.slice(0, 200),
                preset: lastPreset,
            },
        });
    };

    return (
        <div className={className}>
            <Button
                type="button"
                variant={open ? 'secondary' : 'outline'}
                className={open ? 'bg-indigo-500/20 text-indigo-100 hover:bg-indigo-500/30' : 'border-indigo-500/60 text-indigo-200'}
                onClick={() => setOpen((value) => !value)}
            >
                {open ? 'Hide companion' : 'Open AI companion'}
            </Button>

            {open && (
                <div className="mt-4 space-y-4 rounded-xl border border-indigo-700/40 bg-indigo-950/50 p-4 shadow-lg shadow-black/30">
                    <header className="space-y-1">
                        <h3 className="text-lg font-semibold text-indigo-100">{title}</h3>
                        {description && <p className="text-sm text-indigo-200/80">{description}</p>}
                    </header>

                    {presets.length > 0 && (
                        <div className="flex flex-wrap gap-2">
                            {presets.map((preset) => (
                                <Button
                                    key={preset.label}
                                    type="button"
                                    size="sm"
                                    variant="outline"
                                    className="border-amber-500/50 text-xs text-amber-200 hover:bg-amber-500/20"
                                    disabled={loading}
                                    onClick={() => handlePreset(preset)}
                                >
                                    {preset.label}
                                </Button>
                            ))}
                        </div>
                    )}

                    <Textarea
                        value={prompt}
                        onChange={(event) => setPrompt(event.target.value)}
                        rows={3}
                        placeholder="Describe the vibe, goals, or questions you have..."
                        className="border-indigo-700/40 bg-indigo-950/70 text-sm text-indigo-100 placeholder:text-indigo-300/60 focus:border-amber-400 focus:ring-amber-400/40"
                        disabled={loading}
                    />

                    <div className="flex flex-wrap items-center gap-3">
                        <Button type="button" onClick={() => sendPrompt()} disabled={loading} className="bg-amber-500/20 text-amber-100 hover:bg-amber-500/30">
                            {loading ? 'Consulting the steward…' : 'Summon idea'}
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            disabled={!latestAssistantMessage}
                            className="border-emerald-500/40 text-emerald-100 hover:bg-emerald-500/20"
                            onClick={handleApply}
                        >
                            Apply last suggestion
                        </Button>
                        <Button
                            type="button"
                            variant="ghost"
                            disabled={!latestAssistantMessage}
                            className="text-xs text-indigo-200 hover:text-indigo-100"
                            onClick={() => handleFeedback('up')}
                        >
                            👍 Helpful
                        </Button>
                        <Button
                            type="button"
                            variant="ghost"
                            disabled={!latestAssistantMessage}
                            className="text-xs text-indigo-200 hover:text-indigo-100"
                            onClick={() => handleFeedback('down')}
                        >
                            👎 Needs work
                        </Button>
                    </div>

                    {error && <p className="text-sm text-rose-300">{error}</p>}

                    {messages.length > 0 && (
                        <div className="max-h-80 space-y-3 overflow-y-auto rounded-lg border border-indigo-800/40 bg-indigo-950/40 p-3 text-sm text-indigo-100">
                            {messages.map((message) => (
                                <article key={message.id} className="space-y-1">
                                    <div className="flex items-center justify-between">
                                        <span className="text-xs uppercase tracking-wide text-indigo-300/80">
                                            {message.role === 'assistant' ? 'Companion' : 'You'}
                                        </span>
                                        <span className="text-[11px] text-indigo-400/70">
                                            {new Date(message.createdAt).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                                        </span>
                                    </div>
                                    <p className="whitespace-pre-wrap leading-5 text-indigo-100/90">{message.content}</p>
                                    {message.role === 'assistant' && message.structured && (
                                        <details className="rounded border border-indigo-800/60 bg-indigo-950/60 p-2 text-xs text-indigo-200">
                                            <summary className="cursor-pointer text-[11px] uppercase tracking-wide text-indigo-300">Structured fields</summary>
                                            <pre className="mt-2 whitespace-pre-wrap break-words font-sans text-xs leading-5 text-indigo-100/80">
                                                {JSON.stringify(message.structured, null, 2)}
                                            </pre>
                                        </details>
                                    )}
                                </article>
                            ))}
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}

export default AiCompanionDrawer;
