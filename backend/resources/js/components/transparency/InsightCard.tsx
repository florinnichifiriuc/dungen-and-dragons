import { PropsWithChildren, ReactNode } from 'react';

import { cn } from '@/lib/utils';

export type InsightCardProps = PropsWithChildren<{
    title: string;
    value: ReactNode;
    description?: ReactNode;
    tone?: 'default' | 'success' | 'warning' | 'danger';
    footer?: ReactNode;
    className?: string;
}>;

const toneMap: Record<NonNullable<InsightCardProps['tone']>, string> = {
    default: 'border-zinc-800 bg-zinc-950/80 text-zinc-100',
    success: 'border-emerald-600/40 bg-emerald-500/10 text-emerald-100',
    warning: 'border-amber-500/40 bg-amber-500/10 text-amber-100',
    danger: 'border-rose-500/40 bg-rose-500/10 text-rose-100',
};

export function InsightCard({
    title,
    value,
    description,
    tone = 'default',
    footer,
    className,
    children,
}: InsightCardProps) {
    return (
        <article
            className={cn(
                'flex h-full flex-col rounded-xl border p-4 shadow-inner shadow-black/20 transition-colors',
                toneMap[tone],
                className,
            )}
        >
            <header className="mb-3 space-y-1">
                <p className="text-xs uppercase tracking-wide text-zinc-400/80">{title}</p>
                <div className="text-2xl font-semibold leading-tight">{value}</div>
                {description && <p className="text-xs text-zinc-400/90">{description}</p>}
            </header>
            {children && <div className="flex-1 text-xs text-zinc-200/90">{children}</div>}
            {footer && <footer className="mt-4 text-[11px] text-zinc-400/80">{footer}</footer>}
        </article>
    );
}

export default InsightCard;
