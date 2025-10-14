import * as React from 'react';

import { cn } from '@/lib/utils';

export const Checkbox = React.forwardRef<HTMLInputElement, React.ComponentPropsWithoutRef<'input'>>(
    ({ className, ...props }, ref) => (
        <input
            ref={ref}
            type="checkbox"
            className={cn(
                'h-4 w-4 rounded border border-zinc-700 bg-zinc-900 text-indigo-400 outline-none transition focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-zinc-900',
                className
            )}
            {...props}
        />
    )
);

Checkbox.displayName = 'Checkbox';
