<?php

namespace App\Http\Requests;

use App\Models\CuentaDetalle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EstadoPedidoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'estado' => ['required', Rule::in([CuentaDetalle::PREPARANDO, CuentaDetalle::LISTO])],
        ];
    }

    public function messages(): array
    {
        return [
            'estado.required' => 'Indica el nuevo estado del platillo.',
            'estado.in' => 'La cocina solo puede marcar un platillo como «preparando» o «listo».',
        ];
    }
}
