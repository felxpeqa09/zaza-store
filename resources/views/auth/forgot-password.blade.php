<x-auth-card title="Recuperar contraseña">
    <form method="post" action="{{ route('password.email') }}">
        @csrf
        <x-auth-field name="email" label="Correo electrónico" type="email" autocomplete="email" />
        <button class="btn btn-light zaza-button w-100" type="submit">Enviar enlace</button>
    </form>
</x-auth-card>
