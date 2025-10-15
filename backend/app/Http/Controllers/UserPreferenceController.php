<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserPreferenceUpdateRequest;
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

        return Inertia::render('Settings/Preferences', [
            'form' => [
                'locale' => $user->locale,
                'timezone' => $user->timezone,
                'theme' => $user->theme,
                'high_contrast' => $user->high_contrast,
                'font_scale' => $user->font_scale,
            ],
            'options' => [
                'locales' => array_keys((array) config('preferences.locales')),
                'themes' => array_keys((array) config('preferences.themes')),
                'font_scales' => config('preferences.font_scales'),
                'timezones' => DateTimeZone::listIdentifiers(),
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

        return redirect()
            ->route('settings.preferences.edit')
            ->with('success', Lang::get('app.preferences.success'));
    }
}
