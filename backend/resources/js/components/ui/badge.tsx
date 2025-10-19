import { PropsWithChildren } from 'react';

import { cn } from '@/lib/utils';

type BadgeProps = PropsWithChildren<{ className?: string; variant?: 'default' | 'secondary' | 'outline' }>; 

export function Badge({ className, variant = 'default', children }: BadgeProps) {
    const base = 'inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-semibold uppercase tracking-wide';
    const palette = {
        default: 'bg-amber-500/20 text-amber-100',
        secondary: 'bg-indigo-500/20 text-indigo-100',
        outline: 'border border-zinc-500/60 text-zinc-200',
    }[variant];

    return <span className={cn(base, palette, className)}>{children}</span>;
}

export default Badge;
