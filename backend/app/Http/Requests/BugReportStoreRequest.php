<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BugReportStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('context_type')) {
            $context = $this->routeIs('shares.condition-timers.bug-report.store')
                ? 'player_share'
                : 'facilitator';

            $this->merge([
                'context_type' => $context,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'summary' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'steps' => ['nullable', 'string'],
            'expected' => ['nullable', 'string'],
            'actual' => ['nullable', 'string'],
            'attachments' => ['nullable', 'array'],
            'context' => ['nullable', 'array'],
            'context.logs' => ['nullable', 'array'],
            'context.path' => ['nullable', 'string', 'max:255'],
            'context.browser' => ['nullable', 'string', 'max:255'],
            'context.locale' => ['nullable', 'string', 'max:10'],
            'context.extra' => ['nullable', 'array'],
            'ai_focus' => ['nullable', 'array'],
            'ai_focus.*' => ['string'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string'],
            'priority' => ['nullable', 'string', 'in:low,normal,high,critical'],
            'context_type' => ['required', 'string', 'in:facilitator,player_share,admin'],
            'context_identifier' => ['nullable', 'string', 'max:255'],
            'submitted_email' => ['nullable', 'email'],
            'submitted_name' => ['nullable', 'string', 'max:120'],
        ];
    }
}
