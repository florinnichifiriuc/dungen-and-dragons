import { Link, router } from '@inertiajs/react';
import { CalendarClock, Link2, RefreshCcw } from 'lucide-react';
import { useMemo, useState } from 'react';

import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

export type ConditionTimerShareResource = {
    id: number;
    url: string;
    created_at: string | null;
    expires_at: string | null;
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

export function ConditionTimerShareLinkControls({
    groupId,
    share,
    canManage,
    className,
}: ConditionTimerShareLinkControlsProps) {
    const [isProcessing, setIsProcessing] = useState(false);

    const expiresLabel = useMemo(() => {
        if (!share?.expires_at) {
            return null;
        }

        const formatted = formatTimestamp(share.expires_at);
        const relative = formatRelative(share.expires_at);

        return relative ? `${formatted} (${relative})` : formatted;
    }, [share?.expires_at]);

    const createdLabel = useMemo(() => {
        if (!share?.created_at) {
            return null;
        }

        return formatTimestamp(share.created_at);
    }, [share?.created_at]);

    const generateShare = () => {
        if (!canManage || isProcessing) {
            return;
        }

        setIsProcessing(true);
        router.post(
            route('groups.condition-timers.player-summary.share-links.store', groupId),
            {},
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
                Generate a secure link so party members can review the latest condition summaries without logging in. Links expire
                automatically after two weeks, and you can rotate them at any time.
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
                        </div>
                    )}
                </div>
            ) : (
                <p className="mt-4 text-xs text-zinc-500">No active share link yet.</p>
            )}

            {canManage && (
                <div className="mt-4 flex flex-wrap items-center gap-3">
                    <Button size="sm" onClick={generateShare} disabled={isProcessing}>
                        {share ? 'Regenerate link' : 'Generate share link'}
                    </Button>
                    {share && (
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={revokeShare}
                            disabled={isProcessing}
                            className="border-zinc-700 text-zinc-300 hover:border-rose-500/50 hover:text-rose-200"
                        >
                            <RefreshCcw className="mr-2 h-4 w-4" aria-hidden /> Disable current link
                        </Button>
                    )}
                </div>
            )}
        </section>
    );
}

export default ConditionTimerShareLinkControls;
