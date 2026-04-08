<?php

namespace App\Http\Requests\Admin\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateZApiSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'instance_id' => ['required', 'string', 'max:255'],
            'token' => ['nullable', 'string', 'max:255'],
            'client_token' => ['nullable', 'string', 'max:255'],
        ];
    }
}
