<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmpleadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('usuario')?->id;

        return [
            'nombre' => ['sometimes', 'required', 'string', 'max:100'],
            'usuario' => ['sometimes', 'required', 'string', 'alpha_dash', 'max:50', Rule::unique('usuarios', 'usuario')->ignore($id)],
            'pin' => ['nullable', 'string', 'regex:/^\d{4,6}$/'],
            'rol' => ['sometimes', 'required', 'string', Rule::exists('roles', 'nombre')],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('usuarios', 'email')->ignore($id)],
            'activo' => ['sometimes', 'boolean'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'puesto' => ['nullable', 'string', 'max:50'],
            'fecha_ingreso' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return MensajesEmpleado::MENSAJES;
    }
}
