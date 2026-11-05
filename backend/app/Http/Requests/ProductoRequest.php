<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $esAlta = $this->isMethod('post');
        $producto = $this->route('producto');
        $categoriaId = $this->input('categoria_id', $producto?->categoria_id);
        $requerido = $esAlta ? 'required' : 'sometimes';

        return [
            'categoria_id' => [$requerido, 'integer', Rule::exists('categorias', 'id')],
            'nombre' => [
                $requerido, 'string', 'max:100',
                Rule::unique('productos', 'nombre')
                    ->where('categoria_id', $categoriaId)
                    ->ignore($producto?->id),
            ],
            'descripcion' => ['nullable', 'string', 'max:500'],
            'precio' => [$requerido, 'numeric', 'decimal:0,2', 'min:0.01', 'max:99999.99'],
            // 16 % general, 8 % región fronteriza, 0 % alimentos y medicinas
            'tasa_iva' => ['sometimes', 'numeric', Rule::in([0, 0.08, 0.16])],
            'clave_prod_serv' => ['sometimes', 'digits:8'],
            'clave_unidad' => ['sometimes', 'string', 'size:3', 'alpha_num'],
            'activo' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'categoria_id.required' => 'Elige una categoría.',
            'categoria_id.exists' => 'La categoría no existe.',
            'nombre.required' => 'Escribe el nombre del producto.',
            'nombre.unique' => 'Ya existe un producto con ese nombre en esta categoría.',
            'precio.required' => 'Escribe el precio.',
            'precio.numeric' => 'El precio debe ser un número.',
            'precio.decimal' => 'El precio puede tener máximo 2 decimales.',
            'precio.min' => 'El precio debe ser mayor a cero.',
            'precio.max' => 'El precio es demasiado alto.',
            'tasa_iva.in' => 'La tasa de IVA debe ser 0, 0.08 o 0.16.',
            'clave_prod_serv.digits' => 'La clave de producto o servicio del SAT tiene 8 dígitos.',
            'clave_unidad.size' => 'La clave de unidad del SAT tiene 3 caracteres.',
        ];
    }
}
