<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'checkout_token' => ['required', 'uuid'],
            'nombre_completo' => ['required', 'string', 'max:255'],
            'telefono' => ['required', 'string', 'max:32'],
            'direccion' => ['required', 'string', 'max:500'],
            'ciudad' => ['required', 'string', 'max:100'],
            'departamento' => ['required', 'string', 'max:100'],
        ];
    }
}
