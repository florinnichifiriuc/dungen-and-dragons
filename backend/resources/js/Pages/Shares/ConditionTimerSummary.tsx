import { Head } from '@inertiajs/react';

import GuestLayout from '@/Layouts/GuestLayout';
import PlayerConditionTimerSummaryPanel, {
    type ConditionTimerSummaryResource,
} from '@/components/condition-timers/PlayerConditionTimerSummaryPanel';
import { MobileConditionTimerRecapWidget } from '@/components/condition-timers/MobileConditionTimerRecapWidget';

type ConditionTimerSummarySharePageProps = {
    group: { id: number; name: string };
    summary: ConditionTimerSummaryResource;
    share: { created_at: string | null; expires_at: string | null };
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

export default function ConditionTimerSummarySharePage({
    group,
    summary,
    share,
}: ConditionTimerSummarySharePageProps) {
    const createdLabel = formatTimestamp(share.created_at);
    const createdRelative = formatRelative(share.created_at);
    const expiresLabel = share.expires_at ? formatTimestamp(share.expires_at) : null;
    const expiresRelative = formatRelative(share.expires_at);
    const summaryGeneratedLabel = formatTimestamp(summary.generated_at);
    const summaryGeneratedRelative = formatRelative(summary.generated_at);

    return (
        <GuestLayout>
            <Head title={`${group.name} • Shared Condition Outlook`} />
            <div className="mx-auto flex min-h-screen max-w-6xl flex-col gap-6 bg-zinc-950 px-4 py-10 text-zinc-100">
                <header className="rounded-3xl border border-amber-500/10 bg-gradient-to-br from-amber-500/20 via-amber-400/10 to-transparent p-6 text-center shadow-lg shadow-amber-900/20 md:text-left">
                    <p className="text-xs uppercase tracking-[0.3em] text-amber-200">Shared outlook briefing</p>
                    <h1 className="mt-3 text-3xl font-semibold text-amber-50 md:text-4xl">
                        {group.name} condition outlook
                    </h1>
                    <p className="mt-3 text-sm text-amber-100/80 md:text-base">
                        Stay informed about lingering effects, their urgency, and how long the winds of magic are expected to last.
                        This view refreshes whenever the facilitation team updates the encounter board.
                    </p>
                    <div className="mt-4 flex flex-wrap items-center justify-center gap-4 text-xs text-amber-100/80 md:justify-start">
                        <span>
                            Shared {createdLabel}
                            {createdRelative ? ` (${createdRelative})` : ''}
                        </span>
                        <span className="hidden h-1 w-1 rounded-full bg-amber-300/60 md:inline" aria-hidden />
                        <span>
                            Outlook refreshed {summaryGeneratedLabel}
                            {summaryGeneratedRelative ? ` (${summaryGeneratedRelative})` : ''}
                        </span>
                        {expiresLabel && (
                            <>
                                <span className="hidden h-1 w-1 rounded-full bg-amber-300/60 md:inline" aria-hidden />
                                <span>
                                    Link expires {expiresLabel}
                                    {expiresRelative ? ` (${expiresRelative})` : ''}
                                </span>
                            </>
                        )}
                    </div>
                </header>
                <section className="grid flex-1 gap-6 md:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
                    <div className="space-y-6">
                        <MobileConditionTimerRecapWidget
                            summary={summary}
                            className="md:hidden"
                            source="shared_link_mobile"
                            viewerRole="guest"
                        />
                        <PlayerConditionTimerSummaryPanel
                            summary={summary}
                            className="hidden md:block"
                            source="shared_link_desktop"
                            viewerRole="guest"
                            allowAcknowledgements={false}
                        />
                    </div>
                    <aside className="space-y-4">
                        <div className="rounded-2xl border border-zinc-800/80 bg-zinc-900/70 p-5 text-sm text-zinc-300">
                            <h2 className="text-base font-semibold text-amber-100">How to read this outlook</h2>
                            <ul className="mt-3 space-y-2 text-xs text-zinc-400">
                                <li>
                                    <strong className="text-zinc-200">Urgency colors</strong> highlight how soon an effect fades.
                                    Critical effects deserve immediate attention.
                                </li>
                                <li>
                                    <strong className="text-zinc-200">Rounds remaining</strong> show the facilitator&apos;s best estimate.
                                    If you spot inconsistencies, check in with your DM between turns.
                                </li>
                                <li>
                                    <strong className="text-zinc-200">Timeline notes</strong> capture recent adjustments so you can
                                    follow why an effect lingered or shortened.
                                </li>
                            </ul>
                        </div>
                        <div className="rounded-2xl border border-zinc-800/60 bg-zinc-900/60 p-5 text-xs text-zinc-400">
                            <h2 className="text-sm font-semibold uppercase tracking-wide text-zinc-300">Need clarifications?</h2>
                            <p className="mt-2">
                                Reach out to your Dungeon Master or message the party board if something looks off. The outlook
                                is refreshed every time they adjust timers, so checking back after big swings keeps everyone on the
                                same page.
                            </p>
                        </div>
                    </aside>
                </section>
                <footer className="mt-auto text-center text-xs text-zinc-600">
                    Powered by the Dungen & Dragons campaign workspace.
                </footer>
            </div>
        </GuestLayout>
    );
}
