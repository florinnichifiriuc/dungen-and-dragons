import type { ConditionTimerSummaryResource } from '@/components/condition-timers/PlayerConditionTimerSummaryPanel';

export type ConditionAcknowledgementPayload = {
    token_id: number;
    condition_key: string;
    summary_generated_at: string;
    acknowledged_count?: number;
    acknowledged_by_viewer?: boolean;
    actor_id?: number;
};

export function applyAcknowledgementToSummary(
    summary: ConditionTimerSummaryResource,
    payload: ConditionAcknowledgementPayload | undefined,
    viewerId: number | null | undefined = undefined,
): ConditionTimerSummaryResource {
    if (!payload || summary.generated_at !== payload.summary_generated_at) {
        return summary;
    }

    return {
        ...summary,
        entries: summary.entries.map((entry) => {
            if (entry.token.id !== payload.token_id) {
                return entry;
            }

            return {
                ...entry,
                conditions: entry.conditions.map((condition) => {
                    if (condition.key !== payload.condition_key) {
                        return condition;
                    }

                    const acknowledgedByViewer =
                        payload.actor_id !== undefined && viewerId !== undefined
                            ? payload.actor_id === viewerId
                            : payload.acknowledged_by_viewer;

                    return {
                        ...condition,
                        acknowledged_by_viewer:
                            acknowledgedByViewer ?? condition.acknowledged_by_viewer ?? false,
                        acknowledged_count:
                            typeof payload.acknowledged_count === 'number'
                                ? payload.acknowledged_count
                                : condition.acknowledged_count,
                    };
                }),
            };
        }),
    };
}
