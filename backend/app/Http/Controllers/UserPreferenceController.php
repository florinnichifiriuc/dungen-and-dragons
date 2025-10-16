<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserPreferenceUpdateRequest;
use App\Models\NotificationPreference;
use DateTimeZone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;
use Inertia\Inertia;
use Inertia\Response;

class UserPreferenceController extends Controller
{
    /**
     * Show the preference form.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();
        $notificationPreferences = NotificationPreference::forUser($user);

        return Inertia::render('Settings/Preferences', [
            'form' => [
                'locale' => $user->locale,
                'timezone' => $user->timezone,
                'theme' => $user->theme,
                'high_contrast' => $user->high_contrast,
                'font_scale' => $user->font_scale,
                'notification_channel_in_app' => $notificationPreferences->channel_in_app,
                'notification_channel_push' => $notificationPreferences->channel_push,
                'notification_channel_email' => $notificationPreferences->channel_email,
                'notification_quiet_hours_start' => $notificationPreferences->quiet_hours_start,
                'notification_quiet_hours_end' => $notificationPreferences->quiet_hours_end,
                'notification_digest_delivery' => $notificationPreferences->digest_delivery,
            ],
            'options' => [
                'locales' => array_keys((array) config('preferences.locales')),
                'themes' => array_keys((array) config('preferences.themes')),
                'font_scales' => config('preferences.font_scales'),
                'timezones' => DateTimeZone::listIdentifiers(),
                'notification_digests' => config('notifications.digest_options', ['off']),
            ],
        ]);
    }

    /**
     * Persist the preference changes.
     */
    public function update(UserPreferenceUpdateRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $request->user()->forceFill([
            'locale' => $validated['locale'],
            'timezone' => $validated['timezone'],
            'theme' => $validated['theme'],
            'high_contrast' => (bool) ($validated['high_contrast'] ?? false),
            'font_scale' => (int) $validated['font_scale'],
        ])->save();

        $quietStart = $validated['notification_quiet_hours_start'] ?? null;
        $quietEnd = $validated['notification_quiet_hours_end'] ?? null;

        if ($quietStart === '') {
            $quietStart = null;
        }

        if ($quietEnd === '') {
            $quietEnd = null;
        }

        NotificationPreference::forUser($request->user())->fill([
            'channel_in_app' => (bool) $validated['notification_channel_in_app'],
            'channel_push' => (bool) $validated['notification_channel_push'],
            'channel_email' => (bool) $validated['notification_channel_email'],
            'quiet_hours_start' => $quietStart,
            'quiet_hours_end' => $quietEnd,
            'digest_delivery' => $validated['notification_digest_delivery'],
        ])->save();

        return redirect()
            ->route('settings.preferences.edit')
            ->with('success', Lang::get('app.preferences.success'));
    }
}
