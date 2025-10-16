import { Head, usePage } from '@inertiajs/react';
import { useEffect, useRef } from 'react';

import AppLayout from '@/Layouts/AppLayout';
import PlayerConditionTimerSummaryPanel, {
    ConditionTimerSummaryResource,
} from '@/components/condition-timers/PlayerConditionTimerSummaryPanel';
import { MobileConditionTimerRecapWidget } from '@/components/condition-timers/MobileConditionTimerRecapWidget';
import ConditionTimerShareLinkControls, {
    type ConditionTimerShareResource,
} from '@/components/condition-timers/ConditionTimerShareLinkControls';
import { useConditionTimerSummaryCache } from '@/hooks/useConditionTimerSummaryCache';
import {
    applyAcknowledgementToSummary,
    type ConditionAcknowledgementPayload,
} from '@/lib/conditionAcknowledgements';
import { getEcho } from '@/lib/realtime';

type ConditionTimerSummaryPageProps = {
    group: { id: number; name: string; viewer_role?: string | null };
    summary: ConditionTimerSummaryResource;
    share: ConditionTimerShareResource | null;
    can_manage_share: boolean;
};

export default function ConditionTimerSummaryPage({
    group,
    summary,
    share,
    can_manage_share: canManageShare,
}: ConditionTimerSummaryPageProps) {
    const page = usePage();
    const currentUserId = (page.props.auth?.user?.id as number | undefined) ?? null;
    const storageKey = `group.${group.id}.condition-summary`;
    const {
        summary: hydratedSummary,
        updateSummary: updateHydratedSummary,
    } = useConditionTimerSummaryCache({
        storageKey,
        initialSummary: summary,
    });

    const summaryRef = useRef(hydratedSummary);

    useEffect(() => {
        summaryRef.current = hydratedSummary;
    }, [hydratedSummary]);

    useEffect(() => {
        const echo = getEcho();

        if (!echo) {
            return;
        }

        const channel = echo.private(`groups.${group.id}.condition-timers`);

        const handleSummary = (payload: { summary?: ConditionTimerSummaryResource }) => {
            if (payload.summary) {
                updateHydratedSummary(payload.summary);
            }
        };

        const handleAcknowledgement = (payload: ConditionAcknowledgementPayload) => {
            const next = applyAcknowledgementToSummary(
                summaryRef.current,
                payload,
                currentUserId,
            );

            updateHydratedSummary(next, { allowStale: true });
        };

        channel.listen('.condition-timer-summary.updated', handleSummary);
        channel.listen('.condition-timer-acknowledgement.recorded', handleAcknowledgement);

        return () => {
            channel.stopListening('.condition-timer-summary.updated');
            channel.stopListening('.condition-timer-acknowledgement.recorded');
            echo.leave(`groups.${group.id}.condition-timers`);
        };
    }, [group.id, updateHydratedSummary, currentUserId]);

    const shareUrl = share?.url ?? null;

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
                    shareUrl={shareUrl ?? undefined}
                    className="md:hidden"
                    source="group_mobile_widget"
                    viewerRole={group.viewer_role}
                />
                <PlayerConditionTimerSummaryPanel
                    summary={hydratedSummary}
                    shareUrl={shareUrl ?? undefined}
                    className="hidden md:block"
                    source="group_summary_page"
                    viewerRole={group.viewer_role}
                    onSummaryUpdate={(next) => updateHydratedSummary(next, { allowStale: true })}
                />
                <ConditionTimerShareLinkControls
                    groupId={group.id}
                    share={share}
                    canManage={canManageShare}
                />
            </div>
        </AppLayout>
    );
}
