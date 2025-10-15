<?php

namespace App\Http\Requests;

use App\Models\GroupMembership;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GroupMembershipStoreRequest extends FormRequest
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
        return [
            'email' => ['required', 'email'],
            'role' => ['sometimes', 'string', Rule::in(GroupMembership::roles())],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->filled('role')) {
            $this->merge([
                'role' => GroupMembership::ROLE_PLAYER,
            ]);
        }
    }
}
