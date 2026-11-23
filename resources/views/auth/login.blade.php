<x-auth-card title="Iniciar sesión">
    <form method="post" action="{{ route('login') }}">
        @csrf
        <x-auth-field name="email" label="Correo electrónico" type="email" autocomplete="email" />
        <x-auth-field name="password" label="Contraseña" type="password" autocomplete="current-password" />
        <label class="d-flex align-items-center gap-2 mb-3"><input type="checkbox" name="remember" value="1" @checked(old('remember'))> Recordar sesión</label>
        @if ($recaptchaEnabled)
            @if ($recaptchaSiteKey)
                <div class="g-recaptcha mb-3" data-sitekey="{{ $recaptchaSiteKey }}" data-size="compact"></div>
                <script src="https://www.google.com/recaptcha/api.js?hl=es" async defer></script>
            @else
                <p class="zaza-field-error" role="alert">La verificación de seguridad no está disponible.</p>
            @endif
            @error('g-recaptcha-response')
                <p class="zaza-field-error" role="alert">{{ $message }}</p>
            @enderror
        @endif
        <button class="btn btn-light zaza-button w-100" type="submit" @disabled($recaptchaEnabled && ! $recaptchaSiteKey)>Ingresar</button>
    </form>
    <a class="d-block mt-3" href="{{ route('password.request') }}">Olvidé mi contraseña</a>
    <a class="d-block mt-2" href="{{ route('register') }}">Crear una cuenta</a>
</x-auth-card>
