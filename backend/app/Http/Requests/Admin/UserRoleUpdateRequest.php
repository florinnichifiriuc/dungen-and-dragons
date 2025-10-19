<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UserRoleUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('manageUserRoles');
    }

    public function rules(): array
    {
        return [
            'account_role' => ['required', 'string', Rule::in(User::accountRoles())],
        ];
    }
}
