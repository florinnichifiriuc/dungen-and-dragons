<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConditionTimerSummaryShareExtendRequest extends FormRequest
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
            'expiry_preset' => ['required', 'in:24h,72h,custom,never'],
            'extends_in_hours' => ['nullable', 'integer', 'min:1', 'max:336'],
        ];
    }

    public function shouldNeverExpire(): bool
    {
        return $this->string('expiry_preset')->toString() === 'never';
    }

    public function extensionHours(): ?int
    {
        return match ($this->string('expiry_preset')->toString()) {
            '24h' => 24,
            '72h' => 72,
            'never' => null,
            'custom' => $this->integer('extends_in_hours') ?: null,
            default => null,
        };
    }
}
