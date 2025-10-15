<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NpcDialogueRequest extends FormRequest
{
    public function authorize(): bool
    {
        $session = $this->route('session');

        return $session && $this->user()?->can('npcDialogue', $session);
    }

    public function rules(): array
    {
        return [
            'npc_name' => ['required', 'string', 'max:100'],
            'prompt' => ['required', 'string', 'max:1000'],
            'tone' => ['nullable', 'string', 'max:120'],
        ];
    }
}
