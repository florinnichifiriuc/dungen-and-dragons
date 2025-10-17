import { router } from '@inertiajs/react';
import { FormEvent, useState } from 'react';
import { Download, Link2, RefreshCcw } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

type ExportSettings = {
    request_route: string;
    formats: string[];
    visibility_modes: string[];
    recent_exports: {
        id: number;
        format: string;
        visibility_mode: string;
        status: string;
        completed_at?: string | null;
        download_url?: string | null;
    }[];
    webhooks: {
        id: number;
        url: string;
        active: boolean;
        call_count: number;
        last_triggered_at?: string | null;
    }[];
    webhook_route: string;
};

type ConditionTransparencyExportPanelProps = {
    groupId: number;
    canManage: boolean;
    settings: ExportSettings;
    className?: string;
};

const formatTimestamp = (value?: string | null): string => {
    if (!value) {
        return '—';
    }

    try {
        return new Intl.DateTimeFormat('en-US', {
            dateStyle: 'medium',
            timeStyle: 'short',
        }).format(new Date(value));
    } catch (error) {
        return value;
    }
};

export default function ConditionTransparencyExportPanel({
    groupId,
    canManage,
    settings,
    className,
}: ConditionTransparencyExportPanelProps) {
    const [format, setFormat] = useState(settings.formats[0] ?? 'csv');
    const [visibilityMode, setVisibilityMode] = useState(settings.visibility_modes[0] ?? 'counts');
    const [since, setSince] = useState<string>('');
    const [webhookUrl, setWebhookUrl] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);

    const requestExport = (event: FormEvent) => {
        event.preventDefault();

        if (!canManage) {
            return;
        }

        setIsSubmitting(true);
        router.post(
            settings.request_route,
            {
                format,
                visibility_mode: visibilityMode,
                since: since || undefined,
            },
            {
                preserveScroll: true,
                onFinish: () => setIsSubmitting(false),
            },
        );
    };

    const submitWebhook = (event: FormEvent) => {
        event.preventDefault();

        if (!canManage || webhookUrl.trim() === '') {
            return;
        }

        router.post(
            settings.webhook_route,
            { url: webhookUrl.trim() },
            {
                preserveScroll: true,
                onSuccess: () => setWebhookUrl(''),
            },
        );
    };

    const rotateWebhook = (webhookId: number) => {
        router.post(
            route('groups.condition-transparency.webhooks.rotate', { group: groupId, webhook: webhookId }),
            {},
            { preserveScroll: true },
        );
    };

    const deleteWebhook = (webhookId: number) => {
        router.delete(
            route('groups.condition-transparency.webhooks.destroy', { group: groupId, webhook: webhookId }),
            { preserveScroll: true },
        );
    };

    return (
        <section
            className={cn(
                'rounded-xl border border-zinc-800/80 bg-zinc-950/70 p-6 text-sm text-zinc-200 shadow-inner shadow-black/40',
                className,
            )}
        >
            <header className="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h3 className="text-lg font-semibold">Condition transparency exports</h3>
                    <p className="text-xs text-zinc-500">
                        Generate CSV or JSON exports with consent-safe condition data. Completed exports arrive via email and can
                        notify downstream systems via webhook.
                    </p>
                </div>
            </header>

            <form onSubmit={requestExport} className="mt-4 grid gap-3 md:grid-cols-3">
                <label className="flex flex-col gap-1 text-xs text-zinc-400">
                    Format
                    <select
                        className="rounded-md border border-zinc-700 bg-zinc-900 px-2 py-1 text-sm text-zinc-100"
                        value={format}
                        onChange={(event) => setFormat(event.target.value)}
                        disabled={!canManage}
                    >
                        {settings.formats.map((value) => (
                            <option key={value} value={value}>
                                {value.toUpperCase()}
                            </option>
                        ))}
                    </select>
                </label>
                <label className="flex flex-col gap-1 text-xs text-zinc-400">
                    Visibility mode
                    <select
                        className="rounded-md border border-zinc-700 bg-zinc-900 px-2 py-1 text-sm text-zinc-100"
                        value={visibilityMode}
                        onChange={(event) => setVisibilityMode(event.target.value)}
                        disabled={!canManage}
                    >
                        {settings.visibility_modes.map((value) => (
                            <option key={value} value={value}>
                                {value === 'details' ? 'Full details' : 'Anonymized counts'}
                            </option>
                        ))}
                    </select>
                </label>
                <label className="flex flex-col gap-1 text-xs text-zinc-400">
                    Since (optional)
                    <input
                        type="datetime-local"
                        value={since}
                        onChange={(event) => setSince(event.target.value)}
                        className="rounded-md border border-zinc-700 bg-zinc-900 px-2 py-1 text-sm text-zinc-100"
                        disabled={!canManage}
                    />
                </label>
                <div className="md:col-span-3">
                    <Button type="submit" disabled={!canManage || isSubmitting}>
                        Request export
                    </Button>
                </div>
            </form>

            <div className="mt-6 space-y-3">
                <h4 className="text-sm font-semibold text-zinc-100">Recent exports</h4>
                {settings.recent_exports.length === 0 ? (
                    <p className="text-xs text-zinc-500">No exports queued yet.</p>
                ) : (
                    <ul className="space-y-2">
                        {settings.recent_exports.map((item) => (
                            <li
                                key={item.id}
                                className="flex flex-col gap-2 rounded-lg border border-zinc-800/60 bg-zinc-950/80 p-3 text-xs md:flex-row md:items-center md:justify-between"
                            >
                                <div>
                                    <p className="font-medium text-zinc-200">
                                        #{item.id} • {item.format.toUpperCase()} ({item.visibility_mode})
                                    </p>
                                    <p className="text-[11px] text-zinc-500">
                                        Status: {item.status}{' '}
                                        {item.completed_at && `• Completed ${formatTimestamp(item.completed_at)}`}
                                    </p>
                                </div>
                                {item.download_url && (
                                    <Button
                                        asChild
                                        size="sm"
                                        variant="outline"
                                        className="border-zinc-700 text-amber-200 hover:border-amber-400/60 hover:text-amber-100"
                                    >
                                        <a href={item.download_url}>
                                            <Download className="mr-2 h-4 w-4" aria-hidden /> Download
                                        </a>
                                    </Button>
                                )}
                            </li>
                        ))}
                    </ul>
                )}
            </div>

            <div className="mt-6 space-y-3">
                <h4 className="text-sm font-semibold text-zinc-100">Webhook subscriptions</h4>
                <p className="text-xs text-zinc-500">
                    We call each webhook when an export completes. Payloads include a signed SHA-256 hash for verification.
                </p>
                <form onSubmit={submitWebhook} className="flex flex-col gap-2 md:flex-row md:items-center">
                    <input
                        type="url"
                        placeholder="https://example.com/webhooks/condition-transparency"
                        value={webhookUrl}
                        onChange={(event) => setWebhookUrl(event.target.value)}
                        className="flex-1 rounded-md border border-zinc-700 bg-zinc-900 px-3 py-2 text-sm text-zinc-100"
                        disabled={!canManage}
                    />
                    <Button type="submit" size="sm" disabled={!canManage}>
                        Add webhook
                    </Button>
                </form>
                {settings.webhooks.length === 0 ? (
                    <p className="text-xs text-zinc-500">No active webhooks.</p>
                ) : (
                    <ul className="space-y-2 text-xs">
                        {settings.webhooks.map((webhook) => (
                            <li
                                key={webhook.id}
                                className="flex flex-col gap-2 rounded-lg border border-zinc-800/60 bg-zinc-950/80 p-3 md:flex-row md:items-center md:justify-between"
                            >
                                <div className="space-y-1">
                                    <p className="break-all text-zinc-200">{webhook.url}</p>
                                    <p className="text-[11px] text-zinc-500">
                                        Calls: {webhook.call_count} • Last trigger {formatTimestamp(webhook.last_triggered_at)}
                                    </p>
                                </div>
                                {canManage && (
                                    <div className="flex items-center gap-2">
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            onClick={() => rotateWebhook(webhook.id)}
                                            className="border-zinc-700 text-zinc-300 hover:border-amber-400/70 hover:text-amber-200"
                                        >
                                            <RefreshCcw className="mr-2 h-4 w-4" aria-hidden /> Rotate secret
                                        </Button>
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            onClick={() => deleteWebhook(webhook.id)}
                                            className="border-rose-500/30 text-rose-200 hover:border-rose-400/50"
                                        >
                                            Remove
                                        </Button>
                                    </div>
                                )}
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </section>
    );
}
