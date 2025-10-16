import { Link, router } from '@inertiajs/react';
import { CalendarClock, Eye, History, Link2, RefreshCcw } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

export type ConditionTimerShareAccessResource = {
    id: number;
    accessed_at: string | null;
    ip_address: string | null;
    user_agent: string | null;
};

export type ConditionTimerShareExpiry = {
    state: 'no_expiry' | 'active' | 'expiring_24h' | 'expiring_48h' | 'expired';
    label: string;
    remaining_hours: number | null;
};

export type ConditionTimerShareStats = {
    total_views: number;
    last_accessed_at: string | null;
    recent_accesses: ConditionTimerShareAccessResource[];
    daily_views: { date: string; total: number }[];
};

export type ConditionTimerShareResource = {
    id: number;
    url: string;
    created_at: string | null;
    expires_at: string | null;
    expiry?: ConditionTimerShareExpiry;
    stats: ConditionTimerShareStats;
};

type ConditionTimerShareLinkControlsProps = {
    groupId: number;
    share: ConditionTimerShareResource | null;
    canManage: boolean;
    className?: string;
};

const formatTimestamp = (value: string | null): string => {
    if (!value) {
        return 'Unknown';
    }

    try {
        const date = new Date(value);
        return new Intl.DateTimeFormat('en-US', {
            dateStyle: 'medium',
            timeStyle: 'short',
        }).format(date);
    } catch (error) {
        return value;
    }
};

const formatRelative = (value: string | null): string | null => {
    if (!value) {
        return null;
    }

    const parsed = Date.parse(value);

    if (Number.isNaN(parsed)) {
        return null;
    }

    const formatter = new Intl.RelativeTimeFormat('en-US', { numeric: 'auto' });
    const diffMilliseconds = parsed - Date.now();
    const diffMinutes = Math.round(diffMilliseconds / 60000);

    if (Math.abs(diffMinutes) < 60) {
        return formatter.format(Math.round(diffMilliseconds / 1000), 'second');
    }

    const diffHours = Math.round(diffMinutes / 60);

    if (Math.abs(diffHours) < 48) {
        return formatter.format(diffHours, 'hour');
    }

    const diffDays = Math.round(diffHours / 24);

    return formatter.format(diffDays, 'day');
};

const formatDateOnly = (value: string): string => {
    try {
        return new Intl.DateTimeFormat('en-US', {
            weekday: 'short',
            month: 'short',
            day: 'numeric',
        }).format(new Date(`${value}T00:00:00Z`));
    } catch (error) {
        return value;
    }
};

export function ConditionTimerShareLinkControls({
    groupId,
    share,
    canManage,
    className,
}: ConditionTimerShareLinkControlsProps) {
    const [isProcessing, setIsProcessing] = useState(false);
    const [expiresInHours, setExpiresInHours] = useState<number>(336);

    const expiresLabel = useMemo(() => {
        if (!share?.expires_at) {
            return null;
        }

        const formatted = formatTimestamp(share.expires_at);
        const relative = formatRelative(share.expires_at);

        return relative ? `${formatted} (${relative})` : formatted;
    }, [share?.expires_at]);

    const expiryStatusClass = useMemo(() => {
        const state = share?.expiry?.state;

        switch (state) {
            case 'expired':
            case 'expiring_24h':
                return 'border border-rose-500/40 bg-rose-500/10 text-rose-100';
            case 'expiring_48h':
                return 'border border-amber-400/40 bg-amber-400/10 text-amber-100';
            case 'active':
                return 'border border-emerald-500/40 bg-emerald-500/10 text-emerald-100';
            default:
                return 'border border-zinc-700 bg-zinc-900/60 text-zinc-300';
        }
    }, [share?.expiry?.state]);

    const createdLabel = useMemo(() => {
        if (!share?.created_at) {
            return null;
        }

        return formatTimestamp(share.created_at);
    }, [share?.created_at]);

    const lastOpenedLabel = useMemo(() => {
        const lastAccessed = share?.stats?.last_accessed_at ?? null;

        if (!lastAccessed) {
            return null;
        }

        const formatted = formatTimestamp(lastAccessed);
        const relative = formatRelative(lastAccessed);

        return relative ? `${formatted} (${relative})` : formatted;
    }, [share?.stats?.last_accessed_at]);

    const calculatedExpiresInHours = useMemo(() => {
        if (!share?.expires_at) {
            return 336;
        }

        const diff = Date.parse(share.expires_at) - Date.now();

        if (Number.isNaN(diff)) {
            return 336;
        }

        const hours = Math.round(diff / 3600000);

        return Math.min(720, Math.max(24, hours));
    }, [share?.expires_at]);

    const weeklyViews = share?.stats?.daily_views ?? [];

    const weeklySummary = useMemo(() => {
        if (!weeklyViews || weeklyViews.length === 0) {
            return { total: 0, peak: null as { date: string; total: number } | null };
        }

        let total = 0;
        let peak: { date: string; total: number } | null = null;

        weeklyViews.forEach((day) => {
            total += day.total;

            if (!peak || day.total > peak.total) {
                peak = day;
            }
        });

        return { total, peak };
    }, [weeklyViews]);

    useEffect(() => {
        setExpiresInHours(calculatedExpiresInHours);
    }, [calculatedExpiresInHours]);

    const generateShare = () => {
        if (!canManage || isProcessing) {
            return;
        }

        setIsProcessing(true);
        router.post(
            route('groups.condition-timers.player-summary.share-links.store', groupId),
            { expires_in_hours: expiresInHours },
            {
                preserveScroll: true,
                onFinish: () => setIsProcessing(false),
            },
        );
    };

    const extendShare = () => {
        if (!canManage || !share || isProcessing) {
            return;
        }

        setIsProcessing(true);
        router.patch(
            route('groups.condition-timers.player-summary.share-links.update', {
                group: groupId,
                share: share.id,
            }),
            { expires_in_hours: expiresInHours },
            {
                preserveScroll: true,
                onFinish: () => setIsProcessing(false),
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

    return (
        <section
            className={cn(
                'rounded-xl border border-zinc-800/70 bg-zinc-950/70 p-4 text-sm text-zinc-200 shadow-inner shadow-black/30',
                className,
            )}
        >
            <header className="flex items-center gap-2">
                <Link2 className="h-4 w-4 text-amber-300" aria-hidden />
                <h3 className="text-base font-semibold">Share condition outlook</h3>
            </header>
            <p className="mt-2 text-xs text-zinc-500">
                Generate a secure link so party members can review the latest condition summaries without logging in. Links honor
                the expiry window you choose (up to 30 days) and can be rotated at any time.
            </p>

            {share ? (
                <div className="mt-4 space-y-2 text-sm">
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
                    {createdLabel && (
                        <div className="flex items-center gap-2 text-xs text-zinc-500">
                            <CalendarClock className="h-4 w-4" aria-hidden />
                            <span>Generated {createdLabel}</span>
                        </div>
                    )}
                    {expiresLabel && (
                        <div className="flex items-center gap-2 text-xs text-zinc-500">
                            <CalendarClock className="h-4 w-4" aria-hidden />
                            <span>Expires {expiresLabel}</span>
                            {share.expiry && (
                                <span
                                    className={cn(
                                        'rounded-full px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide',
                                        expiryStatusClass,
                                    )}
                                >
                                    {share.expiry.label}
                                </span>
                            )}
                        </div>
                    )}

                    <div className="mt-3 space-y-2 text-xs text-zinc-500">
                        <div className="flex items-center gap-2">
                            <Eye className="h-4 w-4" aria-hidden />
                            <span>
                                {share.stats.total_views === 1
                                    ? 'Opened once'
                                    : `Opened ${share.stats.total_views} times`}
                            </span>
                        </div>

                        {lastOpenedLabel && (
                            <div className="flex items-center gap-2">
                                <History className="h-4 w-4" aria-hidden />
                                <span>Last opened {lastOpenedLabel}</span>
                            </div>
                        )}

                        {weeklyViews.length > 0 && (
                            <div className="rounded-lg border border-zinc-800/60 bg-zinc-900/50 p-3 text-[11px] text-zinc-400">
                                <p className="font-semibold uppercase tracking-wide text-zinc-500">Last 7 days</p>
                                {weeklySummary.total > 0 ? (
                                    <p className="mt-1 text-xs text-zinc-300">
                                        {weeklySummary.total === 1
                                            ? '1 guest visited this week.'
                                            : `${weeklySummary.total} visits logged this week.`}
                                        {weeklySummary.peak && weeklySummary.peak.total > 0 && (
                                            <span>
                                                {' '}
                                                Peak activity on {formatDateOnly(weeklySummary.peak.date)} (
                                                {weeklySummary.peak.total === 1
                                                    ? '1 view'
                                                    : `${weeklySummary.peak.total} views`}
                                                ).
                                            </span>
                                        )}
                                    </p>
                                ) : (
                                    <p className="mt-1 text-xs text-zinc-500">
                                        No visits recorded during the past seven days.
                                    </p>
                                )}
                                <ul className="mt-2 space-y-1">
                                    {weeklyViews.map((day) => (
                                        <li key={day.date} className="flex items-center justify-between">
                                            <span>{formatDateOnly(day.date)}</span>
                                            <span>{day.total === 1 ? '1 view' : `${day.total} views`}</span>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        )}

                        {share.stats.recent_accesses.length > 0 && (
                            <div>
                                <p className="font-semibold text-zinc-400">Recent guests</p>
                                <ul className="mt-1 space-y-1">
                                    {share.stats.recent_accesses.map((access) => {
                                        const timestampLabel = formatTimestamp(access.accessed_at);
                                        const detailParts = [access.ip_address, access.user_agent].filter(Boolean);
                                        const details = detailParts.join(' • ');

                                        return (
                                            <li key={access.id} className="text-[11px] leading-5 text-zinc-500">
                                                <span className="block text-zinc-300">{timestampLabel}</span>
                                                {details && <span>{details}</span>}
                                            </li>
                                        );
                                    })}
                                </ul>
                            </div>
                        )}
                    </div>
                </div>
            ) : (
                <p className="mt-4 text-xs text-zinc-500">No active share link yet.</p>
            )}

            {canManage && (
                <div className="mt-4 space-y-3">
                    <div className="flex flex-wrap items-center gap-3">
                        <label className="text-xs text-zinc-400" htmlFor={`share-expiry-${groupId}`}>
                            Expire link after
                        </label>
                        <input
                            id={`share-expiry-${groupId}`}
                            type="number"
                            min={1}
                            max={720}
                            value={expiresInHours}
                            onChange={(event) => {
                                const next = parseInt(event.target.value, 10);

                                if (Number.isNaN(next)) {
                                    setExpiresInHours(24);
                                    return;
                                }

                                setExpiresInHours(Math.min(720, Math.max(1, next)));
                            }}
                            className="w-24 rounded-lg border border-zinc-700 bg-zinc-900 px-2 py-1 text-xs text-zinc-100 focus:border-amber-400 focus:outline-none"
                        />
                        <span className="text-[11px] text-zinc-500">(1–720 hours)</span>
                    </div>

                    <div className="flex flex-wrap items-center gap-3">
                        <Button size="sm" onClick={generateShare} disabled={isProcessing}>
                        {share ? 'Regenerate link' : 'Generate share link'}
                        </Button>
                        {share && (
                            <>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={extendShare}
                                    disabled={isProcessing}
                                    className="text-amber-200 hover:text-amber-100"
                                >
                                    <CalendarClock className="mr-2 h-4 w-4" aria-hidden /> Extend expiry
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={revokeShare}
                                    disabled={isProcessing}
                                    className="border-zinc-700 text-zinc-300 hover:border-rose-500/50 hover:text-rose-200"
                                >
                                    <RefreshCcw className="mr-2 h-4 w-4" aria-hidden /> Disable current link
                                </Button>
                            </>
                        )}
                    </div>
                </div>
            )}
        </section>
    );
}

export default ConditionTimerShareLinkControls;
