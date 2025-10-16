import { useState } from 'react';

import { Head, usePage } from '@inertiajs/react';

import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';

type DigestSectionEntry = {
    summary?: string | null;
    token?: { label: string } | null;
    quest?: { title: string } | null;
    reward?: { title: string; awarded_to?: string | null } | null;
};

type PlayerDigest = {
    id: number;
    name: string;
    email: string;
    digest: {
        has_updates: boolean;
        urgency: string;
        mode: string;
        markdown: string;
        sections: {
            conditions: DigestSectionEntry[];
            quests: DigestSectionEntry[];
            rewards: DigestSectionEntry[];
        };
    };
};

type DigestPreviewProps = {
    campaign: { id: number; title: string };
    players: PlayerDigest[];
    window: { since: string; until: string };
};

const urgencyStyles: Record<string, string> = {
    critical: 'bg-rose-500/20 text-rose-200',
    warning: 'bg-amber-500/20 text-amber-100',
    calm: 'bg-emerald-500/10 text-emerald-200',
};

export default function DigestPreview() {
    const { campaign, players, window } = usePage<DigestPreviewProps>().props;
    const [copiedPlayerId, setCopiedPlayerId] = useState<number | null>(null);

    const copyMarkdown = async (player: PlayerDigest) => {
        try {
            await navigator.clipboard.writeText(player.digest.markdown);
            setCopiedPlayerId(player.id);
            setTimeout(() => setCopiedPlayerId((current) => (current === player.id ? null : current)), 2000);
        } catch (error) {
            console.error('Failed to copy digest markdown', error);
        }
    };

    return (
        <AppLayout>
            <Head title={`${campaign.title} · Digest preview`} />
            <div className="space-y-6">
                <header className="space-y-2">
                    <h1 className="text-3xl font-semibold text-zinc-100">{campaign.title} — Digest preview</h1>
                    <p className="text-sm text-zinc-400">
                        Review the latest player recap before delivery. Window: {new Date(window.since).toLocaleString()} →{' '}
                        {new Date(window.until).toLocaleString()} (UTC)
                    </p>
                </header>
                <div className="grid gap-6 lg:grid-cols-2">
                    {players.map((player) => {
                        const urgency = player.digest.urgency ?? 'calm';
                        const badgeClass = urgencyStyles[urgency] ?? urgencyStyles.calm;
                        const hasUpdates = player.digest.has_updates;

                        return (
                            <article
                                key={player.id}
                                className="flex flex-col gap-4 rounded-xl border border-zinc-700/60 bg-zinc-900/60 p-5 shadow-sm"
                            >
                                <div className="flex items-start justify-between gap-4">
                                    <div>
                                        <h2 className="text-xl font-semibold text-zinc-100">{player.name}</h2>
                                        <p className="text-xs text-zinc-400">{player.email}</p>
                                    </div>
                                    <span
                                        className={`rounded-full px-3 py-1 text-xs font-medium uppercase tracking-wide ${badgeClass}`}
                                    >
                                        {urgency}
                                    </span>
                                </div>
                                <div className="space-y-2 text-sm text-zinc-300">
                                    <p>
                                        Cadence: <span className="font-medium uppercase">{player.digest.mode}</span>
                                    </p>
                                    <p>
                                        Summary:{' '}
                                        {hasUpdates
                                            ? `${player.digest.sections.conditions.length} condition · ${player.digest.sections.quests.length} quest · ${player.digest.sections.rewards.length} reward updates`
                                            : 'No changes in this window'}
                                    </p>
                                </div>
                                <div className="space-y-3">
                                    <Textarea value={player.digest.markdown} readOnly className="min-h-[220px]" />
                                    <Button type="button" variant="outline" onClick={() => copyMarkdown(player)}>
                                        {copiedPlayerId === player.id ? 'Copied!' : 'Copy Markdown'}
                                    </Button>
                                </div>
                                {hasUpdates ? (
                                    <div className="space-y-4 text-sm text-zinc-200">
                                        {player.digest.sections.conditions.length > 0 && (
                                            <section className="space-y-2">
                                                <h3 className="text-sm font-semibold uppercase tracking-wide text-zinc-400">
                                                    Condition highlights
                                                </h3>
                                                <ul className="space-y-1 text-xs">
                                                    {player.digest.sections.conditions.map((entry, index) => (
                                                        <li key={`cond-${player.id}-${index}`} className="text-zinc-300">
                                                            <span className="font-medium">{entry.token?.label ?? 'Unknown token'}:</span>{' '}
                                                            {entry.summary ?? 'No summary available'}
                                                        </li>
                                                    ))}
                                                </ul>
                                            </section>
                                        )}
                                        {player.digest.sections.quests.length > 0 && (
                                            <section className="space-y-2">
                                                <h3 className="text-sm font-semibold uppercase tracking-wide text-zinc-400">
                                                    Quest updates
                                                </h3>
                                                <ul className="space-y-1 text-xs">
                                                    {player.digest.sections.quests.map((entry, index) => (
                                                        <li key={`quest-${player.id}-${index}`} className="text-zinc-300">
                                                            <span className="font-medium">{entry.quest?.title ?? 'Quest'}:</span>{' '}
                                                            {entry.summary ?? 'No summary available'}
                                                        </li>
                                                    ))}
                                                </ul>
                                            </section>
                                        )}
                                        {player.digest.sections.rewards.length > 0 && (
                                            <section className="space-y-2">
                                                <h3 className="text-sm font-semibold uppercase tracking-wide text-zinc-400">
                                                    Loot & rewards
                                                </h3>
                                                <ul className="space-y-1 text-xs">
                                                    {player.digest.sections.rewards.map((entry, index) => (
                                                        <li key={`reward-${player.id}-${index}`} className="text-zinc-300">
                                                            <span className="font-medium">{entry.reward?.title ?? 'Reward'}</span>
                                                            {entry.reward?.awarded_to ? ` → ${entry.reward.awarded_to}` : ''}
                                                        </li>
                                                    ))}
                                                </ul>
                                            </section>
                                        )}
                                    </div>
                                ) : null}
                            </article>
                        );
                    })}
                    {players.length === 0 && (
                        <p className="rounded-md border border-dashed border-zinc-700/60 bg-zinc-900/40 p-6 text-sm text-zinc-400">
                            No active players found for this campaign. Assign players to campaign roles to preview digests.
                        </p>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
