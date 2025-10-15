import { FormEventHandler } from 'react';

import { Head, Link, useForm } from '@inertiajs/react';

import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { InputError } from '@/components/InputError';

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

export default function CampaignShow({ campaign, available_roles }: CampaignShowProps) {
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
                                            <Link
                                                as="button"
                                                method="delete"
                                                href={route('campaigns.assignments.destroy', [campaign.id, assignment.id])}
                                                className="text-xs text-rose-300 hover:text-rose-200"
                                            >
                                                Remove
                                            </Link>
                                        </div>
                                    </li>
                                ))
                            )}
                        </ul>
                    </article>
                </section>

                <section className="space-y-6">
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
                                    <li key={invitation.id} className="flex items-center justify-between rounded-lg border border-zinc-800 bg-zinc-900/60 px-4 py-3">
                                        <div>
                                            <p className="font-medium text-zinc-100">
                                                {invitation.group ? invitation.group.name : invitation.email}
                                            </p>
                                            <p className="text-xs text-zinc-500">{invitation.expires_at ? new Date(invitation.expires_at).toLocaleString() : 'No expiry'}</p>
                                        </div>
                                        <Link
                                            as="button"
                                            method="delete"
                                            href={route('campaigns.invitations.destroy', [campaign.id, invitation.id])}
                                            className="text-xs text-rose-300 hover:text-rose-200"
                                        >
                                            Revoke
                                        </Link>
                                    </li>
                                ))
                            )}
                        </ul>
                    </article>
                </section>
            </div>
        </AppLayout>
    );
}
