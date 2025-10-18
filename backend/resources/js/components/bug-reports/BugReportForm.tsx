import { useEffect, useMemo, useState } from 'react';
import { useForm } from '@inertiajs/react';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { InputError } from '@/components/InputError';

type BugReportFormProps = {
    action: string;
    method?: 'post' | 'put' | 'patch';
    context: {
        type: string;
        identifier?: string | null;
        path?: string | null;
        groupId?: number | null;
    };
    defaults?: {
        summary?: string | null;
        description?: string | null;
        tags?: string[] | null;
        priority?: 'low' | 'normal' | 'high' | 'critical';
    };
    showContactFields?: boolean;
};

type FormData = {
    summary: string;
    description: string;
    steps: string;
    expected: string;
    actual: string;
    priority: 'low' | 'normal' | 'high' | 'critical';
    tags: string[];
    context_type: string;
    context_identifier?: string | null;
    group_id?: number | null;
    context: {
        path?: string | null;
        browser?: string | null;
        logs?: string[];
        locale?: string | null;
    };
    submitted_name?: string;
    submitted_email?: string;
};

export function BugReportForm({ action, method = 'post', context, defaults, showContactFields }: BugReportFormProps) {
    const [tagInput, setTagInput] = useState(() => (defaults?.tags ?? []).join(', '));
    const [logsInput, setLogsInput] = useState('');

    const form = useForm<FormData>({
        summary: defaults?.summary ?? '',
        description: defaults?.description ?? '',
        steps: '',
        expected: '',
        actual: '',
        priority: defaults?.priority ?? 'normal',
        tags: defaults?.tags ?? [],
        context_type: context.type,
        context_identifier: context.identifier ?? null,
        group_id: context.groupId ?? null,
        context: {
            path: context.path ?? null,
            browser: null,
            logs: [],
            locale: null,
        },
        submitted_name: '',
        submitted_email: '',
    });

    useEffect(() => {
        if (typeof window === 'undefined') {
            return;
        }

        form.setData('context', {
            ...form.data.context,
            browser: window.navigator.userAgent,
            path: form.data.context.path ?? window.location.pathname,
        });
    }, []);

    const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        const preparedTags = tagInput
            .split(',')
            .map((value) => value.trim())
            .filter((value) => value !== '');

        const preparedLogs = logsInput
            .split('\n')
            .map((value) => value.trim())
            .filter((value) => value !== '');

        form.transform((data) => ({
            ...data,
            tags: preparedTags,
            context: {
                ...data.context,
                logs: preparedLogs,
            },
        }));

        const submit = method === 'post' ? form.post : form[method];

        submit(action, {
            preserveScroll: true,
            onFinish: () => {
                form.setTransform((data) => data);
            },
        });
    };

    const priorityOptions: { value: FormData['priority']; label: string }[] = useMemo(
        () => [
            { value: 'low', label: 'Low' },
            { value: 'normal', label: 'Normal' },
            { value: 'high', label: 'High' },
            { value: 'critical', label: 'Critical' },
        ],
        [],
    );

    return (
        <form onSubmit={handleSubmit} className="space-y-6">
            <div className="grid gap-4 md:grid-cols-2">
                <div className="space-y-2">
                    <Label htmlFor="summary">Summary</Label>
                    <Input
                        id="summary"
                        name="summary"
                        value={form.data.summary}
                        onChange={(event) => form.setData('summary', event.target.value)}
                        placeholder="Briefly describe the issue"
                        required
                    />
                    <InputError message={form.errors.summary} className="mt-2" />
                </div>
                <div className="space-y-2">
                    <Label htmlFor="priority">Priority</Label>
                    <select
                        id="priority"
                        name="priority"
                        value={form.data.priority}
                        onChange={(event) =>
                            form.setData('priority', event.target.value as FormData['priority'])
                        }
                        className="w-full rounded-lg border border-zinc-700 bg-zinc-900 px-3 py-2 text-sm text-zinc-100 focus:border-brand-400 focus:outline-none"
                    >
                        {priorityOptions.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </select>
                </div>
            </div>

            <div className="space-y-2">
                <Label htmlFor="description">Description</Label>
                <Textarea
                    id="description"
                    name="description"
                    value={form.data.description}
                    onChange={(event) => form.setData('description', event.target.value)}
                    placeholder="Share the observed behaviour"
                    rows={5}
                    required
                />
                <InputError message={form.errors.description} className="mt-2" />
            </div>

            <div className="grid gap-4 md:grid-cols-2">
                <div className="space-y-2">
                    <Label htmlFor="steps">Steps to reproduce</Label>
                    <Textarea
                        id="steps"
                        name="steps"
                        value={form.data.steps}
                        onChange={(event) => form.setData('steps', event.target.value)}
                        placeholder="Step-by-step instructions"
                        rows={4}
                    />
                    <InputError message={form.errors.steps} className="mt-2" />
                </div>
                <div className="space-y-2">
                    <Label htmlFor="expected">Expected vs actual</Label>
                    <Textarea
                        id="expected"
                        name="expected"
                        value={form.data.expected}
                        onChange={(event) => form.setData('expected', event.target.value)}
                        placeholder="What should have happened?"
                        rows={2}
                    />
                    <Textarea
                        id="actual"
                        name="actual"
                        value={form.data.actual}
                        onChange={(event) => form.setData('actual', event.target.value)}
                        placeholder="What actually happened?"
                        rows={2}
                        className="mt-3"
                    />
                </div>
            </div>

            <div className="space-y-2">
                <Label htmlFor="tags">Tags</Label>
                <Input
                    id="tags"
                    name="tags"
                    value={tagInput}
                    onChange={(event) => setTagInput(event.target.value)}
                    placeholder="comma,separated,tags"
                />
                <InputError message={form.errors.tags} className="mt-2" />
            </div>

            <div className="space-y-2">
                <Label htmlFor="logs">Console or error logs</Label>
                <Textarea
                    id="logs"
                    name="logs"
                    value={logsInput}
                    onChange={(event) => setLogsInput(event.target.value)}
                    placeholder="Paste one log line per row"
                    rows={4}
                />
            </div>

            {showContactFields && (
                <div className="grid gap-4 md:grid-cols-2">
                    <div className="space-y-2">
                        <Label htmlFor="submitted_name">Your name</Label>
                        <Input
                            id="submitted_name"
                            name="submitted_name"
                            value={form.data.submitted_name ?? ''}
                            onChange={(event) => form.setData('submitted_name', event.target.value)}
                            placeholder="Who should we credit?"
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="submitted_email">Contact email</Label>
                        <Input
                            id="submitted_email"
                            type="email"
                            name="submitted_email"
                            value={form.data.submitted_email ?? ''}
                            onChange={(event) => form.setData('submitted_email', event.target.value)}
                            placeholder="Optional email for follow-up"
                        />
                        <InputError message={form.errors.submitted_email} className="mt-2" />
                    </div>
                </div>
            )}

            <div className="flex items-center justify-between rounded-lg border border-zinc-800 bg-zinc-900/80 p-4 text-xs text-zinc-400">
                <div>
                    <p>Context path: {form.data.context.path ?? 'auto-detected'}</p>
                    <p className="mt-1">Browser: {form.data.context.browser ?? 'auto-detected'}</p>
                </div>
                <div className="flex items-center gap-3">
                    <Button type="submit" disabled={form.processing}>
                        {form.processing ? 'Sending…' : 'Submit bug report'}
                    </Button>
                </div>
            </div>
        </form>
    );
}
