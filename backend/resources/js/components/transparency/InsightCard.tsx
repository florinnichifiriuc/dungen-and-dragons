import {
    CSSProperties,
    PropsWithChildren,
    ReactNode,
    useCallback,
    useEffect,
    useMemo,
    useRef,
} from 'react';

import { recordAnalyticsEventSync } from '@/lib/analytics';
import { cn } from '@/lib/utils';

export type InsightCardTone = 'default' | 'success' | 'warning' | 'danger';

export type InsightCardAnalyticsConfig = {
    eventKey: string;
    groupId?: number | null;
    payload?: Record<string, unknown>;
    trigger?: 'mount' | 'manual';
    onReady?: (fire: () => void) => void;
};

export type InsightCardProps = PropsWithChildren<{
    title: string;
    value: ReactNode;
    description?: ReactNode;
    tone?: InsightCardTone;
    footer?: ReactNode;
    className?: string;
    analytics?: InsightCardAnalyticsConfig;
}>;

const toneDefaults: Record<InsightCardTone, { border: string; background: string; foreground: string }> = {
    default: {
        border: 'rgba(39, 39, 42, 0.6)',
        background: 'rgba(9, 9, 11, 0.8)',
        foreground: 'rgb(244, 244, 245)',
    },
    success: {
        border: 'rgba(5, 150, 105, 0.4)',
        background: 'rgba(16, 185, 129, 0.1)',
        foreground: 'rgb(209, 250, 229)',
    },
    warning: {
        border: 'rgba(245, 158, 11, 0.4)',
        background: 'rgba(245, 158, 11, 0.1)',
        foreground: 'rgb(254, 243, 199)',
    },
    danger: {
        border: 'rgba(244, 63, 94, 0.4)',
        background: 'rgba(244, 63, 94, 0.1)',
        foreground: 'rgb(255, 228, 230)',
    },
};

export function InsightCard({
    title,
    value,
    description,
    tone = 'default',
    footer,
    className,
    children,
    analytics,
}: InsightCardProps) {
    const payload = useMemo(() => analytics?.payload ?? {}, [analytics?.payload]);
    const eventKey = analytics?.eventKey ?? null;
    const groupId = analytics?.groupId ?? null;
    const trigger = analytics?.trigger ?? 'mount';

    const firedSignature = useRef<string | null>(null);

    const fireAnalytics = useCallback(() => {
        if (!eventKey) {
            return;
        }

        const signature = JSON.stringify({ eventKey, groupId, payload });

        if (firedSignature.current === signature) {
            return;
        }

        recordAnalyticsEventSync({ key: eventKey, groupId, payload });
        firedSignature.current = signature;
    }, [eventKey, groupId, payload]);

    const onReady = analytics?.onReady;

    useEffect(() => {
        if (typeof onReady === 'function') {
            onReady(fireAnalytics);
        }
    }, [fireAnalytics, onReady]);

    useEffect(() => {
        if (trigger !== 'mount') {
            return;
        }

        fireAnalytics();
    }, [fireAnalytics, trigger]);

    const toneStyle: CSSProperties = {
        '--transparency-card-border-color': `var(--transparency-card-border-${tone}, ${toneDefaults[tone].border})`,
        '--transparency-card-background-color': `var(--transparency-card-background-${tone}, ${toneDefaults[tone].background})`,
        '--transparency-card-foreground-color': `var(--transparency-card-foreground-${tone}, ${toneDefaults[tone].foreground})`,
    };

    return (
        <article
            className={cn(
                'flex h-full flex-col rounded-xl border p-4 shadow-inner shadow-black/20 transition-colors',
                'border-[color:var(--transparency-card-border-color)]',
                'bg-[color:var(--transparency-card-background-color)]',
                'text-[color:var(--transparency-card-foreground-color)]',
                className,
            )}
            style={toneStyle}
        >
            <header className="mb-3 space-y-1">
                <p
                    className="text-xs uppercase tracking-wide"
                    style={{ color: 'var(--transparency-card-label-color)' }}
                >
                    {title}
                </p>
                <div className="text-2xl font-semibold leading-tight" style={{ color: 'var(--transparency-card-value-color)' }}>
                    {value}
                </div>
                {description && (
                    <p className="text-xs" style={{ color: 'var(--transparency-card-description-color)' }}>
                        {description}
                    </p>
                )}
            </header>
            {children && (
                <div className="flex-1 text-xs" style={{ color: 'var(--transparency-card-body-color)' }}>
                    {children}
                </div>
            )}
            {footer && (
                <footer className="mt-4 text-[11px]" style={{ color: 'var(--transparency-card-footer-color)' }}>
                    {footer}
                </footer>
            )}
        </article>
    );
}

export default InsightCard;
