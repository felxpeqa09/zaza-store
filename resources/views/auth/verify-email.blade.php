<x-auth-card title="Verifica tu correo">
    <p>Revisa tu bandeja de entrada para verificar tu dirección de correo.</p>
    <form method="post" action="{{ route('verification.send') }}">
        @csrf
        <button class="btn btn-light zaza-button w-100" type="submit">Reenviar enlace</button>
    </form>
</x-auth-card>
