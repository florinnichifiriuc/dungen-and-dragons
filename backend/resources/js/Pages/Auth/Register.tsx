import { FormEventHandler } from 'react';

import { Head, Link, useForm } from '@inertiajs/react';

import GuestLayout from '@/Layouts/GuestLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { InputError } from '@/components/InputError';

export default function Register() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        post(route('register.store'));
    };

    return (
        <GuestLayout>
            <Head title="Create account" />

            <div className="space-y-6">
                <header className="space-y-2 text-center">
                    <h1 className="text-2xl font-semibold">Create your nexus profile</h1>
                    <p className="text-sm text-zinc-400">Launch cooperative campaigns, coordinate DMs, and sync regional turns.</p>
                </header>

                <form onSubmit={submit} className="space-y-5">
                    <div className="space-y-2">
                        <Label htmlFor="name">Display name</Label>
                        <Input
                            id="name"
                            name="name"
                            value={data.name}
                            onChange={(event) => setData('name', event.target.value)}
                            required
                            autoFocus
                            autoComplete="name"
                        />
                        <InputError>{errors.name}</InputError>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="email">Email</Label>
                        <Input
                            id="email"
                            type="email"
                            name="email"
                            value={data.email}
                            onChange={(event) => setData('email', event.target.value)}
                            required
                            autoComplete="email"
                        />
                        <InputError>{errors.email}</InputError>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="password">Password</Label>
                        <Input
                            id="password"
                            type="password"
                            name="password"
                            value={data.password}
                            onChange={(event) => setData('password', event.target.value)}
                            required
                            autoComplete="new-password"
                        />
                        <InputError>{errors.password}</InputError>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="password_confirmation">Confirm password</Label>
                        <Input
                            id="password_confirmation"
                            type="password"
                            name="password_confirmation"
                            value={data.password_confirmation}
                            onChange={(event) => setData('password_confirmation', event.target.value)}
                            required
                            autoComplete="new-password"
                        />
                        <InputError>{errors.password_confirmation}</InputError>
                    </div>

                    <Button type="submit" className="w-full" disabled={processing}>
                        {processing ? 'Creating account…' : 'Create account'}
                    </Button>
                </form>

                <p className="text-center text-sm text-zinc-400">
                    Already registered?{' '}
                    <Link href={route('login')} className="font-medium text-amber-300 hover:text-amber-200">
                        Sign in here
                    </Link>
                    .
                </p>
            </div>
        </GuestLayout>
    );
}
