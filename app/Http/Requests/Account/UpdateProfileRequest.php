<?php

namespace App\Http\Requests\Account;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => strtolower($this->input('email'))]);
        }
    }

    public function rules(): array
    {
        return [
            'nombre_completo' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)->ignore($this->user())],
            'telefono' => ['required', 'string', 'max:32'],
            'genero' => ['nullable', 'string', 'max:80'],
            'direccion' => ['nullable', 'string', 'max:500'],
            'acepta_marketing' => ['sometimes', 'boolean'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=4096,max_height=4096'],
            'quitar_avatar' => ['sometimes', 'boolean', Rule::when($this->boolean('quitar_avatar'), 'prohibits:avatar')],
        ];
    }
}
