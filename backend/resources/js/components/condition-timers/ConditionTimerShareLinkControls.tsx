import { Link, router } from '@inertiajs/react';
import { CalendarClock, Link2, RefreshCcw } from 'lucide-react';
import { FormEvent, useCallback, useEffect, useMemo, useState } from 'react';

import { Button } from '@/components/ui/button';
import { InsightCard, InsightList } from '@/components/transparency';
import { cn } from '@/lib/utils';
import { useTranslations } from '@/hooks/useTranslations';

export type ConditionTimerShareResource = {
    id: number;
    url: string;
    created_at: string | null;
    expires_at: string | null;
    visibility_mode?: string | null;
    preset_key?: string | null;
    access_count?: number;
    last_accessed_at?: string | null;
    state?: {
        state: string;
        relative?: string | null;
        redacted?: boolean;
        preset?: {
            key: string;
            label: string;
            description?: string | null;
        } | null;
    } | null;
    access_trend?: { date: string; count: number }[];
    insights?: {
        trend: { date: string; count: number }[];
        totals?: { week?: number };
        peak?: {
            date: string;
            count: number;
            bundle_key?: string | null;
            bundle_label?: string | null;
            extension_roles?: string[];
        } | null;
        extension_actors?: { role: string; count: number }[];
        recent_extensions?: { occurred_at?: string | null; actor_role?: string; expires_at?: string | null }[];
        preset_distribution?: { preset_key: string; label: string; total: number }[];
    } | null;
    extend_route?: string | null;
    insights_route?: string | null;
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
        preset_bundles: {
            key: string;
            label: string;
            description?: string | null;
            expiry_preset?: string;
            visibility_mode?: string;
        }[];
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
    const initialPresetKey = useMemo(
        () => share?.state?.preset?.key ?? share?.preset_key ?? 'custom',
        [share?.preset_key, share?.state?.preset?.key]
    );
    const [bundleKey, setBundleKey] = useState<string>(initialPresetKey);
    const [expiryPreset, setExpiryPreset] = useState<string>(settings.expiry_presets[0]?.key ?? '24h');
    const [customHours, setCustomHours] = useState('');
    const [visibilityMode, setVisibilityMode] = useState<string>(
        share?.visibility_mode ?? settings.visibility_modes[0]?.key ?? 'counts'
    );
    const [extendPreset, setExtendPreset] = useState<string>(settings.extend_presets[0]?.key ?? '24h');
    const [extendCustomHours, setExtendCustomHours] = useState('');

    useEffect(() => {
        setBundleKey(initialPresetKey);
    }, [initialPresetKey]);

    useEffect(() => {
        if (bundleKey === 'custom') {
            if (share?.state?.state === 'evergreen') {
                setExpiryPreset('never');
            }

            if (share?.visibility_mode) {
                setVisibilityMode(share.visibility_mode);
            }

            return;
        }

        const bundle = settings.preset_bundles.find((candidate) => candidate.key === bundleKey);

        if (!bundle) {
            return;
        }

        setExpiryPreset(bundle.expiry_preset ?? '24h');
        setVisibilityMode(bundle.visibility_mode ?? 'counts');
    }, [bundleKey, settings.preset_bundles, share?.state?.state, share?.visibility_mode]);

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
            preset: share.state.preset ?? null,
        };
    }, [share?.state, t]);

    const insightTrend = useMemo(
        () => share?.insights?.trend ?? share?.access_trend ?? [],
        [share?.insights?.trend, share?.access_trend]
    );

    const weeklyVisitCount = useMemo(() => {
        if (share?.insights?.totals?.week !== undefined && share?.insights?.totals?.week !== null) {
            return share.insights.totals.week ?? 0;
        }

        return insightTrend.reduce((total, entry) => total + entry.count, 0);
    }, [insightTrend, share?.insights?.totals?.week]);

    const visitSummary = useMemo(
        () => t('share_controls.insights.weekly_summary', undefined, { count: weeklyVisitCount }),
        [t, weeklyVisitCount]
    );

    const peakInsight = share?.insights?.peak ?? null;

    const extensionRoleLabel = useCallback(
        (role: string) => t(`share_controls.insights.roles.${role}`, role),
        [t]
    );

    const trendItems = useMemo(
        () =>
            insightTrend.map((entry) => {
                const isPeak = peakInsight?.date === entry.date;
                const formattedDate = formatTrendDate(entry.date);
                const bundleLabel = peakInsight?.bundle_label ?? peakInsight?.bundle_key ?? t('share_controls.insights.bundle_custom');
                const roleSummary = peakInsight?.extension_roles?.length
                    ? peakInsight.extension_roles.map(extensionRoleLabel).join(', ')
                    : extensionRoleLabel('unknown');

                return {
                    id: entry.date,
                    title: (
                        <div className="flex w-full items-center justify-between">
                            <span className={isPeak ? 'font-semibold text-amber-200' : undefined}>{formattedDate}</span>
                            <span className={isPeak ? 'font-semibold text-amber-200' : 'font-semibold text-zinc-100'}>
                                {entry.count}
                            </span>
                        </div>
                    ),
                    description:
                        isPeak && peakInsight
                            ? t('share_controls.insights.peak.description', undefined, {
                                  bundle: bundleLabel,
                                  roles: roleSummary,
                              })
                            : undefined,
                    icon: isPeak ? <CalendarClock className="h-4 w-4" /> : undefined,
                };
            }),
        [extensionRoleLabel, formatTrendDate, insightTrend, peakInsight, t]
    );

    const extensionItems = useMemo(
        () =>
            (share?.insights?.extension_actors ?? []).map((actor, index) => ({
                id: `${actor.role ?? 'unknown'}-${index}`,
                title: extensionRoleLabel(actor.role ?? 'unknown'),
                description: t('share_controls.insights.extension_count', undefined, { count: actor.count }),
            })),
        [extensionRoleLabel, share?.insights?.extension_actors, t]
    );

    const presetItems = useMemo(
        () =>
            (share?.insights?.preset_distribution ?? []).map((preset) => ({
                id: preset.preset_key,
                title: preset.label,
                description: t('share_controls.insights.preset_usage', undefined, { count: preset.total }),
            })),
        [share?.insights?.preset_distribution, t]
    );

    const recentExtensionItems = useMemo(
        () =>
            (share?.insights?.recent_extensions ?? []).map((entry, index) => {
                const occurred = entry.occurred_at ? formatTimestamp(entry.occurred_at) : t('generic.unknown');
                const expires = entry.expires_at ? formatTimestamp(entry.expires_at) : null;

                return {
                    id: entry.occurred_at ?? `extension-${index}`,
                    title: extensionRoleLabel(entry.actor_role ?? 'unknown'),
                    description: expires
                        ? t('share_controls.insights.recent_extension_with_expiry', undefined, {
                              timestamp: occurred,
                              expires,
                          })
                        : t('share_controls.insights.recent_extension', undefined, { timestamp: occurred }),
                };
            }),
        [extensionRoleLabel, formatTimestamp, share?.insights?.recent_extensions, t]
    );

    const presetTotal = useMemo(
        () => (share?.insights?.preset_distribution ?? []).reduce((sum, entry) => sum + entry.total, 0),
        [share?.insights?.preset_distribution]
    );

    const recentExtensionCount = share?.insights?.recent_extensions?.length ?? 0;

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
                preset_key: bundleKey,
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
                            {shareState.preset && (
                                <span className="text-[11px] text-zinc-400">
                                    {t('share_controls.states.preset_label', 'Bundle: :label', {
                                        label: shareState.preset.label,
                                    })}
                                </span>
                            )}
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
                                {t('share_controls.form.preset_bundle')}
                                <select
                                    value={bundleKey}
                                    onChange={(event) => setBundleKey(event.target.value)}
                                    className="rounded-md border border-zinc-700 bg-zinc-900 px-2 py-1 text-sm text-zinc-100"
                                >
                                    <option value="custom">{t('share_controls.form.bundle_custom')}</option>
                                    {settings.preset_bundles.map((bundle) => (
                                        <option key={bundle.key} value={bundle.key}>
                                            {bundle.label}
                                        </option>
                                    ))}
                                </select>
                                {bundleKey !== 'custom' && (
                                    <span className="text-[11px] text-zinc-500">
                                        {
                                            settings.preset_bundles.find((bundle) => bundle.key === bundleKey)
                                                ?.description
                                        }
                                    </span>
                                )}
                            </label>
                            <label className="flex flex-col gap-1 text-xs text-zinc-400">
                                {t('share_controls.form.expiry_preset')}
                                <select
                                    value={expiryPreset}
                                    onChange={(event) => setExpiryPreset(event.target.value)}
                                    disabled={bundleKey !== 'custom'}
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
                                        disabled={bundleKey !== 'custom'}
                                        className="rounded-md border border-zinc-700 bg-zinc-900 px-2 py-1 text-sm text-zinc-100"
                                    />
                                </label>
                            )}
                            <label className="flex flex-col gap-1 text-xs text-zinc-400">
                                {t('share_controls.form.guest_visibility')}
                                <select
                                    value={visibilityMode}
                                    onChange={(event) => setVisibilityMode(event.target.value)}
                                    disabled={bundleKey !== 'custom'}
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

            {share?.insights && (
                <section className="mt-6 space-y-4" aria-labelledby="share-insights-heading">
                    <div className="flex flex-col gap-1 md:flex-row md:items-center md:justify-between">
                        <h4 id="share-insights-heading" className="text-sm font-semibold text-zinc-100">
                            {t('share_controls.insights.title')}
                        </h4>
                        <p className="text-[11px] text-zinc-500">{visitSummary}</p>
                    </div>
                    <div className="grid gap-4 md:grid-cols-2">
                        <InsightCard
                            title={t('share_controls.insights.weekly_total.title')}
                            value={<span>{weeklyVisitCount}</span>}
                            description={visitSummary}
                            footer={t('share_controls.insights.footnote')}
                        >
                            <InsightList items={trendItems} emptyLabel={t('share_controls.insights.empty')} />
                        </InsightCard>
                        <InsightCard
                            title={t('share_controls.insights.peak.title')}
                            value={<span>{peakInsight ? peakInsight.count : '—'}</span>}
                            description={
                                peakInsight
                                    ? t('share_controls.insights.peak.subtitle', undefined, {
                                          date: formatTrendDate(peakInsight.date),
                                      })
                                    : t('share_controls.insights.peak.empty')
                            }
                        >
                            <InsightList
                                items={extensionItems}
                                emptyLabel={t('share_controls.insights.extension_empty')}
                            />
                        </InsightCard>
                        <InsightCard
                            title={t('share_controls.insights.presets.title')}
                            value={<span>{presetTotal}</span>}
                            description={t('share_controls.insights.presets.description')}
                        >
                            <InsightList
                                items={presetItems}
                                emptyLabel={t('share_controls.insights.presets.empty')}
                            />
                        </InsightCard>
                        <InsightCard
                            title={t('share_controls.insights.recent_extensions.title')}
                            value={<span>{recentExtensionCount}</span>}
                            description={t('share_controls.insights.recent_extensions.description')}
                        >
                            <InsightList
                                items={recentExtensionItems}
                                emptyLabel={t('share_controls.insights.recent_extensions.empty')}
                            />
                        </InsightCard>
                    </div>
                </section>
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
