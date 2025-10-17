import { Link, router } from '@inertiajs/react';
import { CalendarClock, Link2, RefreshCcw } from 'lucide-react';
import { FormEvent, useCallback, useMemo, useState } from 'react';

import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { useTranslations } from '@/hooks/useTranslations';

export type ConditionTimerShareResource = {
    id: number;
    url: string;
    created_at: string | null;
    expires_at: string | null;
    visibility_mode?: string | null;
    access_count?: number;
    last_accessed_at?: string | null;
    state?: {
        state: string;
        relative?: string | null;
        redacted?: boolean;
    } | null;
    access_trend?: { date: string; count: number }[];
    extend_route?: string | null;
    redacted?: boolean;
};

type ConsentStatus = {
    user_id: number;
    user_name: string;
    role: string;
    status: string;
    visibility: string | null;
    recorded_at?: string | null;
    recorded_by?: { id: number | null; name: string | null } | null;
};

type ConsentAuditEntry = {
    id: number;
    action: string;
    visibility: string;
    recorded_at?: string | null;
    notes?: string | null;
    subject?: { id: number | null; name: string | null } | null;
    actor?: { id: number | null; name: string | null } | null;
};

type ConditionTimerShareLinkControlsProps = {
    groupId: number;
    share: ConditionTimerShareResource | null;
    canManage: boolean;
    className?: string;
    settings: {
        expiry_presets: { key: string; label: string }[];
        visibility_modes: { key: string; label: string }[];
        consents: ConsentStatus[];
        audit_log: ConsentAuditEntry[];
        consent_route: string;
        extend_presets: { key: string; label: string }[];
    };
};

export function ConditionTimerShareLinkControls({
    groupId,
    share,
    canManage,
    className,
    settings,
}: ConditionTimerShareLinkControlsProps) {
    const { t, locale } = useTranslations('condition_timers');
    const [isProcessing, setIsProcessing] = useState(false);
    const [isExtending, setIsExtending] = useState(false);
    const [expiryPreset, setExpiryPreset] = useState<string>(settings.expiry_presets[0]?.key ?? '24h');
    const [customHours, setCustomHours] = useState('');
    const [visibilityMode, setVisibilityMode] = useState<string>(settings.visibility_modes[0]?.key ?? 'counts');
    const [extendPreset, setExtendPreset] = useState<string>(settings.extend_presets[0]?.key ?? '24h');
    const [extendCustomHours, setExtendCustomHours] = useState('');

    const formatTimestamp = useCallback(
        (value: string | null): string => {
            if (!value) {
                return t('generic.unknown');
            }

        try {
            return new Intl.DateTimeFormat(locale, {
                dateStyle: 'medium',
                timeStyle: 'short',
            }).format(new Date(value));
        } catch {
            return value ?? t('generic.unknown');
        }
        },
        [locale, t]
    );

    const formatRelative = useCallback(
        (value: string | null): string | null => {
            if (!value) {
                return null;
            }

            const parsed = Date.parse(value);

            if (Number.isNaN(parsed)) {
                return null;
            }

            const diffMilliseconds = parsed - Date.now();
            const diffSeconds = Math.round(diffMilliseconds / 1000);
            const formatter = new Intl.RelativeTimeFormat(locale, { numeric: 'auto' });

            if (Math.abs(diffSeconds) < 60) {
                return formatter.format(diffSeconds, 'second');
            }

            const diffMinutes = Math.round(diffSeconds / 60);

            if (Math.abs(diffMinutes) < 60) {
                return formatter.format(diffMinutes, 'minute');
            }

            const diffHours = Math.round(diffMinutes / 60);

            if (Math.abs(diffHours) < 48) {
                return formatter.format(diffHours, 'hour');
            }

            const diffDays = Math.round(diffHours / 24);

            return formatter.format(diffDays, 'day');
        },
        [locale]
    );

    const expiresLabel = useMemo(() => {
        if (!share?.expires_at) {
            return null;
        }

        const formatted = formatTimestamp(share.expires_at);
        const relative = formatRelative(share.expires_at);

        return relative ? `${formatted} (${relative})` : formatted;
    }, [formatRelative, formatTimestamp, share?.expires_at]);

    const createdLabel = useMemo(() => {
        if (!share?.created_at) {
            return null;
        }

        return formatTimestamp(share.created_at);
    }, [formatTimestamp, share?.created_at]);

    const accessLabel = useMemo(() => {
        if (!share?.last_accessed_at) {
            return null;
        }

        const timestamp = formatTimestamp(share.last_accessed_at);
        const count = share.access_count ?? 0;

        return t('share_controls.link.access', undefined, { timestamp, count });
    }, [formatTimestamp, share?.access_count, share?.last_accessed_at, t]);

    const shareState = useMemo(() => {
        if (!share?.state) {
            return null;
        }

        const palette: Record<string, { className: string }> = {
            evergreen: { className: 'bg-emerald-500/20 text-emerald-200 border-emerald-500/40' },
            active: { className: 'bg-emerald-500/20 text-emerald-200 border-emerald-500/40' },
            expiring_soon: { className: 'bg-amber-500/20 text-amber-200 border-amber-500/40' },
            expired: { className: 'bg-rose-500/20 text-rose-200 border-rose-500/40' },
        };

        const tone = palette[share.state.state] ?? palette.active;
        const label = t(`share_controls.states.${share.state.state}`, t('share_controls.states.active'));

        return {
            ...tone,
            label,
            relative: share.state.relative ?? null,
            redacted: Boolean(share.state.redacted),
        };
    }, [share?.state, t]);

    const weeklyVisitCount = useMemo(() => {
        if (!share?.access_trend) {
            return 0;
        }

        return share.access_trend.reduce((total, entry) => total + entry.count, 0);
    }, [share?.access_trend]);

    const visitSummary = useMemo(
        () => t('share_controls.insights.summary', undefined, { count: weeklyVisitCount }),
        [t, weeklyVisitCount]
    );

    const formatTrendDate = useCallback(
        (value: string) => {
        try {
            return new Intl.DateTimeFormat(locale, {
                weekday: 'short',
                month: 'short',
                day: 'numeric',
            }).format(new Date(`${value}T00:00:00Z`));
        } catch {
            return value;
        }
        },
        [locale]
    );

    const generateShare = (event: FormEvent) => {
        event.preventDefault();

        if (!canManage || isProcessing) {
            return;
        }

        setIsProcessing(true);
        router.post(
            route('groups.condition-timers.player-summary.share-links.store', groupId),
            {
                expiry_preset: expiryPreset,
                expires_in_hours: expiryPreset === 'custom' ? Number(customHours) || null : null,
                visibility_mode: visibilityMode,
            },
            {
                preserveScroll: true,
                onFinish: () => setIsProcessing(false),
            },
        );
    };

    const extendShare = (event: FormEvent) => {
        event.preventDefault();

        if (!canManage || !share || !share.extend_route || isExtending) {
            return;
        }

        setIsExtending(true);
        router.patch(
            share.extend_route,
            {
                expiry_preset: extendPreset,
                extends_in_hours: extendPreset === 'custom' ? Number(extendCustomHours) || null : null,
            },
            {
                preserveScroll: true,
                onFinish: () => setIsExtending(false),
            },
        );
    };

    const revokeShare = () => {
        if (!canManage || !share || isProcessing) {
            return;
        }

        setIsProcessing(true);
        router.delete(
            route('groups.condition-timers.player-summary.share-links.destroy', {
                group: groupId,
                share: share.id,
            }),
            {
                preserveScroll: true,
                onFinish: () => setIsProcessing(false),
            },
        );
    };

    const recordConsent = (userId: number, consented: boolean, visibility: string) => {
        router.post(
            settings.consent_route,
            {
                user_id: userId,
                consented,
                visibility_mode: visibility,
            },
            { preserveScroll: true },
        );
    };

    const shareVisibilityLabel = share?.visibility_mode
        ? t(`share_controls.link.visibility_modes.${share.visibility_mode}`, share.visibility_mode)
        : null;

    return (
        <section
            className={cn(
                'rounded-xl border border-zinc-800 bg-zinc-950/60 p-6 shadow-inner shadow-black/40',
                className,
            )}
        >
            <header className="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h3 className="flex items-center gap-2 text-lg font-semibold">
                        <Link2 className="h-4 w-4 text-amber-300" aria-hidden /> {t('share_controls.title')}
                    </h3>
                    <p className="text-xs text-zinc-500">{t('share_controls.description')}</p>
                </div>
            </header>

            {share ? (
                <div className="mt-4 space-y-2 text-sm" role="status" aria-live="polite">
                    <div className="flex items-center gap-2 break-all">
                        <Link2 className="h-4 w-4 text-zinc-400" aria-hidden />
                        <Link
                            href={share.url}
                            target="_blank"
                            rel="noreferrer"
                            className="text-amber-300 underline decoration-dotted underline-offset-4 hover:text-amber-200"
                        >
                            {share.url}
                        </Link>
                    </div>
                    {shareState && (
                        <div className="flex flex-wrap items-center gap-2 text-xs">
                            <span
                                className={cn(
                                    'inline-flex items-center gap-2 rounded-full border px-3 py-1 font-medium',
                                    shareState.className,
                                )}
                            >
                                {shareState.label}
                                {shareState.relative && (
                                    <span className="text-[11px] text-zinc-300/70">{shareState.relative}</span>
                                )}
                            </span>
                        </div>
                    )}
                    {createdLabel && (
                        <div className="flex items-center gap-2 text-xs text-zinc-500">
                            <CalendarClock className="h-4 w-4" aria-hidden />
                            <span>{t('share_controls.link.generated', createdLabel, { timestamp: createdLabel })}</span>
                        </div>
                    )}
                    {expiresLabel && (
                        <div className="flex items-center gap-2 text-xs text-zinc-500">
                            <CalendarClock className="h-4 w-4" aria-hidden />
                            <span>{t('share_controls.link.expires', expiresLabel, { timestamp: expiresLabel })}</span>
                        </div>
                    )}
                    {share.visibility_mode && shareVisibilityLabel && (
                        <p className="text-xs text-zinc-500">
                            {t('share_controls.link.visibility', undefined, { mode: shareVisibilityLabel })}
                        </p>
                    )}
                    {accessLabel && <p className="text-xs text-zinc-500">{accessLabel}</p>}
                    {shareState?.redacted && (
                        <p className="text-xs text-rose-300">{t('share_controls.link.redacted')}</p>
                    )}
                </div>
            ) : (
                <p className="mt-4 text-xs text-zinc-500">{t('share_controls.no_share')}</p>
            )}

            {canManage && (
                <div className="mt-4 space-y-4">
                    <form onSubmit={generateShare} className="space-y-4">
                        <div className="grid gap-3 md:grid-cols-3">
                            <label className="flex flex-col gap-1 text-xs text-zinc-400">
                                {t('share_controls.form.expiry_preset')}
                                <select
                                    value={expiryPreset}
                                    onChange={(event) => setExpiryPreset(event.target.value)}
                                    className="rounded-md border border-zinc-700 bg-zinc-900 px-2 py-1 text-sm text-zinc-100"
                                >
                                    {settings.expiry_presets.map((preset) => (
                                        <option key={preset.key} value={preset.key}>
                                            {preset.label}
                                        </option>
                                    ))}
                                </select>
                            </label>
                            {expiryPreset === 'custom' && (
                                <label className="flex flex-col gap-1 text-xs text-zinc-400">
                                    {t('share_controls.form.custom_hours')}
                                    <input
                                        type="number"
                                        min={1}
                                        max={336}
                                        value={customHours}
                                        onChange={(event) => setCustomHours(event.target.value)}
                                        className="rounded-md border border-zinc-700 bg-zinc-900 px-2 py-1 text-sm text-zinc-100"
                                    />
                                </label>
                            )}
                            <label className="flex flex-col gap-1 text-xs text-zinc-400">
                                {t('share_controls.form.guest_visibility')}
                                <select
                                    value={visibilityMode}
                                    onChange={(event) => setVisibilityMode(event.target.value)}
                                    className="rounded-md border border-zinc-700 bg-zinc-900 px-2 py-1 text-sm text-zinc-100"
                                >
                                    {settings.visibility_modes.map((mode) => (
                                        <option key={mode.key} value={mode.key}>
                                            {mode.label}
                                        </option>
                                    ))}
                                </select>
                            </label>
                        </div>
                        <div className="flex flex-wrap items-center gap-3">
                            <Button type="submit" size="sm" disabled={isProcessing}>
                                {share
                                    ? t('share_controls.form.regenerate')
                                    : t('share_controls.form.generate')}
                            </Button>
                            {share && (
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={revokeShare}
                                    disabled={isProcessing}
                                    className="border-zinc-700 text-zinc-300 hover:border-rose-500/50 hover:text-rose-200"
                                >
                                    <RefreshCcw className="mr-2 h-4 w-4" aria-hidden />
                                    {t('share_controls.form.disable')}
                                </Button>
                            )}
                        </div>
                    </form>

                    {share && share.extend_route && (
                        <form onSubmit={extendShare} className="rounded-lg border border-zinc-800/70 bg-zinc-950/80 p-4">
                            <div className="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
                                <div className="flex-1">
                                    <p className="text-xs font-semibold text-zinc-200">{t('share_controls.extend.title')}</p>
                                    <p className="text-[11px] text-zinc-500">{t('share_controls.extend.description')}</p>
                                </div>
                                <div className="flex flex-1 flex-col gap-2 md:flex-row md:items-center md:justify-end">
                                    <label className="flex flex-col gap-1 text-xs text-zinc-400">
                                        {t('share_controls.form.extension_preset')}
                                        <select
                                            value={extendPreset}
                                            onChange={(event) => setExtendPreset(event.target.value)}
                                            className="rounded-md border border-zinc-700 bg-zinc-900 px-2 py-1 text-sm text-zinc-100"
                                        >
                                            {settings.extend_presets.map((preset) => (
                                                <option key={preset.key} value={preset.key}>
                                                    {preset.label}
                                                </option>
                                            ))}
                                        </select>
                                    </label>
                                    {extendPreset === 'custom' && (
                                        <label className="flex flex-col gap-1 text-xs text-zinc-400">
                                            {t('share_controls.form.extension_hours')}
                                            <input
                                                type="number"
                                                min={1}
                                                max={336}
                                                value={extendCustomHours}
                                                onChange={(event) => setExtendCustomHours(event.target.value)}
                                                className="rounded-md border border-zinc-700 bg-zinc-900 px-2 py-1 text-sm text-zinc-100"
                                            />
                                        </label>
                                    )}
                                    <Button type="submit" size="sm" disabled={isExtending}>
                                        {extendPreset === 'never'
                                            ? t('share_controls.form.make_evergreen')
                                            : t('share_controls.form.extend')}
                                    </Button>
                                </div>
                            </div>
                        </form>
                    )}
                </div>
            )}

            {share?.access_trend && share.access_trend.length > 0 && (
                <div className="mt-6 space-y-3 rounded-xl border border-zinc-900/60 bg-zinc-950/70 p-4">
                    <div className="flex flex-col gap-1 md:flex-row md:items-center md:justify-between">
                        <h4 className="text-sm font-semibold text-zinc-100">{t('share_controls.insights.title')}</h4>
                        <p className="text-[11px] text-zinc-500">{visitSummary}</p>
                    </div>
                    <ul className="grid gap-2 md:grid-cols-2">
                        {share.access_trend.map((entry) => (
                            <li
                                key={entry.date}
                                className="flex items-center justify-between rounded-lg border border-zinc-900 bg-zinc-950/80 px-3 py-2 text-xs"
                            >
                                <span className="text-zinc-400">{formatTrendDate(entry.date)}</span>
                                <span className="font-semibold text-zinc-100">{entry.count}</span>
                            </li>
                        ))}
                    </ul>
                    <p className="text-[11px] text-zinc-500">{t('share_controls.insights.footnote')}</p>
                </div>
            )}

            <div className="mt-6 space-y-4">
                <h4 className="text-sm font-semibold text-zinc-100">{t('share_controls.consent.title')}</h4>
                {settings.consents.length === 0 ? (
                    <p className="text-xs text-zinc-500">{t('share_controls.consent.empty')}</p>
                ) : (
                    <ul className="space-y-2 text-xs">
                        {settings.consents.map((entry) => {
                            const roleStatus = t('share_controls.consent.role_status', undefined, {
                                role: entry.role,
                                status: entry.status,
                            });
                            const visibilitySuffix = entry.visibility
                                ? t('share_controls.consent.visibility_suffix', undefined, {
                                      visibility: entry.visibility,
                                  })
                                : '';
                            const recordedAt = entry.recorded_at
                                ? t('share_controls.consent.recorded_at', undefined, {
                                      timestamp: formatTimestamp(entry.recorded_at),
                                  })
                                : null;

                            return (
                                <li
                                    key={entry.user_id}
                                    className="flex flex-col gap-2 rounded-lg border border-zinc-800/60 bg-zinc-950/80 p-3 md:flex-row md:items-center md:justify-between"
                                >
                                    <div>
                                        <p className="font-medium text-zinc-200">{entry.user_name}</p>
                                        <p className="text-[11px] text-zinc-500">
                                            {roleStatus}
                                            {visibilitySuffix}
                                            {recordedAt ? ` • ${recordedAt}` : ''}
                                        </p>
                                    </div>
                                    {canManage && (
                                        <div className="flex flex-wrap items-center gap-2">
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                className="border-zinc-700 text-zinc-300 hover:border-emerald-400/50 hover:text-emerald-200"
                                                onClick={() => recordConsent(entry.user_id, true, 'counts')}
                                            >
                                                {t('share_controls.consent.actions.allow_counts')}
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                className="border-zinc-700 text-zinc-300 hover:border-amber-400/50 hover:text-amber-200"
                                                onClick={() => recordConsent(entry.user_id, true, 'details')}
                                            >
                                                {t('share_controls.consent.actions.allow_details')}
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                className="border-rose-500/40 text-rose-200 hover:border-rose-400/50"
                                                onClick={() => recordConsent(entry.user_id, false, 'counts')}
                                            >
                                                {t('share_controls.consent.actions.revoke')}
                                            </Button>
                                        </div>
                                    )}
                                </li>
                            );
                        })}
                    </ul>
                )}
            </div>

            <div className="mt-6 space-y-3">
                <h4 className="text-sm font-semibold text-zinc-100">{t('share_controls.audit.title')}</h4>
                {settings.audit_log.length === 0 ? (
                    <p className="text-xs text-zinc-500">{t('share_controls.audit.empty')}</p>
                ) : (
                    <ul className="space-y-2 text-xs text-zinc-400">
                        {settings.audit_log.map((entry) => {
                            const subject = entry.subject?.name ?? t('share_controls.audit.unknown_subject');
                            const actor = entry.actor?.name ?? t('share_controls.audit.system_actor');
                            const timestamp = entry.recorded_at
                                ? formatTimestamp(entry.recorded_at)
                                : t('share_controls.audit.unknown_time');

                            return (
                                <li key={entry.id} className="rounded-md border border-zinc-800/40 bg-zinc-950/80 p-3">
                                    <p>
                                        {t('share_controls.audit.entry', undefined, {
                                            action: entry.action,
                                            subject,
                                            visibility: entry.visibility,
                                        })}
                                    </p>
                                    <p className="text-[11px]">
                                        {t('share_controls.audit.timestamp', undefined, {
                                            timestamp,
                                            actor,
                                        })}
                                    </p>
                                    {entry.notes && <p className="text-[11px] text-zinc-500">{entry.notes}</p>}
                                </li>
                            );
                        })}
                    </ul>
                )}
            </div>
        </section>
    );
}

export default ConditionTimerShareLinkControls;
