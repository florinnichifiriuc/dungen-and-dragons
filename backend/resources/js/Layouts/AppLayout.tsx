import { PropsWithChildren, useEffect, useMemo, useState } from 'react';

import { Link, usePage } from '@inertiajs/react';

import { Button } from '@/components/ui/button';
import { useTranslations } from '@/hooks/useTranslations';

type PreferenceBag = {
    locale: string;
    timezone: string;
    theme: 'system' | 'dark' | 'light';
    high_contrast: boolean;
    font_scale: number;
};

export default function AppLayout({ children }: PropsWithChildren) {
    const { t, locale } = useTranslations();
    const { props } = usePage<{
        csrf_token?: string;
        flash?: { success?: string; error?: string };
        auth?: { user?: { name: string } & PreferenceBag };
        preferences?: PreferenceBag;
    }>();

    const flash = props.flash;
    const user = props.auth?.user;
    const preferences: PreferenceBag = {
        locale: props.preferences?.locale ?? locale ?? 'en',
        timezone: props.preferences?.timezone ?? 'UTC',
        theme: (props.preferences?.theme as PreferenceBag['theme']) ?? 'system',
        high_contrast: props.preferences?.high_contrast ?? false,
        font_scale: props.preferences?.font_scale ?? 100,
    };

    const [isDarkMode, setIsDarkMode] = useState(true);

    useEffect(() => {
        if (typeof document === 'undefined') {
            return;
        }

        document.documentElement.lang = locale ?? 'en';
    }, [locale]);

    useEffect(() => {
        if (typeof window === 'undefined') {
            return undefined;
        }

        const media = window.matchMedia('(prefers-color-scheme: dark)');

        const applyTheme = () => {
            const shouldUseDark =
                preferences.theme === 'dark' || (preferences.theme === 'system' && media.matches);

            setIsDarkMode(shouldUseDark);
            document.documentElement.classList.toggle('dark', shouldUseDark);
            document.documentElement.dataset.colorScheme = shouldUseDark ? 'dark' : 'light';
        };

        applyTheme();

        if (preferences.theme === 'system') {
            media.addEventListener('change', applyTheme);
            return () => media.removeEventListener('change', applyTheme);
        }

        return undefined;
    }, [preferences.theme]);

    useEffect(() => {
        if (typeof document === 'undefined') {
            return;
        }

        document.documentElement.classList.toggle('high-contrast', Boolean(preferences.high_contrast));
    }, [preferences.high_contrast]);

    useEffect(() => {
        if (typeof document === 'undefined') {
            return;
        }

        document.documentElement.style.fontSize = `${preferences.font_scale}%`;
    }, [preferences.font_scale]);

    const containerClass = useMemo(() => {
        const palette = isDarkMode
            ? 'bg-zinc-950 text-zinc-100'
            : 'bg-amber-50 text-slate-900';

        return `min-h-screen transition-colors duration-200 ${palette}`;
    }, [isDarkMode]);

    const headerClass = useMemo(() => {
        return isDarkMode
            ? 'border-zinc-800 bg-zinc-900/80'
            : 'border-amber-200 bg-white/80 backdrop-blur';
    }, [isDarkMode]);

    const navLinkClass = useMemo(() => {
        const base = 'hidden text-sm sm:inline-flex transition focus-visible:outline-none';
        const color = isDarkMode
            ? 'text-zinc-400 hover:text-amber-300'
            : 'text-slate-600 hover:text-amber-600';

        return `${base} ${color}`;
    }, [isDarkMode]);

    const buttonClass = useMemo(() => {
        return isDarkMode ? 'text-zinc-300 hover:text-amber-300' : 'text-slate-600 hover:text-amber-600';
    }, [isDarkMode]);

    const flashSuccessClass = useMemo(() => {
        return isDarkMode
            ? 'border-emerald-500/40 bg-emerald-500/10 text-emerald-200'
            : 'border-emerald-500/40 bg-emerald-500/10 text-emerald-700';
    }, [isDarkMode]);

    const flashErrorClass = useMemo(() => {
        return isDarkMode
            ? 'border-rose-500/40 bg-rose-500/10 text-rose-200'
            : 'border-rose-500/40 bg-rose-500/10 text-rose-700';
    }, [isDarkMode]);

    return (
        <div className={containerClass}>
            <a
                href="#main-content"
                className="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-amber-500 focus:px-4 focus:py-2 focus:text-zinc-950"
            >
                {t('a11y.skip_to_content')}
            </a>
            <header className={`border-b backdrop-blur ${headerClass}`}>
                <div className="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
                    <div className="flex items-center gap-6">
                        <Link
                            href={route('dashboard')}
                            className="text-lg font-semibold tracking-wide focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-400"
                        >
                            {t('app_name')}
                        </Link>
                        {user && (
                            <Link
                                href={route('search.index')}
                                className={`${navLinkClass} focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-400`}
                            >
                                {t('navigation.search')}
                            </Link>
                        )}
                        {user && (
                            <Link
                                href={route('settings.preferences.edit')}
                                className={`${navLinkClass} focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-400`}
                            >
                                {t('navigation.preferences')}
                            </Link>
                        )}
                    </div>
                    <div className="flex items-center gap-3 text-sm">
                        {user && (
                            <span className={isDarkMode ? 'text-zinc-400' : 'text-slate-600'} aria-live="polite">
                                {user.name}
                            </span>
                        )}
                        <form method="post" action={route('logout')}>
                            <input
                                type="hidden"
                                name="_token"
                                value={typeof props.csrf_token === 'string' ? props.csrf_token : ''}
                            />
                            <Button
                                variant="ghost"
                                size="sm"
                                className={`${buttonClass} focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-400`}
                                type="submit"
                                aria-label={t('navigation.logout')}
                            >
                                {t('navigation.logout')}
                            </Button>
                        </form>
                    </div>
                </div>
            </header>
            <main id="main-content" className="mx-auto max-w-7xl space-y-6 px-6 py-10">
                {flash?.success && (
                    <div className={`rounded-lg border px-4 py-3 text-sm ${flashSuccessClass}`} role="status">
                        {flash.success}
                    </div>
                )}
                {flash?.error && (
                    <div className={`rounded-lg border px-4 py-3 text-sm ${flashErrorClass}`} role="alert">
                        {flash.error}
                    </div>
                )}
                <div>{children}</div>
            </main>
        </div>
    );
}
