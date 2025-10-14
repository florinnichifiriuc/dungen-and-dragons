<?php

namespace App\Http\Requests;

use App\Models\Group;
use App\Models\User;
use App\Models\CampaignRoleAssignment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CampaignRoleAssignmentStoreRequest extends FormRequest
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
        $assigneeType = $this->string('assignee_type')->toString();

        $assigneeRules = ['required', 'integer'];
        if ($assigneeType === Group::class || $assigneeType === 'group') {
            $assigneeRules[] = 'exists:groups,id';
        } else {
            $assigneeRules[] = 'exists:users,id';
        }

        return [
            'assignee_type' => ['required', 'string', Rule::in([User::class, Group::class, 'user', 'group'])],
            'assignee_id' => $assigneeRules,
            'role' => ['required', Rule::in(CampaignRoleAssignment::roles())],
            'scope' => ['nullable', 'string', Rule::in(['campaign', 'region', 'world'])],
            'status' => ['nullable', 'string', Rule::in([
                CampaignRoleAssignment::STATUS_ACTIVE,
                CampaignRoleAssignment::STATUS_PENDING,
                CampaignRoleAssignment::STATUS_REVOKED,
            ])],
            'accept_immediately' => ['nullable', 'boolean'],
        ];
    }
}
