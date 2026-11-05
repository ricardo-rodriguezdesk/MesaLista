<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $esAlta = $this->isMethod('post');
        $id = $this->route('categoria')?->id;

        return [
            'nombre' => [$esAlta ? 'required' : 'sometimes', 'string', 'max:60', Rule::unique('categorias', 'nombre')->ignore($id)],
            'orden' => ['nullable', 'integer', 'min:0', 'max:999'],
            'activo' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'Escribe el nombre de la categoría.',
            'nombre.unique' => 'Ya existe una categoría con ese nombre.',
            'nombre.max' => 'El nombre no puede tener más de 60 caracteres.',
            'orden.integer' => 'El orden debe ser un número entero.',
        ];
    }
}
