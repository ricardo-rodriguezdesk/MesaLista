<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'usuario' => ['required', 'string', 'max:50'],
            'pin' => ['required', 'string', 'regex:/^\d{4,6}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'usuario.required' => 'Escribe tu usuario.',
            'pin.required' => 'Escribe tu PIN.',
            'pin.regex' => 'El PIN debe tener de 4 a 6 dígitos.',
        ];
    }
}
