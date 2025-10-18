import { Head, Link } from '@inertiajs/react';

import GuestLayout from '@/Layouts/GuestLayout';
import { BugReportForm } from '@/components/bug-reports/BugReportForm';
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/hooks/useTranslations';

type ConditionTimerBugReportSharePageProps = {
    share: {
        token: string;
        group: { id: number; name: string } | null;
        context_identifier?: string | null;
    };
    prefill?: {
        summary?: string | null;
        description?: string | null;
    };
};

export default function ConditionTimerBugReportSharePage({ share, prefill }: ConditionTimerBugReportSharePageProps) {
    const { t } = useTranslations('condition_timers');
    const groupName = share.group?.name ?? t('bug_report_share.group_fallback');
    const title = t('bug_report_share.title', undefined, { group: groupName });
    const description = t('bug_report_share.description');

    return (
        <GuestLayout>
            <Head title={t('bug_report_share.head_title', undefined, { group: groupName })} />
            <div className="mx-auto flex min-h-screen max-w-3xl flex-col gap-6 bg-zinc-950 px-4 py-10 text-zinc-100">
                <header className="space-y-4 text-center">
                    <h1 className="text-3xl font-semibold">{title}</h1>
                    <p className="text-sm text-zinc-400">{description}</p>
                    <p className="text-xs text-zinc-500">{t('bug_report_share.contact_hint')}</p>
                    <div className="flex items-center justify-center">
                        <Button asChild variant="outline" size="sm" className="border-brand-500/40 text-brand-200 hover:bg-brand-500/10">
                            <Link href={route('shares.condition-timers.player-summary.show', share.token)}>
                                {t('bug_report_share.back_to_summary')}
                            </Link>
                        </Button>
                    </div>
                </header>
                <section className="rounded-xl border border-zinc-900/60 bg-zinc-950/80 p-6 text-left">
                    <BugReportForm
                        action={route('shares.condition-timers.bug-report.store', share.token)}
                        context={{
                            type: 'player_share',
                            identifier: share.context_identifier ?? share.token,
                            path: undefined,
                            groupId: share.group?.id ?? null,
                        }}
                        defaults={{
                            summary: prefill?.summary ?? '',
                            description: prefill?.description ?? '',
                        }}
                        showContactFields
                    />
                </section>
            </div>
        </GuestLayout>
    );
}
