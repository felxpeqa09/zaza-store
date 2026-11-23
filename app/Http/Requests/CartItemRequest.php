<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CartItemRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'variant_id' => [$this->isMethod('post') ? 'required' : 'sometimes', 'integer', 'min:1'],
            'cantidad' => ['required', 'integer', 'min:1', 'max:2147483647'],
        ];
    }
}
