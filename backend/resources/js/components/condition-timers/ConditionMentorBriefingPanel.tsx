import { router } from '@inertiajs/react';
import { Sparkles } from 'lucide-react';
import { useMemo } from 'react';

import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

type MentorBriefingFocus = {
    critical_conditions?: string[];
    unacknowledged_tokens?: string[];
    recurring_conditions?: string[];
};

type MentorBriefing = {
    focus?: MentorBriefingFocus;
    briefing?: string;
    requested_at?: string;
};

type ConditionMentorBriefingPanelProps = {
    groupId: number;
    enabled: boolean;
    mentorBriefing: MentorBriefing;
    className?: string;
};

const formatTimestamp = (value?: string): string | null => {
    if (!value) {
        return null;
    }

    try {
        return new Intl.DateTimeFormat('en-US', {
            dateStyle: 'medium',
            timeStyle: 'short',
        }).format(new Date(value));
    } catch (error) {
        return value;
    }
};

export default function ConditionMentorBriefingPanel({
    groupId,
    enabled,
    mentorBriefing,
    className,
}: ConditionMentorBriefingPanelProps) {
    const focus = mentorBriefing.focus ?? {};
    const generatedAt = formatTimestamp(mentorBriefing.requested_at);

    const focusEntries = useMemo(() => {
        const entries: { title: string; items: string[] }[] = [];

        if (focus.critical_conditions && focus.critical_conditions.length > 0) {
            entries.push({ title: 'Critical effects', items: focus.critical_conditions });
        }

        if (focus.unacknowledged_tokens && focus.unacknowledged_tokens.length > 0) {
            entries.push({ title: 'Awaiting acknowledgement', items: focus.unacknowledged_tokens });
        }

        if (focus.recurring_conditions && focus.recurring_conditions.length > 0) {
            entries.push({ title: 'Recurring troubles', items: focus.recurring_conditions });
        }

        return entries;
    }, [focus]);

    const toggleMentorBriefings = (nextEnabled: boolean) => {
        router.patch(
            route('groups.condition-transparency.mentor-briefings.update', groupId),
            { enabled: nextEnabled },
            { preserveScroll: true },
        );
    };

    return (
        <section
            className={cn(
                'rounded-xl border border-zinc-800/80 bg-zinc-950/80 p-6 text-sm text-zinc-200 shadow-inner shadow-black/40',
                className,
            )}
        >
            <header className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <h3 className="flex items-center gap-2 text-lg font-semibold">
                        <Sparkles className="h-5 w-5 text-amber-300" aria-hidden /> Mentor briefing
                    </h3>
                    <p className="text-xs text-zinc-500">
                        A spoiler-safe briefing crafted by our AI mentor. It highlights concerning conditions and celebrates party
                        wins without revealing GM secrets.
                    </p>
                </div>
                <div className="flex items-center gap-2">
                    <span className="text-xs text-zinc-500">{enabled ? 'Enabled' : 'Paused'}</span>
                    <Button
                        size="sm"
                        variant="outline"
                        className={enabled ? 'border-amber-400/50 text-amber-200 hover:border-amber-300' : ''}
                        onClick={() => toggleMentorBriefings(!enabled)}
                    >
                        {enabled ? 'Pause briefings' : 'Enable briefings'}
                    </Button>
                </div>
            </header>

            {mentorBriefing.briefing ? (
                <article className="mt-4 space-y-3 rounded-lg border border-zinc-800/60 bg-zinc-950/80 p-4">
                    <p className="whitespace-pre-line text-sm text-zinc-200">{mentorBriefing.briefing}</p>
                    {focusEntries.length > 0 && (
                        <div className="space-y-2">
                            {focusEntries.map((entry) => (
                                <div key={entry.title}>
                                    <p className="text-xs font-semibold uppercase tracking-wide text-zinc-400">{entry.title}</p>
                                    <ul className="mt-1 list-disc space-y-1 pl-5 text-xs text-zinc-400">
                                        {entry.items.map((item) => (
                                            <li key={item}>{item}</li>
                                        ))}
                                    </ul>
                                </div>
                            ))}
                        </div>
                    )}
                    {generatedAt && (
                        <p className="text-[11px] text-zinc-500">Generated {generatedAt}</p>
                    )}
                </article>
            ) : (
                <p className="mt-4 text-xs text-zinc-500">
                    No mentor briefing available yet. Enable the mentor to receive contextual tips based on current conditions.
                </p>
            )}
        </section>
    );
}
