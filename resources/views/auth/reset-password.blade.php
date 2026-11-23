<x-auth-card title="Restablecer contraseña">
    <form method="post" action="{{ route('password.store') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        @error('token')<p class="zaza-field-error" role="alert">{{ $message }}</p>@enderror
        <x-auth-field name="email" label="Correo electrónico" type="email" :value="$email" autocomplete="email" />
        <x-auth-field name="password" label="Nueva contraseña (mínimo 8 caracteres)" type="password" minlength="8" autocomplete="new-password" />
        <x-auth-field name="password_confirmation" label="Confirmar contraseña" type="password" minlength="8" autocomplete="new-password" />
        <button class="btn btn-light zaza-button w-100" type="submit">Guardar contraseña</button>
    </form>
</x-auth-card>
