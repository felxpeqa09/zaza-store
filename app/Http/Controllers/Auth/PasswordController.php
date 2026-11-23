<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\UpdatePasswordRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PasswordController extends Controller
{
    public function update(UpdatePasswordRequest $request): RedirectResponse
    {
        $user = $request->user();
        $previousHash = $user->getAuthPassword();
        $user->password = $request->validated('password');
        $user->setRememberToken(Str::random(60));
        $updated = User::query()->whereKey($user->getKey())->where('password', $previousHash)
            ->update($user->getDirty());
        if ($updated !== 1) {
            throw ValidationException::withMessages([
                'current_password' => 'La contraseña cambió. Inicia sesión de nuevo.',
            ])->errorBag('updatePassword');
        }
        $request->session()->regenerate();

        return redirect()->to(route('account.edit').'#contrasena')
            ->with('password_status', 'Tu contraseña se actualizó.');
    }
}
