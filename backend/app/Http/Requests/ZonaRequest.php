<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ZonaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $requerido = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'nombre' => [$requerido, 'string', 'max:50'],
            'tipo' => ['sometimes', Rule::in(['salon', 'terraza', 'barra'])],
            'x' => ['sometimes', 'integer', 'between:0,5000'],
            'y' => ['sometimes', 'integer', 'between:0,5000'],
            'ancho' => ['sometimes', 'integer', 'between:20,5000'],
            'alto' => ['sometimes', 'integer', 'between:20,5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'Escribe el nombre de la zona.',
            'tipo.in' => 'El tipo debe ser salón, terraza o barra.',
        ];
    }
}
