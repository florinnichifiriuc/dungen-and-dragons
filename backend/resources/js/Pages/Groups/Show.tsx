import { FormEvent, useCallback, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';

import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type GroupMember = {
    id: number;
    user_id: number;
    name: string;
    email: string;
    role: string;
    is_viewer: boolean;
};

type RegionSummary = {
    id: number;
    world_id: number | null;
    name: string;
    summary: string | null;
    ai_controlled: boolean;
    ai_delegate_summary: string | null;
    dungeon_master: { id: number; name: string } | null;
    turn_configuration: {
        turn_duration_hours: number;
        next_turn_at: string | null;
        last_processed_at: string | null;
        is_due: boolean;
    } | null;
    recent_turns: {
        id: number;
        number: number;
        processed_at: string | null;
        summary: string | null;
        used_ai_fallback: boolean;
        processed_by: { id: number; name: string } | null;
    }[];
    can_process_turn: boolean;
    can_delegate_to_ai: boolean;
};

type WorldSummary = {
    id: number;
    name: string;
    summary: string | null;
    description: string | null;
    default_turn_duration_hours: number;
    regions: RegionSummary[];
};

type TileTemplateSummary = {
    id: number;
    name: string;
    key: string | null;
    terrain_type: string;
    movement_cost: number;
    defense_bonus: number;
    world: { id: number; name: string } | null;
    creator: { id: number; name: string } | null;
};

type MapSummary = {
    id: number;
    title: string;
    base_layer: string;
    orientation: string;
    tile_count: number;
    region: { id: number; name: string } | null;
};

type GroupPayload = {
    id: number;
    name: string;
    description: string | null;
    members: GroupMember[];
    worlds: WorldSummary[];
    regions: RegionSummary[];
    campaigns: { id: number; title: string; status: string }[];
    tile_templates: TileTemplateSummary[];
    maps: MapSummary[];
};

type ViewerMembership = {
    id: number;
    role: string;
} | null;

type GroupPermissions = {
    manage_members: boolean;
    promote_to_owner: boolean;
};

type GroupShowProps = {
    group: GroupPayload;
    viewer_membership: ViewerMembership;
    permissions: GroupPermissions;
    join_code: string | null;
    role_options: string[];
};

const roleLabels: Record<string, string> = {
    owner: 'Game Master',
    'dungeon-master': 'Dungeon Master',
    player: 'Adventurer',
};

export default function GroupShow({ group, viewer_membership, permissions, join_code, role_options }: GroupShowProps) {
    const defaultRole = role_options.includes('player') ? 'player' : role_options[0] ?? 'player';
    const inviteForm = useForm({
        email: '',
        role: defaultRole,
    });
    const [copied, setCopied] = useState(false);
    const [roleUpdating, setRoleUpdating] = useState<number | null>(null);
    const [removing, setRemoving] = useState<number | null>(null);
    const [removingTemplate, setRemovingTemplate] = useState<number | null>(null);
    const [removingMap, setRemovingMap] = useState<number | null>(null);
    const [delegatingRegion, setDelegatingRegion] = useState<number | null>(null);
    const [aiFocus, setAiFocus] = useState<Record<number, string>>({});

    const manageMembers = permissions.manage_members;
    const promoteToOwner = permissions.promote_to_owner;
    const totalRegions = group.regions.length;

    const handleInvite = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        inviteForm.post(route('groups.memberships.store', group.id), {
            preserveScroll: true,
            onSuccess: () => {
                inviteForm.reset();
                setTimeout(() => inviteForm.clearErrors(), 0);
            },
        });
    };

    const handleRoleChange = (member: GroupMember, role: string) => {
        if (role === member.role) {
            return;
        }

        setRoleUpdating(member.id);
        router.patch(route('groups.memberships.update', [group.id, member.id]), { role }, {
            preserveScroll: true,
            onFinish: () => setRoleUpdating(null),
        });
    };

    const handleRemove = (member: GroupMember) => {
        setRemoving(member.id);
        router.delete(route('groups.memberships.destroy', [group.id, member.id]), {
            preserveScroll: true,
            onFinish: () => setRemoving(null),
        });
    };

    const handleTemplateRemove = (template: TileTemplateSummary) => {
        setRemovingTemplate(template.id);
        router.delete(route('groups.tile-templates.destroy', [group.id, template.id]), {
            preserveScroll: true,
            onFinish: () => setRemovingTemplate(null),
        });
    };

    const handleMapRemove = (map: MapSummary) => {
        setRemovingMap(map.id);
        router.delete(route('groups.maps.destroy', [group.id, map.id]), {
            preserveScroll: true,
            onFinish: () => setRemovingMap(null),
        });
    };

    const handleAiDelegate = (event: FormEvent<HTMLFormElement>, region: RegionSummary) => {
        event.preventDefault();
        setDelegatingRegion(region.id);
        router.post(route('groups.regions.ai-delegate.store', [group.id, region.id]), {
            focus: aiFocus[region.id] ?? '',
        }, {
            preserveScroll: true,
            onFinish: () => setDelegatingRegion(null),
            onSuccess: () =>
                setAiFocus((current) => ({
                    ...current,
                    [region.id]: '',
                })),
        });
    };

    const copyJoinCode = useCallback(async () => {
        if (!join_code) {
            return;
        }

        try {
            if (typeof navigator !== 'undefined' && navigator.clipboard) {
                await navigator.clipboard.writeText(join_code);
                setCopied(true);
                setTimeout(() => setCopied(false), 2000);
            }
        } catch (error) {
            setCopied(false);
        }
    }, [join_code]);

    return (
        <AppLayout>
            <Head title={group.name} />

            <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 className="text-3xl font-semibold text-zinc-100">{group.name}</h1>
                    {group.description ? (
                        <p className="mt-2 max-w-3xl text-sm text-zinc-400">{group.description}</p>
                    ) : (
                        <p className="mt-2 text-sm text-zinc-500">No primer yet. Share the party&apos;s legend soon.</p>
                    )}
                    {viewer_membership && (
                        <p className="mt-3 text-xs uppercase tracking-wide text-indigo-300">
                            Your role: {roleLabels[viewer_membership.role] ?? viewer_membership.role}
                        </p>
                    )}
                </div>

                <div className="flex items-center gap-3">
                    <Button asChild>
                        <Link href={route('groups.worlds.create', group.id)}>Create world</Link>
                    </Button>
                    <Button asChild variant="outline" className="border-zinc-700 text-sm">
                        <Link href={route('groups.edit', group.id)}>Edit group</Link>
                    </Button>
                </div>
            </div>

            <div className="mt-10 grid gap-8 lg:grid-cols-2">
                <section className="rounded-xl border border-zinc-800 bg-zinc-950/60 p-6 shadow-inner shadow-black/40">
                    <header className="flex items-center justify-between">
                        <h2 className="text-lg font-semibold text-zinc-100">Party roster</h2>
                        <span className="text-xs uppercase tracking-wide text-zinc-500">{group.members.length} members</span>
                    </header>

                    <div className="mt-4 space-y-4">
                        {manageMembers && join_code && (
                            <div className="flex flex-col gap-3 rounded-lg border border-indigo-500/40 bg-indigo-500/10 p-4 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p className="text-xs uppercase tracking-wide text-indigo-200/80">Join code</p>
                                    <p className="font-mono text-lg tracking-[0.4em] text-indigo-100">{join_code}</p>
                                </div>
                                <div className="flex items-center gap-3">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        className="border-indigo-400/60 text-indigo-100"
                                        onClick={copyJoinCode}
                                    >
                                        Copy
                                    </Button>
                                    {copied && <span className="text-xs text-indigo-200">Copied!</span>}
                                </div>
                            </div>
                        )}

                        {manageMembers && (
                            <div className="rounded-lg border border-zinc-800 bg-zinc-900/60 p-4">
                                <h3 className="text-sm font-semibold text-zinc-200">Invite new adventurer</h3>
                                <p className="mt-1 text-xs text-zinc-500">
                                    Send an existing account into the roster by email. They will arrive as an adventurer unless you grant a higher mantle.
                                </p>
                                <form onSubmit={handleInvite} className="mt-4 space-y-4">
                                    <div className="grid gap-4 sm:grid-cols-[2fr,1fr]">
                                        <div className="space-y-2">
                                            <Label htmlFor="invite-email" className="text-xs uppercase tracking-wide text-zinc-400">
                                                Email
                                            </Label>
                                            <Input
                                                id="invite-email"
                                                type="email"
                                                value={inviteForm.data.email}
                                                onChange={(event) => inviteForm.setData('email', event.target.value)}
                                                placeholder="hero@realm.test"
                                                className="text-sm"
                                            />
                                            {inviteForm.errors.email && <p className="text-sm text-rose-400">{inviteForm.errors.email}</p>}
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor="invite-role" className="text-xs uppercase tracking-wide text-zinc-400">
                                                Role
                                            </Label>
                                            <select
                                                id="invite-role"
                                                value={inviteForm.data.role}
                                                onChange={(event) => inviteForm.setData('role', event.target.value)}
                                                className="w-full rounded-md border border-zinc-700 bg-zinc-900/80 px-3 py-2 text-sm text-zinc-100 focus:border-indigo-500 focus:outline-none focus:ring-0"
                                            >
                                                {role_options.map((role) => (
                                                    <option key={role} value={role} disabled={role === 'owner' && !promoteToOwner}>
                                                        {roleLabels[role] ?? role}
                                                    </option>
                                                ))}
                                            </select>
                                            {inviteForm.errors.role && <p className="text-sm text-rose-400">{inviteForm.errors.role}</p>}
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-3">
                                        <Button type="submit" size="sm" disabled={inviteForm.processing}>
                                            Send invite
                                        </Button>
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant="ghost"
                                            disabled={inviteForm.processing}
                                            onClick={() => inviteForm.reset()}
                                        >
                                            Clear
                                        </Button>
                                    </div>
                                </form>
                            </div>
                        )}

                        <div className="space-y-3">
                            {group.members.map((member) => {
                                const isOwner = member.role === 'owner';
                                const canChangeRole = manageMembers && (!isOwner || promoteToOwner);
                                const canRemove = member.is_viewer || (manageMembers && (!isOwner || promoteToOwner));
                                const isChanging = roleUpdating === member.id;
                                const isRemoving = removing === member.id;

                                return (
                                    <div
                                        key={member.id}
                                        className="rounded-lg border border-zinc-800 bg-zinc-900/60 p-4"
                                    >
                                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                            <div>
                                                <div className="flex items-center gap-2">
                                                    <p className="font-medium text-zinc-100">{member.name}</p>
                                                    {member.is_viewer && (
                                                        <span className="rounded-full bg-indigo-500/10 px-2 py-0.5 text-xs font-semibold uppercase tracking-wide text-indigo-200">
                                                            You
                                                        </span>
                                                    )}
                                                </div>
                                                <p className="text-xs text-zinc-500">{member.email}</p>
                                            </div>
                                            <div className="flex flex-col gap-2 sm:items-end">
                                                {manageMembers ? (
                                                    <div className="flex flex-col gap-2 sm:flex-row sm:items-center">
                                                        <select
                                                            value={member.role}
                                                            onChange={(event) => handleRoleChange(member, event.target.value)}
                                                            disabled={!canChangeRole || isChanging}
                                                            className="rounded-md border border-zinc-700 bg-zinc-900/80 px-3 py-2 text-sm text-zinc-100 focus:border-indigo-500 focus:outline-none focus:ring-0"
                                                        >
                                                            {role_options.map((role) => (
                                                                <option key={role} value={role} disabled={role === 'owner' && !promoteToOwner}>
                                                                    {roleLabels[role] ?? role}
                                                                </option>
                                                            ))}
                                                        </select>
                                                        <Button
                                                            type="button"
                                                            variant={member.is_viewer ? 'outline' : 'ghost'}
                                                            size="sm"
                                                            disabled={!canRemove || isRemoving}
                                                            onClick={() => handleRemove(member)}
                                                        >
                                                            {member.is_viewer ? 'Leave party' : 'Remove'}
                                                        </Button>
                                                    </div>
                                                ) : (
                                                    <div className="flex flex-col gap-2 sm:items-end">
                                                        <span className="rounded-full bg-indigo-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-indigo-200">
                                                            {roleLabels[member.role] ?? member.role}
                                                        </span>
                                                        {member.is_viewer && (
                                                            <Button
                                                                type="button"
                                                                variant="outline"
                                                                size="sm"
                                                                disabled={isRemoving}
                                                                onClick={() => handleRemove(member)}
                                                            >
                                                                Leave party
                                                            </Button>
                                                        )}
                                                    </div>
                                                )}
                                                {(isChanging || isRemoving) && (
                                                    <span className="text-xs text-zinc-500">Working...</span>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                </section>

                <section className="rounded-xl border border-zinc-800 bg-zinc-950/60 p-6 shadow-inner shadow-black/40">
                    <header className="flex items-center justify-between">
                        <h2 className="text-lg font-semibold text-zinc-100">World regions</h2>
                        <span className="text-xs uppercase tracking-wide text-zinc-500">{totalRegions} tracked</span>
                    </header>

                    {group.worlds.length === 0 ? (
                        <p className="mt-4 rounded-lg border border-dashed border-zinc-800 bg-zinc-900/40 p-4 text-sm text-zinc-400">
                            No worlds yet. Forge one to begin mapping realms and assigning dungeon masters.
                        </p>
                    ) : (
                        <div className="mt-4 space-y-6">
                            {group.worlds.map((world) => (
                                <article key={world.id} className="space-y-4 rounded-lg border border-zinc-800 bg-zinc-900/60 p-5">
                                    <div className="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                        <div>
                                            <h3 className="text-base font-semibold text-zinc-100">{world.name}</h3>
                                            <p className="text-sm text-zinc-500">
                                                {world.summary ?? 'No summary yet. Chronicle this world for your party.'}
                                            </p>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <Button asChild variant="secondary" size="sm">
                                                <Link href={route('groups.worlds.edit', [group.id, world.id])}>Tend world</Link>
                                            </Button>
                                            <Button asChild size="sm">
                                                <Link
                                                    href={route('groups.regions.create', {
                                                        group: group.id,
                                                        world_id: world.id,
                                                    })}
                                                >
                                                    Add region
                                                </Link>
                                            </Button>
                                        </div>
                                    </div>

                                    {world.regions.length === 0 ? (
                                        <p className="rounded border border-dashed border-zinc-700/70 bg-zinc-950/40 p-4 text-sm text-zinc-500">
                                            No regions assigned. Bring a DM on board to steward the first frontier.
                                        </p>
                                    ) : (
                                        <div className="space-y-5">
                                            {world.regions.map((region) => (
                                                <article key={region.id} id={`region-${region.id}`} className="rounded-lg border border-zinc-800 bg-zinc-950/50 p-4">
                                                    <div className="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                                        <div>
                                                            <h4 className="text-sm font-semibold text-zinc-100">{region.name}</h4>
                                                            <p className="text-xs text-zinc-500">
                                                                {region.summary ?? 'No summary yet. Add a quick legend hook.'}
                                                            </p>
                                                            {region.dungeon_master ? (
                                                                <p className="mt-2 text-[11px] uppercase tracking-wide text-indigo-300">
                                                                    DM: {region.dungeon_master.name}
                                                                </p>
                                                            ) : (
                                                                <p className="mt-2 text-[11px] uppercase tracking-wide text-zinc-500">
                                                                    DM unassigned
                                                                </p>
                                                            )}
                                                            {region.ai_controlled && (
                                                                <p className="mt-1 text-[11px] uppercase tracking-wide text-emerald-300">
                                                                    AI steward active
                                                                </p>
                                                            )}
                                                        </div>
                                                        <div className="flex items-center gap-2">
                                                            <Button asChild variant="outline" size="sm" className="border-indigo-400/40 text-indigo-200">
                                                                <Link href={route('groups.regions.edit', [group.id, region.id])}>Adjust</Link>
                                                            </Button>
                                                            {region.can_process_turn ? (
                                                                <Button asChild size="sm">
                                                                    <Link href={route('groups.regions.turns.create', [group.id, region.id])}>
                                                                        Process turn
                                                                    </Link>
                                                                </Button>
                                                            ) : (
                                                                <span className="rounded border border-zinc-800/70 px-3 py-1 text-[11px] uppercase tracking-wide text-zinc-500">
                                                                    Awaiting cadence
                                                                </span>
                                                            )}
                                                        </div>
                                                    </div>

                                                    <dl className="mt-4 grid gap-3 text-xs text-zinc-400 sm:grid-cols-3">
                                                        <div>
                                                            <dt className="uppercase tracking-wide text-zinc-500">Cadence</dt>
                                                            <dd>
                                                                {region.turn_configuration
                                                                    ? `${region.turn_configuration.turn_duration_hours}h`
                                                                    : `${world.default_turn_duration_hours}h default`}
                                                            </dd>
                                                        </div>
                                                        <div>
                                                            <dt className="uppercase tracking-wide text-zinc-500">Next turn</dt>
                                                            <dd>
                                                                {region.turn_configuration?.next_turn_at
                                                                    ? new Date(region.turn_configuration.next_turn_at).toLocaleString()
                                                                    : 'Awaiting schedule'}
                                                            </dd>
                                                        </div>
                                                        <div>
                                                            <dt className="uppercase tracking-wide text-zinc-500">Status</dt>
                                                            <dd className={region.turn_configuration?.is_due ? 'text-amber-300' : 'text-emerald-300'}>
                                                                {region.turn_configuration?.is_due ? 'Turn ready' : 'On schedule'}
                                                            </dd>
                                                        </div>
                                                    </dl>

                                                    {region.can_delegate_to_ai && (
                                                        <form
                                                            onSubmit={(event) => handleAiDelegate(event, region)}
                                                            className="mt-4 flex flex-col gap-3 rounded-lg border border-indigo-500/20 bg-indigo-500/5 p-3 sm:flex-row sm:items-end"
                                                        >
                                                            <div className="flex-1 space-y-2">
                                                                <Label
                                                                    htmlFor={`ai-brief-${region.id}`}
                                                                    className="text-xs uppercase tracking-wide text-indigo-200"
                                                                >
                                                                    AI brief (optional)
                                                                </Label>
                                                                <Input
                                                                    id={`ai-brief-${region.id}`}
                                                                    value={aiFocus[region.id] ?? ''}
                                                                    onChange={(event) =>
                                                                        setAiFocus((current) => ({
                                                                            ...current,
                                                                            [region.id]: event.target.value,
                                                                        }))
                                                                    }
                                                                    placeholder="Key beats or safety tools to highlight"
                                                                />
                                                            </div>
                                                            <Button type="submit" size="sm" disabled={delegatingRegion === region.id}>
                                                                {delegatingRegion === region.id
                                                                    ? 'Summoning AI...'
                                                                    : region.ai_controlled
                                                                    ? 'Refresh AI plan'
                                                                    : 'Assign AI DM'}
                                                            </Button>
                                                        </form>
                                                    )}

                                                    {region.ai_delegate_summary && (
                                                        <div className="mt-4 rounded-lg border border-emerald-500/20 bg-emerald-500/5 p-3">
                                                            <p className="text-[11px] uppercase tracking-wide text-emerald-300">Latest AI directive</p>
                                                            <p className="mt-2 whitespace-pre-wrap text-sm text-emerald-200">{region.ai_delegate_summary}</p>
                                                        </div>
                                                    )}

                                                    {region.recent_turns.length > 0 && (
                                                        <div className="mt-4 border-t border-zinc-800 pt-4">
                                                            <h5 className="text-[11px] uppercase tracking-wide text-zinc-500">Recent turns</h5>
                                                            <ul className="mt-3 space-y-3 text-sm text-zinc-400">
                                                                {region.recent_turns.map((turn) => (
                                                                    <li key={turn.id} className="rounded border border-zinc-800/60 bg-zinc-950/50 p-3">
                                                                        <div className="flex items-center justify-between text-[11px] uppercase tracking-wide text-zinc-500">
                                                                            <span>Turn #{turn.number}</span>
                                                                            <span>
                                                                                {turn.processed_at
                                                                                    ? new Date(turn.processed_at).toLocaleString()
                                                                                    : 'Pending'}
                                                                            </span>
                                                                        </div>
                                                                        <p className="mt-2 text-sm text-zinc-300">{turn.summary ?? 'Awaiting scribe.'}</p>
                                                                        <p className="mt-2 text-xs text-zinc-500">
                                                                            {turn.processed_by
                                                                                ? `Processed by ${turn.processed_by.name}`
                                                                                : turn.used_ai_fallback
                                                                                ? 'AI assisted'
                                                                                : 'Manual processing'}
                                                                        </p>
                                                                    </li>
                                                                ))}
                                                            </ul>
                                                        </div>
                                                    )}
                                                </article>
                                            ))}
                                        </div>
                                    )}
                                </article>
                            ))}
                        </div>
                    )}
                </section>

                <section className="rounded-xl border border-zinc-800 bg-zinc-950/60 p-6 shadow-inner shadow-black/40 lg:col-span-2">
                    <header className="flex items-center justify-between">
                        <h2 className="text-lg font-semibold text-zinc-100">Campaign arcs</h2>
                        <span className="text-xs uppercase tracking-wide text-zinc-500">{group.campaigns.length} active</span>
                    </header>

                    <div className="mt-4 grid gap-4 md:grid-cols-2">
                        {group.campaigns.length === 0 ? (
                            <p className="rounded-lg border border-dashed border-zinc-800 bg-zinc-900/40 p-4 text-sm text-zinc-400">
                                No campaigns launched yet. Spin up a new saga to rally your adventurers.
                            </p>
                        ) : (
                            group.campaigns.map((campaign) => (
                                <article key={campaign.id} className="rounded-lg border border-zinc-800 bg-zinc-900/60 p-4">
                                    <div className="flex items-center justify-between">
                                        <div>
                                            <h3 className="text-base font-semibold text-zinc-100">{campaign.title}</h3>
                                            <p className="text-xs uppercase tracking-wide text-indigo-300">{campaign.status}</p>
                                        </div>
                                        <Button asChild variant="outline" size="sm" className="border-zinc-700 text-xs">
                                            <Link href={route('campaigns.show', campaign.id)}>View</Link>
                                        </Button>
                                    </div>
                                </article>
                            ))
                        )}
                    </div>
                </section>

                <section className="rounded-xl border border-zinc-800 bg-zinc-950/60 p-6 shadow-inner shadow-black/40 lg:col-span-2">
                    <header className="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h2 className="text-lg font-semibold text-zinc-100">Tile template library</h2>
                            <p className="text-sm text-zinc-500">
                                Reusable terrain tiles guide consistent maps and movement rules across your worlds.
                            </p>
                        </div>
                        <Button asChild size="sm">
                            <Link href={route('groups.tile-templates.create', group.id)}>Add template</Link>
                        </Button>
                    </header>

                    {group.tile_templates.length === 0 ? (
                        <p className="mt-4 rounded-lg border border-dashed border-zinc-800 bg-zinc-900/40 p-4 text-sm text-zinc-400">
                            No templates yet. Draft a few core terrains so dungeon masters can assemble boards quickly.
                        </p>
                    ) : (
                        <div className="mt-6 space-y-4">
                            {group.tile_templates.map((template) => (
                                <article key={template.id} className="flex flex-col gap-3 rounded-lg border border-zinc-800 bg-zinc-950/50 p-4 md:flex-row md:items-center md:justify-between">
                                    <div>
                                        <div className="flex items-center gap-2">
                                            <h3 className="text-base font-semibold text-zinc-100">{template.name}</h3>
                                            {template.key && (
                                                <span className="rounded-full bg-zinc-800 px-2 py-0.5 text-[11px] uppercase tracking-wide text-zinc-400">{template.key}</span>
                                            )}
                                        </div>
                                        <p className="mt-1 text-sm text-zinc-400">
                                            Terrain: {template.terrain_type} · Movement cost {template.movement_cost} · Defense +{template.defense_bonus}
                                        </p>
                                        <div className="mt-2 flex flex-wrap gap-3 text-xs text-zinc-500">
                                            {template.world ? <span>World: {template.world.name}</span> : <span>Shared across worlds</span>}
                                            {template.creator && <span>Crafted by {template.creator.name}</span>}
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-2 self-end md:self-auto">
                                        <Button asChild variant="secondary" size="sm">
                                            <Link href={route('groups.tile-templates.edit', [group.id, template.id])}>Edit</Link>
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            className="text-rose-300 hover:text-rose-200"
                                            disabled={removingTemplate === template.id}
                                            onClick={() => handleTemplateRemove(template)}
                                        >
                                            {removingTemplate === template.id ? 'Removing...' : 'Remove'}
                                        </Button>
                                    </div>
                                </article>
                            ))}
                        </div>
                    )}
                </section>

                <section className="rounded-xl border border-zinc-800 bg-zinc-950/60 p-6 shadow-inner shadow-black/40 lg:col-span-2">
                    <header className="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h2 className="text-lg font-semibold text-zinc-100">Region maps</h2>
                            <p className="text-sm text-zinc-500">
                                Track the boards that anchor your adventures and drop tiles with axial precision.
                            </p>
                        </div>
                        <Button asChild size="sm">
                            <Link href={route('groups.maps.create', group.id)}>Create map</Link>
                        </Button>
                    </header>

                    {group.maps.length === 0 ? (
                        <p className="mt-4 rounded-lg border border-dashed border-zinc-800 bg-zinc-900/40 p-4 text-sm text-zinc-400">
                            No maps charted yet. Craft one to start placing templates on the hex grid.
                        </p>
                    ) : (
                        <div className="mt-6 space-y-4">
                            {group.maps.map((map) => (
                                <article key={map.id} className="flex flex-col gap-3 rounded-lg border border-zinc-800 bg-zinc-950/50 p-4 md:flex-row md:items-center md:justify-between">
                                    <div>
                                        <h3 className="text-base font-semibold text-zinc-100">{map.title}</h3>
                                        <p className="mt-1 text-sm text-zinc-400">
                                            {map.base_layer === 'hex' ? 'Hex grid' : map.base_layer === 'square' ? 'Square grid' : 'Image backdrop'} · {map.orientation === 'pointy' ? 'Pointy-top' : 'Flat-top'}
                                        </p>
                                        <div className="mt-2 flex flex-wrap gap-3 text-xs text-zinc-500">
                                            <span>{map.tile_count} tiles</span>
                                            {map.region ? <span>Region: {map.region.name}</span> : <span>Unassigned region</span>}
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-2 self-end md:self-auto">
                                        <Button asChild variant="secondary" size="sm">
                                            <Link href={route('groups.maps.show', [group.id, map.id])}>Open</Link>
                                        </Button>
                                        <Button asChild variant="outline" size="sm" className="border-zinc-700 text-xs">
                                            <Link href={route('groups.maps.edit', [group.id, map.id])}>Settings</Link>
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            className="text-rose-300 hover:text-rose-200"
                                            disabled={removingMap === map.id}
                                            onClick={() => handleMapRemove(map)}
                                        >
                                            {removingMap === map.id ? 'Removing...' : 'Remove'}
                                        </Button>
                                    </div>
                                </article>
                            ))}
                        </div>
                    )}
                </section>
            </div>
        </AppLayout>
    );
}
