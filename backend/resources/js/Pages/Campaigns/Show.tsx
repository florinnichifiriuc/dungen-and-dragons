import { FormEventHandler, useState } from 'react';

import { Head, Link, useForm } from '@inertiajs/react';

import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { InputError } from '@/components/InputError';
import AiIdeaPanel, { AiIdeaResult } from '@/components/ai/AiIdeaPanel';

type CampaignMember = {
    id: number;
    name: string;
    email: string;
    role: string;
};

type CampaignAssignment = {
    id: number;
    role: string;
    status: string;
    assignee:
        | { type: 'user'; id: number; name: string; email: string }
        | { type: 'group'; id: number; name: string }
        | null;
};

type CampaignInvitation = {
    id: number;
    role: string;
    email: string | null;
    group: { id: number; name: string } | null;
    expires_at: string | null;
    accept_url: string | null;
};

type CampaignPayload = {
    id: number;
    title: string;
    status: string;
    synopsis: string | null;
    default_timezone: string;
    start_date: string | null;
    end_date: string | null;
    turn_hours: number | null;
    group: { id: number; name: string };
    region: { id: number; name: string } | null;
    members: CampaignMember[];
    assignments: CampaignAssignment[];
    invitations: CampaignInvitation[];
    entities_count: number;
    recent_entities: { id: number; name: string; entity_type: string }[];
    quests_count: number;
    spotlight_quests: { id: number; title: string; status: string; priority: string }[];
    can_manage: boolean;
};

type CampaignShowProps = {
    campaign: CampaignPayload;
    available_roles: string[];
    available_statuses: string[];
};

const roleLabels: Record<string, string> = {
    gm: 'Game Master',
    player: 'Adventurer',
    observer: 'Observer',
};

const entityTypeLabels: Record<string, string> = {
    character: 'Character',
    npc: 'NPC',
    monster: 'Monster',
    item: 'Item',
    location: 'Location',
};

const questStatusLabels: Record<string, string> = {
    planned: 'Planned',
    active: 'Active',
    completed: 'Completed',
    failed: 'Failed',
};

const questPriorityLabels: Record<string, string> = {
    critical: 'Critical',
    high: 'High',
    standard: 'Standard',
    low: 'Low',
};

const questPriorityStyles: Record<string, string> = {
    critical: 'bg-rose-500/20 text-rose-100',
    high: 'bg-amber-500/20 text-amber-100',
    standard: 'bg-zinc-700/40 text-zinc-200',
    low: 'bg-zinc-800 text-zinc-300',
};

export default function CampaignShow({ campaign, available_roles }: CampaignShowProps) {
    const [copiedInvitationId, setCopiedInvitationId] = useState<number | null>(null);
    const [aiTaskSuggestions, setAiTaskSuggestions] = useState<{ title: string; description?: string }[]>([]);
    const roleForm = useForm({
        assignee_type: 'user',
        assignee_id: '',
        role: available_roles[0] ?? 'player',
    });

    const invitationForm = useForm({
        group_id: '',
        email: '',
        role: available_roles[0] ?? 'player',
        expires_at: '',
    });

    const submitRole: FormEventHandler = (event) => {
        event.preventDefault();
        roleForm.post(route('campaigns.assignments.store', campaign.id), {
            preserveScroll: true,
            onSuccess: () => roleForm.reset('assignee_id'),
        });
    };

    const submitInvitation: FormEventHandler = (event) => {
        event.preventDefault();
        invitationForm.post(route('campaigns.invitations.store', campaign.id), {
            preserveScroll: true,
            onSuccess: () => invitationForm.reset('email'),
        });
    };

    const copyInvitationLink = async (invitation: CampaignInvitation) => {
        if (!invitation.accept_url) {
            return;
        }

        try {
            await navigator.clipboard.writeText(invitation.accept_url);
            setCopiedInvitationId(invitation.id);

            setTimeout(() => {
                setCopiedInvitationId((current) => (current === invitation.id ? null : current));
            }, 2500);
        } catch (error) {
            console.error('Unable to copy invitation link', error);
        }
    };

    const canManage = campaign.can_manage;

    return (
        <AppLayout>
            <Head title={campaign.title} />

            <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 className="text-3xl font-semibold text-zinc-100">{campaign.title}</h1>
                    <p className="mt-2 text-sm text-zinc-400">{campaign.synopsis ?? 'No synopsis yet. Sketch the campaign tone soon.'}</p>
                </div>

                <div className="flex items-center gap-3">
                    <Button
                        asChild
                        variant="outline"
                        className="border-emerald-600/60 text-sm text-emerald-200 hover:bg-emerald-500/10"
                    >
                        <Link href={route('campaigns.tasks.index', campaign.id)}>Task board</Link>
                    </Button>
                    <Button
                        asChild
                        variant="outline"
                        className="border-purple-600/60 text-sm text-purple-200 hover:bg-purple-500/10"
                    >
                        <Link href={route('campaigns.entities.index', campaign.id)}>Lore codex</Link>
                    </Button>
                    <Button
                        asChild
                        variant="outline"
                        className="border-sky-600/60 text-sm text-sky-200 hover:bg-sky-500/10"
                    >
                        <Link href={route('campaigns.quests.index', campaign.id)}>Quest log</Link>
                    </Button>
                    <Button
                        asChild
                        variant="outline"
                        className="border-amber-500/60 text-sm text-amber-200 hover:bg-amber-500/10"
                    >
                        <Link href={route('campaigns.sessions.index', { campaign: campaign.id })}>Session workspace</Link>
                    </Button>
                    <Button asChild variant="outline" className="border-zinc-700 text-sm">
                        <Link href={route('campaigns.edit', campaign.id)}>Edit campaign</Link>
                    </Button>
                    <Button asChild variant="outline" className="border-rose-700/60 text-sm text-rose-200 hover:bg-rose-900/40">
                        <Link method="delete" href={route('campaigns.destroy', campaign.id)} as="button">
                            Archive
                        </Link>
                    </Button>
                </div>
            </div>

            <div className="mt-8 grid gap-8 lg:grid-cols-3">
                <section className="lg:col-span-2 space-y-6">
                    <article className="rounded-xl border border-zinc-800 bg-zinc-950/60 p-6 shadow-inner shadow-black/40">
                        <header className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <h2 className="text-lg font-semibold text-zinc-100">Details</h2>
                            <span className="rounded-full bg-indigo-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-indigo-300">
                                {campaign.status}
                            </span>
                        </header>

                        <dl className="mt-4 grid gap-4 text-sm text-zinc-400 sm:grid-cols-2">
                            <div>
                                <dt className="text-xs uppercase tracking-wide text-zinc-500">Group</dt>
                                <dd>{campaign.group.name}</dd>
                            </div>
                            <div>
                                <dt className="text-xs uppercase tracking-wide text-zinc-500">Region</dt>
                                <dd>{campaign.region ? campaign.region.name : 'Unassigned'}</dd>
                            </div>
                            <div>
                                <dt className="text-xs uppercase tracking-wide text-zinc-500">Turn cadence</dt>
                                <dd>{campaign.turn_hours ? `${campaign.turn_hours} hours` : 'Unset'}</dd>
                            </div>
                            <div>
                                <dt className="text-xs uppercase tracking-wide text-zinc-500">Timezone</dt>
                                <dd>{campaign.default_timezone}</dd>
                            </div>
                            <div>
                                <dt className="text-xs uppercase tracking-wide text-zinc-500">Start date</dt>
                                <dd>{campaign.start_date ?? 'Pending'}</dd>
                            </div>
                            <div>
                                <dt className="text-xs uppercase tracking-wide text-zinc-500">End date</dt>
                                <dd>{campaign.end_date ?? 'Open'}</dd>
                            </div>
                        </dl>
                    </article>

                    <article className="rounded-xl border border-zinc-800 bg-zinc-950/60 p-6 shadow-inner shadow-black/40">
                        <header className="flex items-center justify-between">
                            <h2 className="text-lg font-semibold text-zinc-100">Lore codex</h2>
                            <span className="text-xs uppercase tracking-wide text-zinc-500">
                                {campaign.entities_count} entr{campaign.entities_count === 1 ? 'y' : 'ies'}
                            </span>
                        </header>

                        {campaign.entities_count === 0 ? (
                            <p className="mt-4 rounded-lg border border-dashed border-zinc-800 bg-zinc-900/40 p-4 text-sm text-zinc-400">
                                No lore entries captured yet. Chronicle key NPCs, factions, and relics to anchor each session.
                            </p>
                        ) : (
                            <ul className="mt-4 space-y-3">
                                {campaign.recent_entities.map((entity) => (
                                    <li key={entity.id} className="flex items-center justify-between rounded-lg border border-zinc-800 bg-zinc-900/60 px-4 py-3">
                                        <div>
                                            <p className="font-medium text-zinc-100">{entity.name}</p>
                                            <p className="text-xs text-zinc-500">
                                                {entityTypeLabels[entity.entity_type] ?? entity.entity_type}
                                            </p>
                                        </div>
                                        <Link
                                            href={route('campaigns.entities.show', [campaign.id, entity.id])}
                                            className="text-xs font-semibold uppercase tracking-wide text-amber-300 hover:text-amber-200"
                                        >
                                            View
                                        </Link>
                                    </li>
                                ))}
                                {campaign.entities_count > campaign.recent_entities.length && (
                                    <li className="text-xs text-zinc-500">
                                        + {campaign.entities_count - campaign.recent_entities.length} more awaiting discovery
                                    </li>
                                )}
                            </ul>
                        )}
                    </article>

                    <article className="rounded-xl border border-zinc-800 bg-zinc-950/60 p-6 shadow-inner shadow-black/40">
                        <header className="flex items-center justify-between">
                            <h2 className="text-lg font-semibold text-zinc-100">Quest log</h2>
                            <span className="text-xs uppercase tracking-wide text-zinc-500">
                                {campaign.quests_count} active quest{campaign.quests_count === 1 ? '' : 's'}
                            </span>
                        </header>

                        {campaign.quests_count === 0 ? (
                            <p className="mt-4 rounded-lg border border-dashed border-zinc-800 bg-zinc-900/40 p-4 text-sm text-zinc-400">
                                No quests recorded yet. Capture threads so each turn has a clear objective and stakes.
                            </p>
                        ) : (
                            <ul className="mt-4 space-y-3">
                                {campaign.spotlight_quests.map((quest) => (
                                    <li
                                        key={quest.id}
                                        className="flex flex-col gap-3 rounded-lg border border-zinc-800 bg-zinc-900/60 px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
                                    >
                                        <div>
                                            <p className="text-sm font-medium text-zinc-100">{quest.title}</p>
                                            <p className="text-xs uppercase tracking-wide text-zinc-500">
                                                {questStatusLabels[quest.status] ?? quest.status} quest
                                            </p>
                                        </div>
                                        <div className="flex items-center gap-3">
                                            <span className={`rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-wide ${questPriorityStyles[quest.priority] ?? 'bg-zinc-800 text-zinc-200'}`}>
                                                {questPriorityLabels[quest.priority] ?? quest.priority}
                                            </span>
                                            <Button
                                                asChild
                                                variant="outline"
                                                className="border-sky-500/40 text-xs text-sky-200 hover:bg-sky-500/10"
                                            >
                                                <Link href={route('campaigns.quests.show', [campaign.id, quest.id])}>Open</Link>
                                            </Button>
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </article>

                    <article className="rounded-xl border border-zinc-800 bg-zinc-950/60 p-6 shadow-inner shadow-black/40">
                        <header className="flex items-center justify-between">
                            <h2 className="text-lg font-semibold text-zinc-100">Party roster</h2>
                            <span className="text-xs uppercase tracking-wide text-zinc-500">{campaign.members.length} members</span>
                        </header>
                        <ul className="mt-4 space-y-3">
                            {campaign.members.map((member) => (
                                <li key={member.id} className="flex items-center justify-between rounded-lg border border-zinc-800 bg-zinc-900/60 px-4 py-3">
                                    <div>
                                        <p className="font-medium text-zinc-100">{member.name}</p>
                                        <p className="text-xs text-zinc-500">{member.email}</p>
                                    </div>
                                    <span className="text-xs font-semibold uppercase tracking-wide text-amber-300">
                                        {roleLabels[member.role] ?? member.role}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    </article>

                    <article className="rounded-xl border border-zinc-800 bg-zinc-950/60 p-6 shadow-inner shadow-black/40">
                        <header className="flex items-center justify-between">
                            <h2 className="text-lg font-semibold text-zinc-100">Assigned roles</h2>
                            <span className="text-xs uppercase tracking-wide text-zinc-500">{campaign.assignments.length} active</span>
                        </header>
                        <ul className="mt-4 space-y-3">
                            {campaign.assignments.length === 0 ? (
                                <li className="rounded-lg border border-dashed border-zinc-800 bg-zinc-900/40 p-4 text-sm text-zinc-400">
                                    No roles assigned yet. Add game masters or players below.
                                </li>
                            ) : (
                                campaign.assignments.map((assignment) => (
                                    <li key={assignment.id} className="flex items-center justify-between rounded-lg border border-zinc-800 bg-zinc-900/60 px-4 py-3">
                                        <div>
                                            <p className="font-medium text-zinc-100">{assignment.assignee?.type === 'group' ? assignment.assignee.name : assignment.assignee?.name ?? 'Unknown'}</p>
                                            {assignment.assignee?.type === 'user' && (
                                                <p className="text-xs text-zinc-500">{assignment.assignee.email}</p>
                                            )}
                                        </div>
                                        <div className="flex items-center gap-3">
                                            <span className="text-xs font-semibold uppercase tracking-wide text-indigo-300">
                                                {roleLabels[assignment.role] ?? assignment.role}
                                            </span>
                                            {canManage && (
                                                <Link
                                                    as="button"
                                                    method="delete"
                                                    href={route('campaigns.assignments.destroy', [campaign.id, assignment.id])}
                                                    className="text-xs text-rose-300 hover:text-rose-200"
                                                >
                                                    Remove
                                                </Link>
                                            )}
                                        </div>
                                    </li>
                                ))
                            )}
                        </ul>
                    </article>
                </section>

                <section className="space-y-6">
                    <article className="rounded-xl border border-amber-500/30 bg-amber-500/10 p-6 text-amber-100 shadow-inner shadow-amber-900/20">
                        <header className="mb-3">
                            <h2 className="text-lg font-semibold">Task board primer</h2>
                            <p className="mt-1 text-sm text-amber-100/80">
                                Track prep beats, player follow-ups, and session goals. Use the AI cartographer below to turn a few words into backlog cards.
                            </p>
                        </header>
                        <div className="space-y-4">
                            <AiIdeaPanel
                                domain="campaign_tasks"
                                endpoint={route('campaigns.ai.tasks', campaign.id)}
                                title="AI backlog steward"
                                description="Describe upcoming arcs or blockers. The steward will draft tasks and a prompt for Automatic1111 map renders if relevant."
                                defaultPrompt={`Prep beats for ${campaign.title}`}
                                context={{
                                    campaign_title: campaign.title,
                                    synopsis: campaign.synopsis,
                                    region: campaign.region?.name,
                                }}
                                actions={[
                                    {
                                        label: 'Preview suggested cards',
                                        onApply: (result: AiIdeaResult) => {
                                            const structured = result.structured?.fields?.tasks;
                                            if (Array.isArray(structured)) {
                                                setAiTaskSuggestions(
                                                    structured
                                                        .slice(0, 5)
                                                        .map((task) => ({
                                                            title: typeof task.title === 'string' ? task.title : result.text,
                                                            description: typeof task.description === 'string' ? task.description : undefined,
                                                        }))
                                                );
                                            } else {
                                                setAiTaskSuggestions([
                                                    {
                                                        title: result.text.slice(0, 120),
                                                        description: result.text.length > 120 ? result.text : undefined,
                                                    },
                                                ]);
                                            }
                                        },
                                    },
                                ]}
                            />
                            {aiTaskSuggestions.length > 0 && (
                                <div className="space-y-2 rounded-lg border border-amber-400/40 bg-amber-500/15 p-4 text-amber-100">
                                    <h3 className="text-sm font-semibold uppercase tracking-wide">Staged cards</h3>
                                    <ul className="space-y-2 text-sm">
                                        {aiTaskSuggestions.map((task, index) => (
                                            <li key={`${task.title}-${index}`} className="rounded-md border border-amber-400/30 bg-amber-500/10 p-3">
                                                <p className="font-semibold">{task.title}</p>
                                                {task.description && <p className="mt-1 text-xs text-amber-100/80">{task.description}</p>}
                                            </li>
                                        ))}
                                    </ul>
                                    <Button
                                        asChild
                                        size="sm"
                                        variant="outline"
                                        className="mt-2 border-amber-300/60 text-amber-100 hover:bg-amber-500/20"
                                    >
                                        <Link href={route('campaigns.tasks.index', campaign.id)}>Open task board</Link>
                                    </Button>
                                </div>
                            )}
                        </div>
                    </article>
                    {canManage && (
                        <form onSubmit={submitRole} className="rounded-xl border border-zinc-800 bg-zinc-950/60 p-6 shadow-inner shadow-black/40">
                        <header className="mb-4">
                            <h2 className="text-lg font-semibold text-zinc-100">Assign a role</h2>
                            <p className="mt-1 text-sm text-zinc-400">Link an existing group member to the campaign.</p>
                        </header>

                        <div className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="assignee_id">Member</Label>
                                <select
                                    id="assignee_id"
                                    className="w-full rounded-md border border-zinc-800 bg-zinc-950/70 px-3 py-2 text-sm text-zinc-100 focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-500/40"
                                    value={roleForm.data.assignee_id}
                                    onChange={(event) => roleForm.setData('assignee_id', event.target.value)}
                                    required
                                >
                                    <option value="" disabled>
                                        Select a member
                                    </option>
                                    {campaign.members.map((member) => (
                                        <option key={member.id} value={member.id}>
                                            {member.name}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={roleForm.errors.assignee_id} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="role">Role</Label>
                                <select
                                    id="role"
                                    className="w-full rounded-md border border-zinc-800 bg-zinc-950/70 px-3 py-2 text-sm text-zinc-100 focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-500/40"
                                    value={roleForm.data.role}
                                    onChange={(event) => roleForm.setData('role', event.target.value)}
                                    required
                                >
                                    {available_roles.map((role) => (
                                        <option key={role} value={role}>
                                            {roleLabels[role] ?? role}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={roleForm.errors.role} />
                            </div>
                        </div>

                        <div className="mt-4 flex items-center justify-between">
                            <Button type="submit" disabled={roleForm.processing}>
                                Assign role
                            </Button>
                            <InputError message={roleForm.errors.assignee_type} />
                        </div>
                        </form>
                    )}

                    {canManage && (
                        <form onSubmit={submitInvitation} className="rounded-xl border border-zinc-800 bg-zinc-950/60 p-6 shadow-inner shadow-black/40">
                            <header className="mb-4">
                                <h2 className="text-lg font-semibold text-zinc-100">Send invitation</h2>
                                <p className="mt-1 text-sm text-zinc-400">Invite another group or external player via email.</p>
                            </header>

                        <div className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="invite_email">Invite by email</Label>
                                <Input
                                    id="invite_email"
                                    type="email"
                                    value={invitationForm.data.email}
                                    onChange={(event) => invitationForm.setData('email', event.target.value)}
                                    placeholder="dungeonmaster@example.com"
                                />
                                <InputError message={invitationForm.errors.email} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="invite_role">Requested role</Label>
                                <select
                                    id="invite_role"
                                    className="w-full rounded-md border border-zinc-800 bg-zinc-950/70 px-3 py-2 text-sm text-zinc-100 focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-500/40"
                                    value={invitationForm.data.role}
                                    onChange={(event) => invitationForm.setData('role', event.target.value)}
                                    required
                                >
                                    {available_roles.map((role) => (
                                        <option key={role} value={role}>
                                            {roleLabels[role] ?? role}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={invitationForm.errors.role} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="expires_at">Expires at</Label>
                                <Input
                                    id="expires_at"
                                    type="datetime-local"
                                    value={invitationForm.data.expires_at}
                                    onChange={(event) => invitationForm.setData('expires_at', event.target.value)}
                                />
                                <InputError message={invitationForm.errors.expires_at} />
                            </div>
                        </div>

                            <div className="mt-4 flex items-center justify-between">
                                <Button type="submit" disabled={invitationForm.processing}>
                                    Record invitation
                                </Button>
                                <InputError message={invitationForm.errors.group_id} />
                            </div>
                        </form>
                    )}

                    {canManage && (
                        <article className="rounded-xl border border-zinc-800 bg-zinc-950/60 p-6 shadow-inner shadow-black/40">
                            <header className="flex items-center justify-between">
                                <h2 className="text-lg font-semibold text-zinc-100">Pending invitations</h2>
                                <span className="text-xs uppercase tracking-wide text-zinc-500">{campaign.invitations.length}</span>
                            </header>
                            <ul className="mt-4 space-y-3">
                                {campaign.invitations.length === 0 ? (
                                    <li className="rounded-lg border border-dashed border-zinc-800 bg-zinc-900/40 p-4 text-sm text-zinc-400">
                                        No invitations recorded.
                                    </li>
                                ) : (
                                    campaign.invitations.map((invitation) => (
                                        <li key={invitation.id} className="space-y-2 rounded-lg border border-zinc-800 bg-zinc-900/60 px-4 py-3">
                                            <div className="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                                                <div>
                                                    <p className="font-medium text-zinc-100">
                                                        {invitation.group ? invitation.group.name : invitation.email}
                                                    </p>
                                                    <p className="text-xs uppercase tracking-wide text-amber-400/80">{roleLabels[invitation.role] ?? invitation.role}</p>
                                                </div>
                                                <div className="flex items-center gap-3">
                                                    {invitation.accept_url ? (
                                                        <Button
                                                            type="button"
                                                            size="sm"
                                                            variant="outline"
                                                            className="border-emerald-600/60 text-emerald-200 hover:bg-emerald-500/10"
                                                            onClick={() => copyInvitationLink(invitation)}
                                                        >
                                                            {copiedInvitationId === invitation.id ? 'Link copied!' : 'Copy link'}
                                                        </Button>
                                                    ) : null}
                                                    <Link
                                                        as="button"
                                                        method="delete"
                                                        href={route('campaigns.invitations.destroy', [campaign.id, invitation.id])}
                                                        className="text-xs text-rose-300 hover:text-rose-200"
                                                    >
                                                        Revoke
                                                    </Link>
                                                </div>
                                            </div>
                                            <p className="text-xs text-zinc-500">
                                                {invitation.expires_at ? new Date(invitation.expires_at).toLocaleString() : 'No expiry'}
                                            </p>
                                        </li>
                                    ))
                                )}
                            </ul>
                        </article>
                    )}
                </section>
            </div>
        </AppLayout>
    );
}
