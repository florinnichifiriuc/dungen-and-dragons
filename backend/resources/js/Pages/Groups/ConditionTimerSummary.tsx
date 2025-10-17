import { Head, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useRef } from 'react';

import AppLayout from '@/Layouts/AppLayout';
import PlayerConditionTimerSummaryPanel, {
    ConditionTimerSummaryResource,
} from '@/components/condition-timers/PlayerConditionTimerSummaryPanel';
import { MobileConditionTimerRecapWidget } from '@/components/condition-timers/MobileConditionTimerRecapWidget';
import ConditionTimerShareLinkControls, {
    type ConditionTimerShareResource,
} from '@/components/condition-timers/ConditionTimerShareLinkControls';
import ConditionTransparencyExportPanel from '@/components/condition-timers/ConditionTransparencyExportPanel';
import ConditionMentorBriefingPanel from '@/components/condition-timers/ConditionMentorBriefingPanel';
import { useConditionTimerSummaryCache } from '@/hooks/useConditionTimerSummaryCache';
import {
    applyAcknowledgementToSummary,
    type ConditionAcknowledgementPayload,
} from '@/lib/conditionAcknowledgements';
import { getEcho } from '@/lib/realtime';
import { useTranslations } from '@/hooks/useTranslations';

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

type ShareSettings = {
    expiry_presets: { key: string; label: string }[];
    visibility_modes: { key: string; label: string }[];
    consents: ConsentStatus[];
    audit_log: ConsentAuditEntry[];
    consent_route: string;
};

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

type MentorBriefing = {
    focus?: {
        critical_conditions?: string[];
        unacknowledged_tokens?: string[];
        recurring_conditions?: string[];
    };
    briefing?: string;
    requested_at?: string;
};

type ConditionTimerSummaryPageProps = {
    group: { id: number; name: string; viewer_role?: string | null; mentor_briefings_enabled?: boolean };
    summary: ConditionTimerSummaryResource;
    share: ConditionTimerShareResource | null;
    can_manage_share: boolean;
    share_settings: ShareSettings;
    export_settings: ExportSettings;
    mentor_briefing: MentorBriefing | [];
};

export default function ConditionTimerSummaryPage({
    group,
    summary,
    share,
    can_manage_share: canManageShare,
    share_settings: shareSettings,
    export_settings: exportSettings,
    mentor_briefing: mentorBriefing,
}: ConditionTimerSummaryPageProps) {
    const { t } = useTranslations('condition_timers');
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

    const headTitle = useMemo(
        () => t('summary_page.head_title', undefined, { group: group.name }),
        [group.name, t]
    );
    const pageTitle = useMemo(
        () => t('summary_page.title', undefined, { group: group.name }),
        [group.name, t]
    );
    const pageDescription = useMemo(
        () => t('summary_page.description'),
        [t]
    );

    return (
        <AppLayout>
            <Head title={headTitle} />
            <div className="mx-auto flex max-w-5xl flex-col gap-6 p-6">
                <div className="space-y-2">
                    <h1 className="text-2xl font-semibold text-zinc-100">{pageTitle}</h1>
                    <p className="text-sm text-zinc-400">{pageDescription}</p>
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
                    settings={shareSettings}
                />
                <ConditionTransparencyExportPanel
                    groupId={group.id}
                    canManage={canManageShare}
                    settings={exportSettings}
                />
                <ConditionMentorBriefingPanel
                    groupId={group.id}
                    enabled={Boolean(group.mentor_briefings_enabled)}
                    mentorBriefing={Array.isArray(mentorBriefing) ? {} : mentorBriefing}
                />
            </div>
        </AppLayout>
    );
}
