<?php

namespace App\Http\Requests;

use App\Models\Map;
use App\Models\MapToken;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class MapTokenConditionTimerBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $map = $this->route('map');

        if (! $user || ! $map instanceof Map) {
            return false;
        }

        return $user->can('create', [MapToken::class, $map]);
    }

    protected function prepareForValidation(): void
    {
        $adjustments = $this->input('adjustments');

        if (! is_array($adjustments)) {
            return;
        }

        $normalized = array_map(function ($adjustment) {
            if (! is_array($adjustment)) {
                return $adjustment;
            }

            return [
                'token_id' => isset($adjustment['token_id']) ? (int) $adjustment['token_id'] : null,
                'condition' => $adjustment['condition'] ?? null,
                'delta' => array_key_exists('delta', $adjustment) && $adjustment['delta'] !== null
                    ? (int) $adjustment['delta']
                    : null,
                'set_to' => array_key_exists('set_to', $adjustment) && $adjustment['set_to'] !== null
                    ? (int) $adjustment['set_to']
                    : null,
                'expected_rounds' => array_key_exists('expected_rounds', $adjustment) && $adjustment['expected_rounds'] !== null
                    ? (int) $adjustment['expected_rounds']
                    : null,
            ];
        }, $adjustments);

        $this->merge(['adjustments' => $normalized]);
    }

    public function rules(): array
    {
        return [
            'adjustments' => ['required', 'array', 'min:1'],
            'adjustments.*.token_id' => ['required', 'integer'],
            'adjustments.*.condition' => ['required', 'string', Rule::in(MapToken::CONDITIONS)],
            'adjustments.*.delta' => [
                'nullable',
                'integer',
                'not_in:0',
                'between:-'.MapToken::MAX_CONDITION_DURATION.','.MapToken::MAX_CONDITION_DURATION,
            ],
            'adjustments.*.set_to' => [
                'nullable',
                'integer',
                'between:1,'.MapToken::MAX_CONDITION_DURATION,
            ],
            'adjustments.*.expected_rounds' => ['nullable', 'integer', 'between:1,'.MapToken::MAX_CONDITION_DURATION],
        ];
    }

    public function messages(): array
    {
        return [
            'adjustments.required' => trans('app.condition_timer_batch.errors.none_selected'),
            'adjustments.array' => trans('app.condition_timer_batch.errors.invalid_payload'),
            'adjustments.min' => trans('app.condition_timer_batch.errors.none_selected'),
            'adjustments.*.token_id.required' => trans('app.condition_timer_batch.errors.token_missing'),
            'adjustments.*.condition.in' => trans('app.condition_timer_batch.errors.condition_unknown'),
            'adjustments.*.delta.not_in' => trans('app.condition_timer_batch.errors.delta_zero'),
            'adjustments.*.delta.between' => trans('app.condition_timer_batch.errors.delta_bounds'),
            'adjustments.*.set_to.between' => trans('app.condition_timer_batch.errors.set_bounds'),
            'adjustments.*.expected_rounds.between' => trans('app.condition_timer_batch.errors.expected_bounds'),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $adjustments = $this->input('adjustments');

            if (! is_array($adjustments)) {
                return;
            }

            foreach ($adjustments as $index => $adjustment) {
                $hasDelta = array_key_exists('delta', $adjustment) && $adjustment['delta'] !== null;
                $hasSet = array_key_exists('set_to', $adjustment) && $adjustment['set_to'] !== null;

                if (! $hasDelta && ! $hasSet) {
                    $validator->errors()->add(
                        "adjustments.$index.delta",
                        trans('app.condition_timer_batch.errors.delta_or_set')
                    );
                }

                if ($hasDelta && $hasSet) {
                    $validator->errors()->add(
                        "adjustments.$index.delta",
                        trans('app.condition_timer_batch.errors.delta_conflict')
                    );
                }
            }
        });
    }

    protected function failedAuthorization(): void
    {
        throw new AuthorizationException(trans('app.condition_timer_batch.errors.unauthorized'));
    }
}

