<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AgregarPlatillosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'platillos' => ['required', 'array', 'min:1', 'max:50'],
            'platillos.*.producto_id' => ['required', 'integer', Rule::exists('productos', 'id')],
            'platillos.*.cantidad' => ['required', 'integer', 'between:1,99'],
            'platillos.*.notas' => ['nullable', 'string', 'max:255'],
            'platillos.*.comensal' => ['nullable', 'integer', 'between:1,50'],
        ];
    }

    public function messages(): array
    {
        return [
            'platillos.required' => 'Agrega al menos un platillo.',
            'platillos.min' => 'Agrega al menos un platillo.',
            'platillos.*.producto_id.exists' => 'Uno de los productos no existe.',
            'platillos.*.cantidad.between' => 'La cantidad debe ser de 1 a 99.',
            'platillos.*.notas.max' => 'Las notas pueden tener máximo 255 caracteres.',
        ];
    }
}
