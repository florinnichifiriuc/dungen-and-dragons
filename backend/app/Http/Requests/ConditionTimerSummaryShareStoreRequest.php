<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConditionTimerSummaryShareStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        $group = $this->route('group');

        return $group && $this->user()?->can('update', $group);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'expiry_preset' => ['nullable', 'in:24h,72h,custom,never'],
            'expires_in_hours' => ['nullable', 'integer', 'min:1', 'max:336'],
            'visibility_mode' => ['nullable', 'in:counts,details'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function expiresInHours(): ?int
    {
        $preset = $this->string('expiry_preset')->toString();

        return match ($preset) {
            '24h' => 24,
            '72h' => 72,
            'never' => null,
            'custom' => $this->integer('expires_in_hours') ?: null,
            default => null,
        };
    }

    public function shouldNeverExpire(): bool
    {
        return $this->string('expiry_preset')->toString() === 'never';
    }
}
