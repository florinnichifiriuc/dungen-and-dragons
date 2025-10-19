import { FormEventHandler, useMemo } from 'react';

import { Head, Link, router, useForm, usePage } from '@inertiajs/react';

import AppLayout from '@/Layouts/AppLayout';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { InputError } from '@/components/InputError';
import AiIdeaPanel, { AiIdeaResult } from '@/components/ai/AiIdeaPanel';

type ColumnMeta = {
    key: string;
    label: string;
    description: string;
    accent: string;
};

type TaskAssignee = {
    id: number;
    name: string;
    email?: string;
};

type TaskPayload = {
    id: number;
    title: string;
    description: string | null;
    status: string;
    position: number;
    due_turn_number: number | null;
    due_in: number | null;
    due_at: string | null;
    completed_at: string | null;
    assigned_user: TaskAssignee | null;
    assigned_group: { id: number; name: string } | null;
    can_update: boolean;
};

type CampaignSummary = {
    id: number;
    title: string;
    status: string;
    group: { id: number; name: string };
    current_turn: number;
};

type TurnOption = {
    value: number;
    label: string;
};

type MemberOption = {
    id: number;
    name: string;
    email: string;
    role: string;
};

type StatusOption = {
    key: string;
    label: string;
};

type StructuredAiTask = {
    title?: string;
    description?: string;
    status?: string;
};

const isStructuredTask = (value: unknown): value is StructuredAiTask => {
    if (typeof value !== 'object' || value === null) {
        return false;
    }

    return 'title' in value || 'description' in value || 'status' in value;
};

type PageProps = {
    campaign: CampaignSummary;
    columns: ColumnMeta[];
    tasks: Record<string, TaskPayload[]>;
    turn_suggestions: TurnOption[];
    members: MemberOption[];
    can_manage: boolean;
    statuses: StatusOption[];
};

export default function TaskBoard() {
    const { campaign, columns, tasks, turn_suggestions, members, can_manage, statuses } = usePage<PageProps>().props;

    const createForm = useForm({
        title: '',
        description: '',
        status: statuses[0]?.key ?? 'backlog',
        due_turn_number: '',
        assigned_user_id: '',
    });

    const submitCreate: FormEventHandler = (event) => {
        event.preventDefault();
        createForm.post(route('campaigns.tasks.store', campaign.id), {
            preserveScroll: true,
            onSuccess: () => {
                createForm.reset('title', 'description', 'due_turn_number', 'assigned_user_id');
            },
        });
    };

    const handleStatusChange = (task: TaskPayload, nextStatus: string) => {
        if (nextStatus === task.status) {
            return;
        }

        router.patch(route('campaigns.tasks.update', { campaign: campaign.id, task: task.id }), {
            status: nextStatus,
        }, { preserveScroll: true });
    };

    const handleDueTurnChange = (task: TaskPayload, value: string) => {
        router.patch(route('campaigns.tasks.update', { campaign: campaign.id, task: task.id }), {
            due_turn_number: value === '' ? null : Number(value),
        }, { preserveScroll: true });
    };

    const handleAssignChange = (task: TaskPayload, value: string) => {
        router.patch(route('campaigns.tasks.update', { campaign: campaign.id, task: task.id }), {
            assigned_user_id: value === '' ? null : Number(value),
        }, { preserveScroll: true });
    };

    const handleCompleteToggle = (task: TaskPayload) => {
        const nextStatus = task.status === 'completed' ? 'ready' : 'completed';

        router.patch(route('campaigns.tasks.update', { campaign: campaign.id, task: task.id }), {
            status: nextStatus,
        }, { preserveScroll: true });
    };

    const reorderWithinColumn = (column: ColumnMeta, taskId: number, direction: 'up' | 'down') => {
        const columnTasks = tasks[column.key] ?? [];
        const index = columnTasks.findIndex((item) => item.id === taskId);

        if (index === -1) {
            return;
        }

        const targetIndex = direction === 'up' ? Math.max(0, index - 1) : Math.min(columnTasks.length - 1, index + 1);

        if (targetIndex === index) {
            return;
        }

        const order = columnTasks.map((item) => item.id);
        const [removed] = order.splice(index, 1);
        order.splice(targetIndex, 0, removed);

        router.post(route('campaigns.tasks.reorder', campaign.id), {
            status: column.key,
            order,
        }, { preserveScroll: true });
    };

    const existingTaskContext = useMemo(
        () =>
            columns.map((column) => ({
                status: column.label,
                titles: (tasks[column.key] ?? []).slice(0, 3).map((task) => task.title),
            })),
        [columns, tasks]
    );

    const findStatusKey = (value: string | undefined): string | null => {
        if (!value) {
            return null;
        }

        const lower = value.toLowerCase();
        const byKey = statuses.find((status) => status.key.toLowerCase() === lower);
        if (byKey) {
            return byKey.key;
        }

        const byLabel = statuses.find((status) => status.label.toLowerCase() === lower);
        return byLabel ? byLabel.key : null;
    };

    return (
        <AppLayout>
            <Head title={`Task Board · ${campaign.title}`} />

            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 className="text-3xl font-semibold text-zinc-100">{campaign.title} · Task Board</h1>
                    <p className="text-sm text-zinc-400">Track every quest thread by turn cadence and Kanban status.</p>
                </div>
                <div className="flex gap-2">
                    <Button asChild variant="outline" className="border-zinc-700 text-sm">
                        <Link href={route('campaigns.show', campaign.id)}>Campaign overview</Link>
                    </Button>
                    <Button asChild variant="outline" className="border-indigo-600/60 text-sm text-indigo-200 hover:bg-indigo-500/10">
                        <Link href={route('campaigns.sessions.index', { campaign: campaign.id })}>Session workspace</Link>
                    </Button>
                </div>
            </div>

            {can_manage && (
                <div className="mt-8 grid gap-6 lg:grid-cols-[2fr_1fr]">
                    <section className="rounded-xl border border-zinc-800 bg-zinc-950/80 p-6 shadow-inner shadow-black/30">
                        <header className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 className="text-lg font-semibold text-zinc-100">Add a new task</h2>
                                <p className="text-sm text-zinc-500">Anchor work to the upcoming turns so the party keeps pace.</p>
                            </div>
                            <span className="text-xs uppercase tracking-wide text-zinc-500">Current turn {campaign.current_turn ?? 0}</span>
                        </header>

                        <form onSubmit={submitCreate} className="mt-6 grid gap-4 md:grid-cols-2">
                            <div className="md:col-span-2">
                                <Label htmlFor="title">Title</Label>
                                <Input
                                    id="title"
                                    value={createForm.data.title}
                                    onChange={(event) => createForm.setData('title', event.target.value)}
                                    placeholder="Secure supply lines through the Bramble Pass"
                                    required
                                />
                                <InputError message={createForm.errors.title} className="mt-2" />
                            </div>

                            <div className="md:col-span-2">
                                <Label htmlFor="description">Description</Label>
                                <Textarea
                                    id="description"
                                    value={createForm.data.description}
                                    onChange={(event) => createForm.setData('description', event.target.value)}
                                    placeholder="Outline what success looks like for the party or support crew."
                                    rows={3}
                                />
                                <InputError message={createForm.errors.description} className="mt-2" />
                            </div>

                            <div>
                                <Label htmlFor="status">Status</Label>
                                <select
                                    id="status"
                                    className="mt-1 w-full rounded-md border border-zinc-700 bg-zinc-950/60 px-3 py-2 text-sm text-zinc-100 shadow-inner focus:border-indigo-500 focus:outline-none"
                                    value={createForm.data.status}
                                    onChange={(event) => createForm.setData('status', event.target.value)}
                                >
                                    {statuses.map((status) => (
                                        <option key={status.key} value={status.key}>
                                            {status.label}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={createForm.errors.status} className="mt-2" />
                            </div>

                            <div>
                                <Label htmlFor="due_turn_number">Due turn</Label>
                                <select
                                    id="due_turn_number"
                                    className="mt-1 w-full rounded-md border border-zinc-700 bg-zinc-950/60 px-3 py-2 text-sm text-zinc-100 shadow-inner focus:border-indigo-500 focus:outline-none"
                                    value={createForm.data.due_turn_number}
                                    onChange={(event) => createForm.setData('due_turn_number', event.target.value)}
                                >
                                    <option value="">Unassigned</option>
                                    {turn_suggestions.map((turn) => (
                                        <option key={turn.value} value={turn.value}>
                                            {turn.label}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={createForm.errors.due_turn_number} className="mt-2" />
                            </div>

                            <div>
                                <Label htmlFor="assigned_user_id">Assigned to</Label>
                                <select
                                    id="assigned_user_id"
                                    className="mt-1 w-full rounded-md border border-zinc-700 bg-zinc-950/60 px-3 py-2 text-sm text-zinc-100 shadow-inner focus:border-indigo-500 focus:outline-none"
                                    value={createForm.data.assigned_user_id}
                                    onChange={(event) => createForm.setData('assigned_user_id', event.target.value)}
                                >
                                    <option value="">Unassigned</option>
                                    {members.map((member) => (
                                        <option key={member.id} value={member.id}>
                                            {member.name} – {member.role}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={createForm.errors.assigned_user_id} className="mt-2" />
                            </div>

                            <div className="md:col-span-2 flex justify-end">
                                <Button type="submit" disabled={createForm.processing}>
                                    Add task
                                </Button>
                            </div>
                        </form>
                    </section>

                    <AiIdeaPanel
                        domain="campaign_tasks"
                        title="Ask the strategist"
                        description="Let the AI storyboard a backlog, align statuses, and prep art prompts for tokens."
                        placeholder="Secure the Bramble Pass, negotiate with the Wardens..."
                        context={{
                            campaign_title: campaign.title,
                            current_turn: campaign.current_turn,
                            statuses: statuses.map((status) => status.label),
                            existing_tasks: existingTaskContext,
                        }}
                        actions={[
                            {
                                label: 'Fill new task form',
                                onApply: (result: AiIdeaResult) => {
                                    const structuredTasks = Array.isArray(result.structured?.tasks)
                                        ? (result.structured?.tasks as unknown[])
                                        : [];
                                    const first = structuredTasks.find((entry) => isStructuredTask(entry));

                                    if (first && typeof first.title === 'string') {
                                        createForm.setData('title', first.title);
                                    }

                                    if (first && typeof first.description === 'string') {
                                        createForm.setData('description', first.description);
                                    } else if (typeof result.structured?.overview === 'string') {
                                        createForm.setData('description', result.structured.overview);
                                    }

                                    if (first && typeof first.status === 'string') {
                                        const statusKey = findStatusKey(first.status);
                                        if (statusKey) {
                                            createForm.setData('status', statusKey);
                                        }
                                    }
                                },
                            },
                        ]}
                    />
                </div>
            )}

            <section className="mt-10 grid gap-6 lg:grid-cols-5">
                {columns.map((column) => {
                    const columnTasks = tasks[column.key] ?? [];

                    return (
                        <article
                            key={column.key}
                            className={`flex h-full flex-col rounded-xl border ${column.accent} bg-zinc-950/70 p-4 shadow-inner shadow-black/30`}
                        >
                            <header className="pb-3">
                                <h2 className="text-lg font-semibold text-zinc-100">{column.label}</h2>
                                <p className="text-xs text-zinc-500">{column.description}</p>
                                <p className="mt-2 text-xs uppercase tracking-wide text-zinc-500">{columnTasks.length} tasks</p>
                            </header>

                            <div className="flex-1 space-y-4 overflow-y-auto pr-2">
                                {columnTasks.length === 0 && (
                                    <p className="rounded-lg border border-dashed border-zinc-800 bg-zinc-900/50 p-4 text-sm text-zinc-500">
                                        No tasks here yet. Drag quests into this column as priorities shift.
                                    </p>
                                )}

                                {columnTasks.map((task, index) => (
                                    <div key={task.id} className="rounded-lg border border-zinc-800 bg-zinc-900/60 p-4 shadow-sm shadow-black/20">
                                        <div className="flex items-start justify-between gap-3">
                                            <div>
                                                <h3 className="text-base font-semibold text-zinc-100">{task.title}</h3>
                                                {task.description && (
                                                    <p className="mt-2 whitespace-pre-wrap text-sm text-zinc-400">{task.description}</p>
                                                )}
                                            </div>
                                            {task.can_update && (
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    className={`border-zinc-700 text-xs ${task.status === 'completed' ? 'text-emerald-300 hover:bg-emerald-500/10' : 'text-zinc-300 hover:bg-zinc-800/60'}`}
                                                    onClick={() => handleCompleteToggle(task)}
                                                >
                                                    {task.status === 'completed' ? 'Reopen' : 'Mark done'}
                                                </Button>
                                            )}
                                        </div>

                                        <dl className="mt-4 grid gap-3 text-xs text-zinc-400">
                                            <div className="flex items-center justify-between gap-2">
                                                <dt className="uppercase tracking-wide text-zinc-500">Due turn</dt>
                                                <dd>
                                                    {task.can_update ? (
                                                        <select
                                                            className="rounded-md border border-zinc-700 bg-zinc-950/70 px-2 py-1 text-xs text-zinc-200 focus:border-indigo-500 focus:outline-none"
                                                            value={task.due_turn_number ?? ''}
                                                            onChange={(event) => handleDueTurnChange(task, event.target.value)}
                                                        >
                                                            <option value="">Unassigned</option>
                                                            {turn_suggestions.map((turn) => (
                                                                <option key={turn.value} value={turn.value}>
                                                                    {turn.label}
                                                                </option>
                                                            ))}
                                                        </select>
                                                    ) : task.due_turn_number ? (
                                                        <span>Turn {task.due_turn_number}</span>
                                                    ) : (
                                                        <span className="text-zinc-500">Unassigned</span>
                                                    )}

                                                    {task.due_in !== null && (
                                                        <span className="ml-2 rounded-full bg-indigo-500/10 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-indigo-300">
                                                            {task.due_in === 0 ? 'Due this turn' : `Due in ${task.due_in} turn${task.due_in === 1 ? '' : 's'}`}
                                                        </span>
                                                    )}
                                                </dd>
                                            </div>

                                            <div className="flex items-center justify-between gap-2">
                                                <dt className="uppercase tracking-wide text-zinc-500">Assigned</dt>
                                                <dd>
                                                    {task.can_update ? (
                                                        <select
                                                            className="rounded-md border border-zinc-700 bg-zinc-950/70 px-2 py-1 text-xs text-zinc-200 focus:border-indigo-500 focus:outline-none"
                                                            value={task.assigned_user?.id ?? ''}
                                                            onChange={(event) => handleAssignChange(task, event.target.value)}
                                                        >
                                                            <option value="">Unassigned</option>
                                                            {members.map((member) => (
                                                                <option key={member.id} value={member.id}>
                                                                    {member.name}
                                                                </option>
                                                            ))}
                                                        </select>
                                                    ) : task.assigned_user ? (
                                                        <span>{task.assigned_user.name}</span>
                                                    ) : (
                                                        <span className="text-zinc-500">Unassigned</span>
                                                    )}
                                                </dd>
                                            </div>

                                            <div className="flex items-center justify-between gap-2">
                                                <dt className="uppercase tracking-wide text-zinc-500">Status</dt>
                                                <dd>
                                                    {task.can_update ? (
                                                        <select
                                                            className="rounded-md border border-zinc-700 bg-zinc-950/70 px-2 py-1 text-xs text-zinc-200 focus:border-indigo-500 focus:outline-none"
                                                            value={task.status}
                                                            onChange={(event) => handleStatusChange(task, event.target.value)}
                                                        >
                                                            {statuses.map((status) => (
                                                                <option key={status.key} value={status.key}>
                                                                    {status.label}
                                                                </option>
                                                            ))}
                                                        </select>
                                                    ) : (
                                                        <span className="text-zinc-300">{statuses.find((status) => status.key === task.status)?.label ?? task.status}</span>
                                                    )}
                                                </dd>
                                            </div>
                                        </dl>

                                        {task.can_update && columnTasks.length > 1 && (
                                            <div className="mt-4 flex justify-end gap-2">
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    className="border-zinc-700 text-xs text-zinc-300 hover:bg-zinc-800/60"
                                                    onClick={() => reorderWithinColumn(column, task.id, 'up')}
                                                    disabled={index === 0}
                                                >
                                                    Move up
                                                </Button>
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    className="border-zinc-700 text-xs text-zinc-300 hover:bg-zinc-800/60"
                                                    onClick={() => reorderWithinColumn(column, task.id, 'down')}
                                                    disabled={index === columnTasks.length - 1}
                                                >
                                                    Move down
                                                </Button>
                                            </div>
                                        )}
                                    </div>
                                ))}
                            </div>
                        </article>
                    );
                })}
            </section>
        </AppLayout>
    );
}
