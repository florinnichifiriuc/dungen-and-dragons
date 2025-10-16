import { FormEvent } from 'react';

import { Head, useForm, usePage } from '@inertiajs/react';

import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { InputError } from '@/components/InputError';
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/hooks/useTranslations';

type PreferenceForm = {
    locale: string;
    timezone: string;
    theme: string;
    high_contrast: boolean;
    font_scale: number;
    notification_channel_in_app: boolean;
    notification_channel_push: boolean;
    notification_channel_email: boolean;
    notification_quiet_hours_start: string | null;
    notification_quiet_hours_end: string | null;
    notification_digest_delivery: string;
    notification_digest_channel_in_app: boolean;
    notification_digest_channel_email: boolean;
    notification_digest_channel_push: boolean;
};

type PreferenceOptions = {
    locales: string[];
    themes: string[];
    font_scales: number[];
    timezones: string[];
    notification_digests: string[];
};

type PreferencesPageProps = {
    form: PreferenceForm;
    options: PreferenceOptions;
};

export default function Preferences() {
    const { t } = useTranslations();
    const { form: defaults, options } = usePage<PreferencesPageProps>().props;
    const { data, setData, put, processing, errors } = useForm<PreferenceForm>(defaults);

    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        put(route('settings.preferences.update'));
    };

    return (
        <AppLayout>
            <Head title={t('preferences.title')} />
            <section className="space-y-6">
                <header className="space-y-2">
                    <h1 className="text-3xl font-semibold">{t('preferences.title')}</h1>
                    <p className="max-w-3xl text-sm text-zinc-500 dark:text-zinc-400">
                        {t('preferences.description')}
                    </p>
                </header>
                <form onSubmit={handleSubmit} className="space-y-8" noValidate>
                    <div className="grid gap-6 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="locale">{t('preferences.locale_label')}</Label>
                            <select
                                id="locale"
                                name="locale"
                                value={data.locale}
                                onChange={(event) => setData('locale', event.target.value)}
                                className="w-full rounded-md border border-zinc-400/40 bg-white/80 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-300 dark:border-zinc-700/60 dark:bg-zinc-900/80 dark:text-zinc-100"
                            >
                                {options.locales.map((value) => (
                                    <option key={value} value={value} className="bg-inherit text-inherit">
                                        {t(`preferences.locale_options.${value}`)}
                                    </option>
                                ))}
                            </select>
                            <InputError message={errors.locale} className="text-sm" />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="timezone">{t('preferences.timezone_label')}</Label>
                            <select
                                id="timezone"
                                name="timezone"
                                value={data.timezone}
                                onChange={(event) => setData('timezone', event.target.value)}
                                className="w-full rounded-md border border-zinc-400/40 bg-white/80 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-300 dark:border-zinc-700/60 dark:bg-zinc-900/80 dark:text-zinc-100"
                            >
                                {options.timezones.map((zone) => (
                                    <option key={zone} value={zone} className="bg-inherit text-inherit">
                                        {zone}
                                    </option>
                                ))}
                            </select>
                            <InputError message={errors.timezone} className="text-sm" />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="theme">{t('preferences.theme_label')}</Label>
                            <select
                                id="theme"
                                name="theme"
                                value={data.theme}
                                onChange={(event) => setData('theme', event.target.value)}
                                className="w-full rounded-md border border-zinc-400/40 bg-white/80 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-300 dark:border-zinc-700/60 dark:bg-zinc-900/80 dark:text-zinc-100"
                            >
                                {options.themes.map((theme) => (
                                    <option key={theme} value={theme} className="bg-inherit text-inherit">
                                        {t(`preferences.theme_options.${theme}`)}
                                    </option>
                                ))}
                            </select>
                            <InputError message={errors.theme} className="text-sm" />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="font_scale">{t('preferences.font_scale_label')}</Label>
                            <select
                                id="font_scale"
                                name="font_scale"
                                value={data.font_scale}
                                onChange={(event) => setData('font_scale', Number.parseInt(event.target.value, 10))}
                                className="w-full rounded-md border border-zinc-400/40 bg-white/80 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-300 dark:border-zinc-700/60 dark:bg-zinc-900/80 dark:text-zinc-100"
                            >
                                {options.font_scales.map((scale) => (
                                    <option key={scale} value={scale} className="bg-inherit text-inherit">
                                        {t(`preferences.font_scale_options.${scale}`)}
                                    </option>
                                ))}
                            </select>
                            <p className="text-xs text-zinc-600 dark:text-zinc-400">{t('preferences.font_scale_help')}</p>
                            <InputError message={errors.font_scale} className="text-sm" />
                        </div>
                    </div>
                    <div className="flex items-center gap-3">
                        <Checkbox
                            id="high_contrast"
                            checked={data.high_contrast}
                            onCheckedChange={(checked) => setData('high_contrast', Boolean(checked))}
                        />
                        <Label htmlFor="high_contrast" className="text-sm">
                            {t('preferences.contrast_label')}
                        </Label>
                    </div>
                    <fieldset className="space-y-4 rounded-lg border border-zinc-400/40 p-4 dark:border-zinc-700/60">
                        <legend className="text-base font-medium">
                            {t('preferences.notifications.title')}
                        </legend>
                        <p className="text-sm text-zinc-600 dark:text-zinc-400">
                            {t('preferences.notifications.description')}
                        </p>
                        <div className="space-y-3">
                            <div className="flex items-start gap-3">
                                <Checkbox
                                    id="notification_channel_in_app"
                                    checked={data.notification_channel_in_app}
                                    onCheckedChange={(checked) =>
                                        setData('notification_channel_in_app', Boolean(checked))
                                    }
                                />
                                <div className="space-y-1">
                                    <Label htmlFor="notification_channel_in_app" className="text-sm font-medium">
                                        {t('preferences.notifications.channels.in_app')}
                                    </Label>
                                    <p className="text-xs text-zinc-600 dark:text-zinc-400">
                                        {t('preferences.notifications.channels.in_app_hint')}
                                    </p>
                                </div>
                            </div>
                            <div className="flex items-start gap-3">
                                <Checkbox
                                    id="notification_channel_push"
                                    checked={data.notification_channel_push}
                                    onCheckedChange={(checked) =>
                                        setData('notification_channel_push', Boolean(checked))
                                    }
                                />
                                <div className="space-y-1">
                                    <Label htmlFor="notification_channel_push" className="text-sm font-medium">
                                        {t('preferences.notifications.channels.push')}
                                    </Label>
                                    <p className="text-xs text-zinc-600 dark:text-zinc-400">
                                        {t('preferences.notifications.channels.push_hint')}
                                    </p>
                                </div>
                            </div>
                            <div className="flex items-start gap-3">
                                <Checkbox
                                    id="notification_channel_email"
                                    checked={data.notification_channel_email}
                                    onCheckedChange={(checked) =>
                                        setData('notification_channel_email', Boolean(checked))
                                    }
                                />
                                <div className="space-y-1">
                                    <Label htmlFor="notification_channel_email" className="text-sm font-medium">
                                        {t('preferences.notifications.channels.email')}
                                    </Label>
                                    <p className="text-xs text-zinc-600 dark:text-zinc-400">
                                        {t('preferences.notifications.channels.email_hint')}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="notification_quiet_hours_start" className="text-sm font-medium">
                                    {t('preferences.notifications.quiet_hours_start')}
                                </Label>
                                <Input
                                    id="notification_quiet_hours_start"
                                    type="time"
                                    value={data.notification_quiet_hours_start ?? ''}
                                    onChange={(event) =>
                                        setData('notification_quiet_hours_start', event.target.value || null)
                                    }
                                    className="w-full"
                                />
                                <InputError message={errors.notification_quiet_hours_start} className="text-sm" />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="notification_quiet_hours_end" className="text-sm font-medium">
                                    {t('preferences.notifications.quiet_hours_end')}
                                </Label>
                                <Input
                                    id="notification_quiet_hours_end"
                                    type="time"
                                    value={data.notification_quiet_hours_end ?? ''}
                                    onChange={(event) =>
                                        setData('notification_quiet_hours_end', event.target.value || null)
                                    }
                                    className="w-full"
                                />
                                <InputError message={errors.notification_quiet_hours_end} className="text-sm" />
                            </div>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="notification_digest_delivery" className="text-sm font-medium">
                                {t('preferences.notifications.digest_label')}
                            </Label>
                            <select
                                id="notification_digest_delivery"
                                name="notification_digest_delivery"
                                value={data.notification_digest_delivery}
                                onChange={(event) =>
                                    setData('notification_digest_delivery', event.target.value)
                                }
                                className="w-full rounded-md border border-zinc-400/40 bg-white/80 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-300 dark:border-zinc-700/60 dark:bg-zinc-900/80 dark:text-zinc-100"
                            >
                                {options.notification_digests.map((option) => (
                                    <option key={option} value={option} className="bg-inherit text-inherit">
                                        {t(`preferences.notifications.digest_options.${option}`)}
                                    </option>
                                ))}
                            </select>
                            <p className="text-xs text-zinc-600 dark:text-zinc-400">
                                {t('preferences.notifications.digest_hint')}
                            </p>
                            <InputError message={errors.notification_digest_delivery} className="text-sm" />
                        </div>
                        <div className="space-y-3 rounded-md border border-dashed border-zinc-400/40 p-4 dark:border-zinc-700/60">
                            <div>
                                <p className="text-sm font-medium text-zinc-800 dark:text-zinc-100">
                                    {t('preferences.notifications.digest_channels_title')}
                                </p>
                                <p className="text-xs text-zinc-600 dark:text-zinc-400">
                                    {t('preferences.notifications.digest_channels_hint')}
                                </p>
                            </div>
                            <div className="space-y-2">
                                <div className="flex items-start gap-3">
                                    <Checkbox
                                        id="notification_digest_channel_in_app"
                                        checked={data.notification_digest_channel_in_app}
                                        onCheckedChange={(checked) =>
                                            setData('notification_digest_channel_in_app', Boolean(checked))
                                        }
                                    />
                                    <div className="space-y-1">
                                        <Label htmlFor="notification_digest_channel_in_app" className="text-sm font-medium">
                                            {t('preferences.notifications.digest_channels.in_app')}
                                        </Label>
                                    </div>
                                </div>
                                <div className="flex items-start gap-3">
                                    <Checkbox
                                        id="notification_digest_channel_email"
                                        checked={data.notification_digest_channel_email}
                                        onCheckedChange={(checked) =>
                                            setData('notification_digest_channel_email', Boolean(checked))
                                        }
                                    />
                                    <div className="space-y-1">
                                        <Label htmlFor="notification_digest_channel_email" className="text-sm font-medium">
                                            {t('preferences.notifications.digest_channels.email')}
                                        </Label>
                                    </div>
                                </div>
                                <div className="flex items-start gap-3">
                                    <Checkbox
                                        id="notification_digest_channel_push"
                                        checked={data.notification_digest_channel_push}
                                        onCheckedChange={(checked) =>
                                            setData('notification_digest_channel_push', Boolean(checked))
                                        }
                                    />
                                    <div className="space-y-1">
                                        <Label htmlFor="notification_digest_channel_push" className="text-sm font-medium">
                                            {t('preferences.notifications.digest_channels.push')}
                                        </Label>
                                    </div>
                                </div>
                            </div>
                            <InputError message={errors.notification_digest_channel_in_app} className="text-sm" />
                            <InputError message={errors.notification_digest_channel_email} className="text-sm" />
                            <InputError message={errors.notification_digest_channel_push} className="text-sm" />
                        </div>
                    </fieldset>
                    <Button type="submit" disabled={processing} className="inline-flex items-center gap-2">
                        {t('preferences.submit')}
                    </Button>
                </form>
            </section>
        </AppLayout>
    );
}
