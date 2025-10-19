import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function formatTimestamp(value?: string | null): string {
    if (!value) {
        return 'unknown';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return 'unknown';
    }

    return date.toLocaleString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}
