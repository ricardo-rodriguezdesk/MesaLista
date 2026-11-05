<?php

namespace App\Http\Requests;

use App\Models\Rol;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmpleadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:100'],
            'usuario' => ['required', 'string', 'alpha_dash', 'max:50', Rule::unique('usuarios', 'usuario')],
            'pin' => ['required', 'string', 'regex:/^\d{4,6}$/'],
            'rol' => ['required', 'string', Rule::exists('roles', 'nombre')],
            'email' => ['nullable', 'required_if:rol,'.Rol::ADMINISTRADOR, 'email', 'max:255', Rule::unique('usuarios', 'email')],
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
