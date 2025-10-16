<?php

namespace App\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

class UpdateConditionTimerSummaryShareRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'expires_in_hours' => ['required', 'integer', 'min:1', 'max:720'],
        ];
    }

    public function resolveExpiresAt(): CarbonImmutable
    {
        $hours = (int) $this->input('expires_in_hours');

        return CarbonImmutable::now('UTC')->addHours($hours);
    }
}
