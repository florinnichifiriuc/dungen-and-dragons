import { Head } from '@inertiajs/react';

import AppLayout from '@/Layouts/AppLayout';
import { BugReportForm } from '@/components/bug-reports/BugReportForm';

type BugReportCreatePageProps = {
    group: { id: number; name: string } | null;
    context: {
        type: string;
        identifier?: string | null;
        path?: string | null;
    };
    prefill?: {
        summary?: string | null;
        description?: string | null;
    };
};

export default function BugReportCreatePage({ group, context, prefill }: BugReportCreatePageProps) {
    const title = group ? `Report an issue for ${group.name}` : 'Report an issue';

    return (
        <AppLayout>
            <Head title={title} />
            <div className="mx-auto flex max-w-4xl flex-col gap-6 p-6">
                <header className="space-y-2">
                    <h1 className="text-2xl font-semibold text-zinc-100">{title}</h1>
                    <p className="text-sm text-zinc-400">
                        Share as much detail as you can so the support team can triage the bug quickly. Relevant logs and steps are
                        especially helpful during the release freeze.
                    </p>
                    {group && <p className="text-xs text-zinc-500">Group context: {group.name}</p>}
                </header>
                <section className="rounded-xl border border-zinc-800 bg-zinc-950/70 p-6">
                    <BugReportForm
                        action={route('bug-reports.store')}
                        context={{
                            type: context.type,
                            identifier: context.identifier ?? null,
                            path: context.path ?? null,
                            groupId: group?.id ?? null,
                        }}
                        defaults={{
                            summary: prefill?.summary ?? '',
                            description: prefill?.description ?? '',
                            priority: 'normal',
                        }}
                    />
                </section>
            </div>
        </AppLayout>
    );
}
