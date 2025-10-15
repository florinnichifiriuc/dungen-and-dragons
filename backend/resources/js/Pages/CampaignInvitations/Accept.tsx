import { FormEventHandler } from 'react';

import { Head, Link, useForm } from '@inertiajs/react';

import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';

const roleLabels: Record<string, string> = {
    gm: 'Game Master',
    player: 'Adventurer',
    observer: 'Observer',
};

type InvitationAcceptProps = {
    campaign: {
        id: number;
        title: string;
        group: { id: number; name: string };
    };
    invitation: {
        role: string;
        email: string | null;
        group: { id: number; name: string } | null;
        expires_at: string | null;
        accept_route: string;
    };
};

export default function CampaignInvitationAccept({ campaign, invitation }: InvitationAcceptProps) {
    const form = useForm({});

    const acceptInvitation: FormEventHandler = (event) => {
        event.preventDefault();
        form.post(invitation.accept_route);
    };

    const targetLabel = invitation.group ? invitation.group.name : invitation.email;
    const expiresLabel = invitation.expires_at ? new Date(invitation.expires_at).toLocaleString() : 'No expiry';

    return (
        <AppLayout>
            <Head title={`Join ${campaign.title}`} />

            <div className="mx-auto flex max-w-3xl flex-col gap-6">
                <header className="rounded-xl border border-emerald-700/50 bg-emerald-900/20 p-6 text-emerald-100 shadow-lg shadow-emerald-900/40">
                    <p className="text-sm uppercase tracking-wide text-emerald-300">Campaign invitation</p>
                    <h1 className="mt-2 text-3xl font-semibold">{campaign.title}</h1>
                    <p className="mt-2 text-sm text-emerald-200/80">
                        You have been invited to join <span className="font-semibold text-emerald-100">{campaign.group.name}</span>'s campaign.
                    </p>
                </header>

                <article className="rounded-xl border border-zinc-800 bg-zinc-950/70 p-6 shadow-inner shadow-black/40">
                    <dl className="grid gap-4 text-sm text-zinc-300 sm:grid-cols-2">
                        <div>
                            <dt className="text-xs uppercase tracking-wide text-zinc-500">Invitation for</dt>
                            <dd className="font-medium text-zinc-100">{targetLabel}</dd>
                        </div>
                        <div>
                            <dt className="text-xs uppercase tracking-wide text-zinc-500">Role</dt>
                            <dd className="font-medium text-amber-300">{roleLabels[invitation.role] ?? invitation.role}</dd>
                        </div>
                        <div>
                            <dt className="text-xs uppercase tracking-wide text-zinc-500">Expires</dt>
                            <dd>{expiresLabel}</dd>
                        </div>
                        <div>
                            <dt className="text-xs uppercase tracking-wide text-zinc-500">Campaign link</dt>
                            <dd>
                                <Link href={route('campaigns.show', campaign.id)} className="text-sky-300 hover:text-sky-200">
                                    View campaign overview
                                </Link>
                            </dd>
                        </div>
                    </dl>

                    <form onSubmit={acceptInvitation} className="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <p className="text-xs text-zinc-500">
                            Accepting will grant you the requested role and, if needed, add you to the campaign's group roster.
                        </p>
                        <Button type="submit" disabled={form.processing} className="w-full sm:w-auto">
                            Accept invitation
                        </Button>
                    </form>
                </article>
            </div>
        </AppLayout>
    );
}
