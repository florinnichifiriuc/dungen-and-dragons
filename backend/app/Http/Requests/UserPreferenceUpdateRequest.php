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

        return [
            'locale' => ['required', Rule::in($locales)],
            'timezone' => ['required', 'timezone:all'],
            'theme' => ['required', Rule::in($themes)],
            'high_contrast' => ['nullable', 'boolean'],
            'font_scale' => ['required', 'integer', Rule::in($fontScales)],
        ];
    }
}
