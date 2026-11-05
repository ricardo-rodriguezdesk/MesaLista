<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarCroquisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'zonas' => ['required_without:mesas', 'array'],
            'zonas.*.id' => ['required', 'integer', Rule::exists('zonas', 'id')],
            'zonas.*.x' => ['required', 'integer', 'between:0,5000'],
            'zonas.*.y' => ['required', 'integer', 'between:0,5000'],
            'zonas.*.ancho' => ['required', 'integer', 'between:20,5000'],
            'zonas.*.alto' => ['required', 'integer', 'between:20,5000'],

            'mesas' => ['required_without:zonas', 'array'],
            'mesas.*.id' => ['required', 'integer', Rule::exists('mesas', 'id')],
            'mesas.*.zona_id' => ['sometimes', 'integer', Rule::exists('zonas', 'id')],
            'mesas.*.x' => ['required', 'integer', 'between:0,5000'],
            'mesas.*.y' => ['required', 'integer', 'between:0,5000'],
            'mesas.*.ancho' => ['required', 'integer', 'between:20,1000'],
            'mesas.*.alto' => ['required', 'integer', 'between:20,1000'],
            'mesas.*.rotacion' => ['sometimes', 'integer', 'between:-360,360'],
        ];
    }

    public function messages(): array
    {
        return [
            'zonas.required_without' => 'Envía las zonas o las mesas a guardar.',
            'mesas.required_without' => 'Envía las zonas o las mesas a guardar.',
            '*.*.id.exists' => 'Uno de los elementos del croquis ya no existe.',
            '*.*.*.between' => 'Una posición o tamaño está fuera de rango.',
        ];
    }
}
