<x-auth-card title="Crear una cuenta">
    <form method="post" action="{{ route('register') }}">
        @csrf
        <x-auth-field name="nombre_completo" label="Nombre y apellidos" autocomplete="name" />
        <x-auth-field name="email" label="Correo electrónico" type="email" autocomplete="email" />
        <x-auth-field name="telefono" label="Teléfono" type="tel" autocomplete="tel" />
        <x-auth-field name="password" label="Contraseña (mínimo 8 caracteres)" type="password" minlength="8" autocomplete="new-password" />
        <x-auth-field name="password_confirmation" label="Confirmar contraseña" type="password" minlength="8" autocomplete="new-password" />
        <label class="d-flex align-items-center gap-2 mb-3"><input type="checkbox" name="acepta_marketing" value="1" @checked(old('acepta_marketing'))> Acepto recibir novedades y promociones</label>
        @error('acepta_marketing')<p class="zaza-field-error small">{{ $message }}</p>@enderror
        <p class="small mb-3">Consulta la <a href="{{ route('privacy') }}">política de privacidad</a>.</p>
        <button class="btn btn-light zaza-button w-100" type="submit">Registrarme</button>
    </form>
    <p class="mt-3 mb-0">¿Ya tienes una cuenta? <a href="{{ route('login') }}">Iniciar sesión</a></p>
</x-auth-card>
