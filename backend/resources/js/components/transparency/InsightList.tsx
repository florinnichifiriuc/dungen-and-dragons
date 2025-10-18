import { ReactNode } from 'react';

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
        return <p className={className ? `${className} text-xs text-zinc-500` : 'text-xs text-zinc-500'}>{emptyLabel}</p>;
    }

    return (
        <ul className={className ? `${className} space-y-2` : 'space-y-2'}>
            {items.map((item) => (
                <li
                    key={item.id}
                    className="flex items-start gap-3 rounded-lg border border-zinc-800/60 bg-zinc-950/70 p-3 text-xs text-zinc-200"
                >
                    {item.icon && <span className="mt-[2px] text-amber-300" aria-hidden>{item.icon}</span>}
                    <div className="space-y-1">
                        <p className="font-medium text-zinc-100">{item.title}</p>
                        {item.description && <p className="text-[11px] text-zinc-400">{item.description}</p>}
                    </div>
                </li>
            ))}
        </ul>
    );
}

export default InsightList;
