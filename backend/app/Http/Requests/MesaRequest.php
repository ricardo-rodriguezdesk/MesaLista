<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MesaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $requerido = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'zona_id' => [$requerido, 'integer', Rule::exists('zonas', 'id')],
            'nombre' => [$requerido, 'string', 'max:20', Rule::unique('mesas', 'nombre')->ignore($this->route('mesa')?->id)],
            'capacidad' => ['sometimes', 'integer', 'between:1,50'],
            'forma' => ['sometimes', Rule::in(['cuadrada', 'redonda', 'barra'])],
            'x' => ['sometimes', 'integer', 'between:0,5000'],
            'y' => ['sometimes', 'integer', 'between:0,5000'],
            'ancho' => ['sometimes', 'integer', 'between:20,1000'],
            'alto' => ['sometimes', 'integer', 'between:20,1000'],
            'rotacion' => ['sometimes', 'integer', 'between:-360,360'],
            'activo' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'zona_id.required' => 'Elige la zona de la mesa.',
            'zona_id.exists' => 'La zona no existe.',
            'nombre.required' => 'Escribe el nombre o número de la mesa.',
            'nombre.unique' => 'Ya existe una mesa con ese nombre.',
            'capacidad.between' => 'La capacidad debe ser de 1 a 50 personas.',
            'forma.in' => 'La forma debe ser cuadrada, redonda o barra.',
        ];
    }
}
