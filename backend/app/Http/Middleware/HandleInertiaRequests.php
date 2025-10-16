<?php

namespace App\Http\Middleware;

use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Defines the props that are shared by default.
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'csrf_token' => fn () => csrf_token(),
            'locale' => fn () => app()->getLocale(),
            'auth' => [
                'user' => fn () => $request->user()?->only([
                    'id',
                    'name',
                    'email',
                    'locale',
                    'timezone',
                    'theme',
                    'high_contrast',
                    'font_scale',
                ]),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'notifications' => [
                'unread_count' => fn () => $request->user()?->unreadNotifications()->count() ?? 0,
            ],
            'preferences' => fn () => $request->user()?->only([
                'locale',
                'timezone',
                'theme',
                'high_contrast',
                'font_scale',
            ]) ?? [
                'locale' => app()->getLocale(),
                'timezone' => config('app.timezone'),
                'theme' => 'system',
                'high_contrast' => false,
                'font_scale' => 100,
            ],
            'preferenceOptions' => [
                'locales' => array_keys((array) config('preferences.locales')),
                'themes' => array_keys((array) config('preferences.themes')),
                'font_scales' => config('preferences.font_scales'),
                'timezones' => DateTimeZone::listIdentifiers(),
            ],
            'translations' => [
                'app' => fn () => Lang::get('app'),
            ],
        ]);
    }
}
