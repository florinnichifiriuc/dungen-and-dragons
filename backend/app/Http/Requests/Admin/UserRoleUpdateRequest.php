<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UserRoleUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User|null $actor */
        $actor = $this->user();

        return (bool) ($actor?->is_support_admin);
    }

    public function rules(): array
    {
        return [
            'is_support_admin' => ['required', 'boolean'],
        ];
    }
}
