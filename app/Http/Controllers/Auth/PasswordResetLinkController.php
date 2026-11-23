<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RecoveryEmailRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(RecoveryEmailRequest $request): RedirectResponse
    {
        Password::sendResetLink($request->validated());

        return back()->with('status', 'Comprueba tu bandeja de entrada');
    }
}
