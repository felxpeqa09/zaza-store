<x-auth-card title="Confirma tu contraseña">
    <form method="post" action="{{ route('password.confirm') }}">
        @csrf
        <x-auth-field name="password" label="Contraseña" type="password" autocomplete="current-password" />
        <button class="btn btn-light zaza-button w-100" type="submit">Confirmar</button>
    </form>
</x-auth-card>
