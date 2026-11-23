<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    public function create(Request $request, string $token): View
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->string('email')->toString()]);
    }

    public function store(ResetPasswordRequest $request): RedirectResponse
    {
        $status = $this->reset($request->validated());

        return $status === Password::PASSWORD_RESET
            ? to_route('login')->with('status', 'Tu contraseña se actualizó. Ya puedes iniciar sesión.')
            : back()->withInput($request->only('email'))
                ->withErrors(['email' => 'El enlace de recuperación es inválido o está vencido. Solicita uno nuevo.']);
    }

    private function reset(array $credentials): string
    {
        return DB::transaction(function () use ($credentials): string {
            $table = config('auth.passwords.'.config('auth.defaults.passwords').'.table');
            DB::table($table)->where('email', $credentials['email'])->lockForUpdate()->first();

            return Password::reset($credentials, function (User $user, string $password): void {
                $user->password = $password;
                $user->setRememberToken(Str::random(60));
                $user->save();

                DB::afterCommit(fn () => event(new PasswordReset($user)));
            });
        }, 3);
    }
}
