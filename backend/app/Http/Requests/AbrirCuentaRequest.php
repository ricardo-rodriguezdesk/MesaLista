<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AbrirCuentaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mesa_id' => ['required', 'integer', Rule::exists('mesas', 'id')],
            'comensales' => ['required', 'integer', 'between:1,50'],
        ];
    }

    public function messages(): array
    {
        return [
            'mesa_id.required' => 'Elige una mesa.',
            'mesa_id.exists' => 'La mesa no existe.',
            'comensales.required' => 'Indica cuántas personas hay en la mesa.',
            'comensales.between' => 'Los comensales deben ser de 1 a 50.',
        ];
    }
}
