import { ReactNode } from 'react';

import { cn } from '@/lib/utils';

export type InsightListItem = {
    id: string;
    title: ReactNode;
    description?: ReactNode;
    icon?: ReactNode;
};

export type InsightListProps = {
    items: InsightListItem[];
    emptyLabel?: ReactNode;
    className?: string;
};

export function InsightList({ items, emptyLabel, className }: InsightListProps) {
    if (items.length === 0) {
        return (
            <p
                className={cn('text-xs', className)}
                style={{ color: 'var(--transparency-list-empty-color)' }}
            >
                {emptyLabel}
            </p>
        );
    }

    return (
        <ul className={cn('space-y-2', className)}>
            {items.map((item) => (
                <li
                    key={item.id}
                    className="flex items-start gap-3 rounded-lg border p-3 text-xs"
                    style={{
                        borderColor: 'var(--transparency-list-border-color)',
                        background: 'var(--transparency-list-background-color)',
                        color: 'var(--transparency-list-title-color)',
                    }}
                >
                    {item.icon && (
                        <span
                            className="mt-[2px]"
                            style={{ color: 'var(--transparency-list-icon-color)' }}
                            aria-hidden
                        >
                            {item.icon}
                        </span>
                    )}
                    <div className="space-y-1">
                        <p className="font-medium" style={{ color: 'var(--transparency-list-title-color)' }}>
                            {item.title}
                        </p>
                        {item.description && (
                            <p className="text-[11px]" style={{ color: 'var(--transparency-list-description-color)' }}>
                                {item.description}
                            </p>
                        )}
                    </div>
                </li>
            ))}
        </ul>
    );
}

export default InsightList;
