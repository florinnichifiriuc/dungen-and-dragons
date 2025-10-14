import type { PropsWithChildren } from 'react';

import { cn } from '@/lib/utils';

export function InputError({ children, className }: PropsWithChildren<{ className?: string }>) {
    if (!children) {
        return null;
    }

    return <p className={cn('text-sm text-rose-400', className)}>{children}</p>;
}
