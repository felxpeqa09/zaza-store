<?php

namespace App\Http\Requests\Auth;

use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends RecoveryEmailRequest
{
    public function rules(): array
    {
        return parent::rules() + [
            'token' => ['required', 'string', 'max:128'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
        ];
    }
}
