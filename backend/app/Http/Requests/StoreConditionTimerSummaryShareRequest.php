<?php

namespace App\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

class StoreConditionTimerSummaryShareRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'expires_in_hours' => ['nullable', 'integer', 'min:1', 'max:720'],
        ];
    }

    public function resolveExpiresAt(): ?CarbonImmutable
    {
        if (! $this->filled('expires_in_hours')) {
            return null;
        }

        $hours = (int) $this->input('expires_in_hours');

        if ($hours <= 0) {
            return null;
        }

        return CarbonImmutable::now('UTC')->addHours($hours);
    }
}
