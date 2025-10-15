<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SessionRecordingStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'recording' => [
                'required',
                'file',
                'max:512000',
                'mimetypes:audio/mpeg,audio/mp3,audio/wav,audio/x-wav,audio/flac,audio/ogg,audio/webm,video/mp4,video/webm,video/ogg',
            ],
        ];
    }
}
