<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CatalogRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'subcategoria' => ['nullable', 'string', 'max:255'],
            'precio_min' => ['nullable', 'integer', 'min:0'],
            'precio_max' => ['nullable', 'integer', 'min:0', Rule::when($this->filled('precio_min'), 'gte:precio_min')],
            'talla' => ['nullable', 'string', 'max:20'],
            'orden' => ['nullable', Rule::in(['recientes', 'menor-precio', 'mayor-precio', 'nombre'])],
        ];
    }
}
