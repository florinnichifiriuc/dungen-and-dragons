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

export default function ConditionTimerSummarySharePage({
    group,
    summary,
    share,
}: ConditionTimerSummarySharePageProps) {
    const createdLabel = formatTimestamp(share.created_at);
    const expiresLabel = share.expires_at ? formatTimestamp(share.expires_at) : null;

    return (
        <GuestLayout>
            <Head title={`${group.name} • Shared Condition Outlook`} />
            <div className="mx-auto flex min-h-screen max-w-5xl flex-col gap-6 bg-zinc-950 px-4 py-10 text-zinc-100">
                <header className="space-y-2 text-center">
                    <h1 className="text-2xl font-semibold">{group.name} • Shared Condition Outlook</h1>
                    <p className="text-sm text-zinc-400">
                        Review the latest lingering effects impacting this party. Timers update automatically as facilitators log
                        changes.
                    </p>
                    <div className="text-xs text-zinc-500">
                        <p>Generated {createdLabel}</p>
                        {expiresLabel && <p>Link expires {expiresLabel}</p>}
                    </div>
                </header>
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
                <footer className="mt-auto text-center text-xs text-zinc-600">
                    Powered by the Dungen & Dragons campaign workspace.
                </footer>
            </div>
        </GuestLayout>
    );
}
