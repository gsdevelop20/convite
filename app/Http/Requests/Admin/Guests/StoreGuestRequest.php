<?php

namespace App\Http\Requests\Admin\Guests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGuestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'regex:/^\d{10,11}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Informe o telefone apenas com numeros, no padrao 61995706650.',
        ];
    }
}
