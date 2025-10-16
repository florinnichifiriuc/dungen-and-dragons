<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserPreferenceUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $locales = array_keys((array) config('preferences.locales'));
        $themes = array_keys((array) config('preferences.themes'));
        $fontScales = config('preferences.font_scales');
        $digestOptions = config('notifications.digest_options', ['off']);

        return [
            'locale' => ['required', Rule::in($locales)],
            'timezone' => ['required', 'timezone:all'],
            'theme' => ['required', Rule::in($themes)],
            'high_contrast' => ['nullable', 'boolean'],
            'font_scale' => ['required', 'integer', Rule::in($fontScales)],
            'notification_channel_in_app' => ['required', 'boolean'],
            'notification_channel_push' => ['required', 'boolean'],
            'notification_channel_email' => ['required', 'boolean'],
            'notification_quiet_hours_start' => ['nullable', 'date_format:H:i', 'required_with:notification_quiet_hours_end'],
            'notification_quiet_hours_end' => ['nullable', 'date_format:H:i', 'required_with:notification_quiet_hours_start'],
            'notification_digest_delivery' => ['required', Rule::in($digestOptions)],
            'notification_digest_channel_in_app' => ['required', 'boolean'],
            'notification_digest_channel_email' => ['required', 'boolean'],
            'notification_digest_channel_push' => ['required', 'boolean'],
        ];
    }
}
