export type BugReportUpdateEntry = {
    id: number;
    type: string;
    payload: Record<string, unknown> | null;
    created_at?: string | null;
    actor?: { id: number; name: string; email?: string | null } | null;
};

export type BugReportSummary = {
    id: string;
    reference: string;
    summary: string;
    description: string;
    status: string;
    priority: 'low' | 'normal' | 'high' | 'critical';
    tags: string[];
    context_type: string;
    context_identifier?: string | null;
    environment?: Record<string, unknown> | null;
    ai_context?: {
        id: string | number;
        type: string;
        created_at?: string | null;
        summary: string;
        focus_match?: boolean | null;
    }[];
    submitted: {
        name?: string | null;
        email?: string | null;
        at?: string | null;
    };
    assignee?: { id: number; name: string; email?: string | null } | null;
    group?: { id: number; name: string } | null;
    updates: BugReportUpdateEntry[];
};
