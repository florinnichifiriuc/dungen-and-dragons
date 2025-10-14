<?php

namespace App\Http\Requests;

use App\Models\CampaignRoleAssignment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CampaignInvitationStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'group_id' => ['nullable', 'integer', 'exists:groups,id', 'required_without:email', 'prohibits:email'],
            'email' => ['nullable', 'email', 'max:255', 'required_without:group_id'],
            'role' => ['required', Rule::in(CampaignRoleAssignment::roles())],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }
}
