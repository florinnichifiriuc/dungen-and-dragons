<?php

namespace App\Http\Requests;

use App\Services\GlobalSearchService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SearchIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:120'],
            'scopes' => ['nullable', 'array'],
            'scopes.*' => ['string', Rule::in(GlobalSearchService::SCOPES)],
        ];
    }
}
