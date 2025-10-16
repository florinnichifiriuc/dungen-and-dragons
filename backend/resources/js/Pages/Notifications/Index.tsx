import { Link, usePage } from '@inertiajs/react';

import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/hooks/useTranslations';

type NotificationRecord = {
    id: string;
    type: string;
    data: Record<string, unknown>;
    read_at: string | null;
    created_at: string | null;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type NotificationPageProps = {
    notifications: {
        data: NotificationRecord[];
        links: PaginationLink[];
    };
};

function resolveString(value: unknown, fallback = ''): string {
    if (typeof value === 'string' && value.trim() !== '') {
        return value;
    }

    return fallback;
}

function urgencyClass(urgency: string | undefined): string {
    switch (urgency) {
        case 'critical':
            return 'bg-rose-500/10 text-rose-500 dark:text-rose-300 border border-rose-500/40';
        case 'warning':
            return 'bg-amber-500/10 text-amber-600 dark:text-amber-300 border border-amber-500/30';
        default:
            return 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-300 border border-emerald-500/30';
    }
}

export default function NotificationIndex() {
    const { t } = useTranslations();
    const { notifications, csrf_token: csrfTokenValue } = usePage<NotificationPageProps & { csrf_token?: string }>().props;
    const entries = notifications.data;
    const csrfToken = typeof csrfTokenValue === 'string' ? csrfTokenValue : '';

    return (
        <AppLayout>
            <section className="space-y-6">
                <header className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-3xl font-semibold">{t('notifications.title')}</h1>
                        <p className="text-sm text-zinc-600 dark:text-zinc-400">
                            {t('notifications.description')}
                        </p>
                    </div>
                    {entries.length > 0 && (
                        <form method="post" action={route('notifications.read-all')} className="inline-flex">
                            <input type="hidden" name="_token" value={csrfToken} />
                            <Button type="submit" variant="outline" className="text-sm">
                                {t('notifications.mark_all')}
                            </Button>
                        </form>
                    )}
                </header>
                {entries.length === 0 && (
                    <div className="rounded-lg border border-dashed border-zinc-400/40 bg-white/50 p-8 text-center text-sm text-zinc-500 dark:border-zinc-700/60 dark:bg-zinc-900/60 dark:text-zinc-400">
                        {t('notifications.empty')}
                    </div>
                )}
                <div className="space-y-4">
                    {entries.map((notification) => {
                        const data = notification.data;
                        const urgency = resolveString(data['urgency'], 'calm');
                        const urgencyLabel = t(`notifications.urgency.${urgency}`, urgency);
                        const title = resolveString(data['title'], 'Condition update');
                        const body = resolveString(data['body']);
                        const group = data['group'] as Record<string, unknown> | undefined;
                        const token = data['token'] as Record<string, unknown> | undefined;
                        const condition = data['condition'] as Record<string, unknown> | undefined;
                        const map = data['map'] as Record<string, unknown> | undefined;
                        const createdAt = notification.created_at ? new Date(notification.created_at) : null;
                        const contextUrl = typeof data['context_url'] === 'string' ? data['context_url'] : null;
                        const isRead = notification.read_at !== null;

                        return (
                            <article
                                key={notification.id}
                                className={`rounded-lg border p-4 shadow-sm transition ${
                                    isRead
                                        ? 'border-zinc-300/60 bg-white/70 dark:border-zinc-800/60 dark:bg-zinc-900/70'
                                        : 'border-amber-400/60 bg-white dark:border-amber-400/40 dark:bg-zinc-900'
                                }`}
                            >
                                <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div className="space-y-2">
                                        <div className="flex flex-wrap items-center gap-3">
                                            <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold ${urgencyClass(urgency)}`}>
                                                {urgencyLabel}
                                            </span>
                                            {group && (
                                                <span className="text-xs text-zinc-600 dark:text-zinc-400">
                                                    {resolveString(group['name'], 'Unknown group')}
                                                </span>
                                            )}
                                            {map && (
                                                <span className="text-xs text-zinc-600 dark:text-zinc-400">
                                                    {resolveString(map['title'], 'Map')}
                                                </span>
                                            )}
                                        </div>
                                        <h2 className="text-lg font-semibold text-slate-900 dark:text-zinc-100">{title}</h2>
                                        {body && <p className="text-sm text-zinc-700 dark:text-zinc-300">{body}</p>}
                                        <div className="flex flex-wrap gap-4 text-xs text-zinc-600 dark:text-zinc-400">
                                            {token && (
                                                <span>
                                                    {t('notifications.token_label')}{' '}
                                                    <span className="font-medium text-slate-900 dark:text-zinc-100">{resolveString(token['label'], 'Token')}</span>
                                                </span>
                                            )}
                                            {condition && (
                                                <span>
                                                    {t('notifications.condition_label')}{' '}
                                                    <span className="font-medium text-slate-900 dark:text-zinc-100">{resolveString(condition['label'], 'Condition')}</span>
                                                </span>
                                            )}
                                            {condition && typeof condition['rounds'] === 'number' && (
                                                <span>
                                                    {t('notifications.rounds_label')}: {condition['rounds']}
                                                </span>
                                            )}
                                        </div>
                                    </div>
                                    <div className="flex flex-col items-end gap-3 text-sm">
                                        {createdAt && (
                                            <time
                                                dateTime={createdAt.toISOString()}
                                                className="text-xs text-zinc-500 dark:text-zinc-400"
                                            >
                                                {createdAt.toLocaleString()}
                                            </time>
                                        )}
                                        <div className="flex items-center gap-2">
                                            {contextUrl && (
                                                <a
                                                    href={contextUrl}
                                                    className="rounded-md border border-amber-400/60 px-3 py-1 text-xs font-medium text-amber-700 transition hover:bg-amber-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-400 dark:border-amber-400/40 dark:text-amber-300 dark:hover:bg-amber-400/10"
                                                >
                                                    {t('notifications.view_summary')}
                                                </a>
                                            )}
                                            {!isRead && (
                                                <Link
                                                    href={route('notifications.read', notification.id)}
                                                    method="patch"
                                                    as="button"
                                                    className="rounded-md bg-emerald-500 px-3 py-1 text-xs font-semibold text-emerald-50 transition hover:bg-emerald-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-400"
                                                >
                                                    {t('notifications.mark_read')}
                                                </Link>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            </article>
                        );
                    })}
                </div>
                {notifications.links?.length > 0 && (
                    <nav className="flex items-center justify-end gap-2 text-sm" aria-label="Notification pagination">
                        {notifications.links.map((link, index) => {
                            const key = `${link.label}-${index}`;

                            if (!link.url) {
                                return (
                                    <span
                                        key={key}
                                        className="cursor-not-allowed rounded-md px-3 py-1 text-zinc-400 dark:text-zinc-600"
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                );
                            }

                            return (
                                <Link
                                    // eslint-disable-next-line react/no-array-index-key
                                    key={key}
                                    href={link.url}
                                    className={`rounded-md px-3 py-1 transition ${
                                        link.active
                                            ? 'bg-amber-500 text-zinc-950'
                                            : 'text-zinc-600 hover:bg-amber-100 hover:text-amber-700 dark:text-zinc-400 dark:hover:bg-amber-400/10'
                                    }`}
                                    preserveScroll
                                    preserveState
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            );
                        })}
                    </nav>
                )}
            </section>
        </AppLayout>
    );
}
