import { Head } from '@inertiajs/react';

import AppLayout from '@/Layouts/AppLayout';
import PlayerConditionTimerSummaryPanel, {
    ConditionTimerSummaryResource,
} from '@/components/condition-timers/PlayerConditionTimerSummaryPanel';
import { MobileConditionTimerRecapWidget } from '@/components/condition-timers/MobileConditionTimerRecapWidget';
import { useConditionTimerSummaryCache } from '@/hooks/useConditionTimerSummaryCache';

type ConditionTimerSummaryPageProps = {
    group: { id: number; name: string; viewer_role?: string | null };
    summary: ConditionTimerSummaryResource;
};

export default function ConditionTimerSummaryPage({ group, summary }: ConditionTimerSummaryPageProps) {
    const storageKey = `group.${group.id}.condition-summary`;
    const { summary: hydratedSummary } = useConditionTimerSummaryCache({
        storageKey,
        initialSummary: summary,
    });

    return (
        <AppLayout>
            <Head title={`${group.name} • Condition Timers`} />
            <div className="mx-auto flex max-w-5xl flex-col gap-6 p-6">
                <div className="space-y-2">
                    <h1 className="text-2xl font-semibold text-zinc-100">{group.name} condition outlook</h1>
                    <p className="text-sm text-zinc-400">
                        Share this page with players to keep them informed about lingering effects without revealing GM secrets.
                    </p>
                </div>
                <MobileConditionTimerRecapWidget
                    summary={hydratedSummary}
                    className="md:hidden"
                    source="group_mobile_widget"
                    viewerRole={group.viewer_role}
                />
                <PlayerConditionTimerSummaryPanel
                    summary={hydratedSummary}
                    className="hidden md:block"
                    source="group_summary_page"
                    viewerRole={group.viewer_role}
                />
            </div>
        </AppLayout>
    );
}
