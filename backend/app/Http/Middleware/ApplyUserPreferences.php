<?php

namespace App\Http\Middleware;

use Closure;
use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;

class ApplyUserPreferences
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        $locales = array_keys((array) config('preferences.locales'));
        $locale = $user?->locale;

        if (! $locale || ! in_array($locale, $locales, true)) {
            $locale = config('app.locale');
        }

        App::setLocale($locale);

        $timezone = $user?->timezone ?: config('app.timezone');
        if (! in_array($timezone, DateTimeZone::listIdentifiers(), true)) {
            $timezone = config('app.timezone');
        }

        Config::set('app.timezone', $timezone);
        date_default_timezone_set($timezone);

        return $next($request);
    }
}
