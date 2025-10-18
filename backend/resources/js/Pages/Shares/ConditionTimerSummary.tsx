import { Head } from '@inertiajs/react';
import { useCallback, useMemo } from 'react';

import GuestLayout from '@/Layouts/GuestLayout';
import PlayerConditionTimerSummaryPanel, {
    type ConditionTimerSummaryResource,
} from '@/components/condition-timers/PlayerConditionTimerSummaryPanel';
import { MobileConditionTimerRecapWidget } from '@/components/condition-timers/MobileConditionTimerRecapWidget';
import { useTranslations } from '@/hooks/useTranslations';

type MentorCatchUpPrompt = {
    id: string;
    delivered_at?: string | null;
    excerpt?: string | null;
    focus_summary?: string | null;
};

type ConditionTimerSummarySharePageProps = {
    group: { id: number; name: string };
    summary: ConditionTimerSummaryResource;
    share: {
        created_at: string | null;
        expires_at: string | null;
        state?: { state: string; relative?: string | null; redacted?: boolean } | null;
        redacted?: boolean;
        freshness?: { status: string; generated_at?: string | null; relative?: string | null } | null;
    };
    catch_up_prompts: MentorCatchUpPrompt[];
};

export default function ConditionTimerSummarySharePage({
    group,
    summary,
    share,
    catch_up_prompts: catchUpPrompts,
}: ConditionTimerSummarySharePageProps) {
    const { t, locale } = useTranslations('condition_timers');

    const formatTimestamp = useCallback(
        (value: string | null): string | null => {
            if (!value) {
                return null;
            }

            try {
                return new Intl.DateTimeFormat(locale, {
                    dateStyle: 'medium',
                    timeStyle: 'short',
                }).format(new Date(value));
            } catch {
                return value;
            }
        },
        [locale]
    );

    const createdLabel = formatTimestamp(share.created_at);
    const expiresLabel = formatTimestamp(share.expires_at);
    const summaryUpdatedLabel = formatTimestamp(summary.generated_at ?? share.created_at);

    const stateLabel = useMemo(() => {
        const state = share.state?.state ?? 'active';
        return t(`share_controls.states.${state}`, t('share_controls.states.active'));
    }, [share.state?.state, t]);

    const stateTone = useMemo(() => {
        const state = share.state?.state;

        switch (state) {
            case 'evergreen':
            case 'active':
                return 'bg-emerald-500/10 text-emerald-200 border-emerald-500/30';
            case 'expiring_soon':
                return 'bg-amber-500/10 text-amber-200 border-amber-500/30';
            case 'expired':
                return 'bg-rose-500/10 text-rose-200 border-rose-500/30';
            default:
                return 'bg-emerald-500/10 text-emerald-200 border-emerald-500/30';
        }
    }, [share.state?.state]);

    const headTitle = useMemo(
        () => t('share_view.head_title', undefined, { group: group.name }),
        [group.name, t]
    );

    const pageTitle = useMemo(
        () => t('share_view.title', undefined, { group: group.name }),
        [group.name, t]
    );

    const freshness = share.freshness ?? null;
    const freshnessLabel = useMemo(() => {
        if (!freshness?.status) {
            return null;
        }

        return t(`share_view.staleness.${freshness.status}`, undefined, {
            relative: freshness.relative ?? '',
        });
    }, [freshness?.relative, freshness?.status, t]);

    const catchUpItems = useMemo(() => {
        if (!catchUpPrompts || catchUpPrompts.length === 0) {
            return [];
        }

        return catchUpPrompts.map((prompt) => {
            const delivered = formatTimestamp(prompt.delivered_at ?? null);

            return {
                id: prompt.id,
                excerpt: prompt.excerpt ?? '',
                delivered,
                focus: prompt.focus_summary ?? null,
            };
        });
    }, [catchUpPrompts, formatTimestamp]);

    return (
        <GuestLayout>
            <Head title={headTitle} />
            <div className="mx-auto flex min-h-screen max-w-5xl flex-col gap-6 bg-zinc-950 px-4 py-10 text-zinc-100">
                <header className="space-y-4 text-center">
                    <div className="flex flex-col items-center gap-3">
                        <span className={`inline-flex items-center gap-2 rounded-full border px-4 py-1 text-xs font-semibold ${stateTone}`}>
                            {stateLabel}
                            {share.state?.relative && (
                                <span className="text-[11px] text-zinc-300/80">{share.state.relative}</span>
                            )}
                        </span>
                        <h1 className="text-3xl font-semibold">{pageTitle}</h1>
                    </div>
                    <p className="text-sm text-zinc-400">{t('share_view.description')}</p>
                    {freshnessLabel && <p className="text-xs text-amber-200">{freshnessLabel}</p>}
                    <p className="text-xs text-zinc-500">{t('share_view.contact_hint', undefined, { facilitator: t('share_view.facilitator_generic') })}</p>
                    <div className="space-y-1 text-xs text-zinc-500">
                        {createdLabel && <p>{t('share_view.summoned', undefined, { timestamp: createdLabel })}</p>}
                        {summaryUpdatedLabel && (
                            <p>{t('share_view.refreshed', undefined, { timestamp: summaryUpdatedLabel })}</p>
                        )}
                        {expiresLabel && <p>{t('share_view.retires', undefined, { timestamp: expiresLabel })}</p>}
                    </div>
                    {share.redacted && (
                        <div className="rounded-lg border border-rose-500/40 bg-rose-500/10 px-4 py-3 text-xs text-rose-100">
                            {t('share_view.redacted_banner')}
                        </div>
                    )}
                </header>
                {!share.redacted && (
                    <>
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
                    </>
                )}
                {share.redacted && (
                    <div className="flex flex-1 items-center justify-center rounded-xl border border-zinc-900 bg-zinc-950/80 p-8 text-center text-sm text-zinc-400">
                        {t('share_view.redacted_empty')}
                    </div>
                )}
                {!share.redacted && (
                    <section className="space-y-3 rounded-xl border border-zinc-900/70 bg-zinc-950/70 p-4" aria-labelledby="mentor-catch-up-heading">
                        <div className="flex flex-col gap-1 md:flex-row md:items-center md:justify-between">
                            <h2 id="mentor-catch-up-heading" className="text-sm font-semibold text-zinc-100">
                                {t('share_view.catch_up.title')}
                            </h2>
                            <p className="text-[11px] text-zinc-500">{t('share_view.catch_up.subtitle')}</p>
                        </div>
                        {catchUpItems.length === 0 ? (
                            <p className="text-xs text-zinc-500">{t('share_view.catch_up.empty')}</p>
                        ) : (
                            <ul className="space-y-2 text-left text-xs">
                                {catchUpItems.map((item) => (
                                    <li
                                        key={item.id}
                                        className="rounded-lg border border-zinc-900/60 bg-zinc-950/80 p-3 text-zinc-200"
                                    >
                                        <p className="font-medium text-amber-100">{item.excerpt}</p>
                                        <p className="text-[11px] text-zinc-400">
                                            {t('share_view.catch_up.item_timestamp', undefined, { timestamp: item.delivered ?? t('generic.unknown') })}
                                        </p>
                                        {item.focus && (
                                            <p className="text-[11px] text-zinc-500">{item.focus}</p>
                                        )}
                                    </li>
                                ))}
                            </ul>
                        )}
                        <p className="text-[11px] text-zinc-500">{t('share_view.catch_up.cta')}</p>
                    </section>
                )}
                <footer className="mt-auto text-center text-xs text-zinc-600">
                    {t('share_view.footer')}
                </footer>
            </div>
        </GuestLayout>
    );
}
