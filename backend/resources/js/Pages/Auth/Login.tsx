import { FormEventHandler, useEffect } from 'react';

import { Head, Link, useForm } from '@inertiajs/react';

import GuestLayout from '@/Layouts/GuestLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { InputError } from '@/components/InputError';

interface LoginProps {
    canRegister: boolean;
    status?: string;
}

export default function Login({ canRegister, status }: LoginProps) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    useEffect(() => {
        return () => {
            reset('password');
        };
    }, [reset]);

    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        post(route('login.store'));
    };

    return (
        <GuestLayout>
            <Head title="Sign in" />

            <div className="space-y-6">
                <header className="space-y-2 text-center">
                    <h1 className="text-2xl font-semibold">Welcome back</h1>
                    <p className="text-sm text-zinc-400">Sign in to resume your campaign turns and group coordination.</p>
                </header>

                {status && <div className="rounded-lg border border-emerald-500/40 bg-emerald-500/10 px-4 py-2 text-sm text-emerald-200">{status}</div>}

                <form onSubmit={submit} className="space-y-5">
                    <div className="space-y-2">
                        <Label htmlFor="email">Email</Label>
                        <Input
                            id="email"
                            type="email"
                            autoComplete="email"
                            value={data.email}
                            onChange={(event) => setData('email', event.target.value)}
                            required
                        />
                        <InputError>{errors.email}</InputError>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="password">Password</Label>
                        <Input
                            id="password"
                            type="password"
                            autoComplete="current-password"
                            value={data.password}
                            onChange={(event) => setData('password', event.target.value)}
                            required
                        />
                        <InputError>{errors.password}</InputError>
                    </div>

                    <label className="flex items-center gap-2 text-sm text-zinc-400">
                        <input
                            type="checkbox"
                            name="remember"
                            checked={data.remember}
                            onChange={(event) => setData('remember', event.target.checked)}
                            className="h-4 w-4 rounded border-zinc-700 bg-zinc-900 text-amber-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500"
                        />
                        Remember me on this device
                    </label>

                    <Button type="submit" className="w-full" disabled={processing}>
                        {processing ? 'Signing in…' : 'Sign in'}
                    </Button>
                </form>

                {canRegister && (
                    <p className="text-center text-sm text-zinc-400">
                        Need an account?{' '}
                        <Link href={route('register')} className="font-medium text-amber-300 hover:text-amber-200">
                            Create one now
                        </Link>
                        .
                    </p>
                )}
            </div>
        </GuestLayout>
    );
}
