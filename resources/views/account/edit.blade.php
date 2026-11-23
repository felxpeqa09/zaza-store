<x-store-layout title="Configurar cuenta | ZAZA STORE">
    <div class="container zaza-section">
        <a class="d-inline-flex align-items-center gap-2 mb-4" href="{{ route('account.show') }}">
            <i class="bi bi-arrow-left" aria-hidden="true"></i> Mi cuenta
        </a>
        <x-store-logo class="zaza-account-logo d-block mb-3" alt="" />
        <h1 class="zaza-heading mb-4">Configurar cuenta</h1>

        <div class="zaza-account-settings">
            <section class="zaza-account-panel" aria-labelledby="profile-heading">
                <h2 class="h4 fw-bold mb-4" id="profile-heading">Datos personales</h2>
                @if (session('profile_status'))
                    <p class="zaza-form-status" role="status">{{ session('profile_status') }}</p>
                @endif
                <form method="post" action="{{ route('account.update') }}" enctype="multipart/form-data">
                    @csrf
                    @method('patch')
                    <x-auth-field name="nombre_completo" label="Nombre y apellidos" :value="$profile->nombre_completo" autocomplete="name" />
                    <x-auth-field name="email" label="Correo electrónico" type="email" :value="$profile->email" autocomplete="email" />
                    <x-auth-field name="telefono" label="Teléfono" type="tel" :value="$profile->telefono" autocomplete="tel" />
                    <x-auth-field name="genero" label="Género (opcional)" :value="$profile->genero" :required="false" maxlength="80" />
                    <label class="form-label" for="direccion">Dirección (opcional)</label>
                    <textarea class="form-control zaza-address" id="direccion" name="direccion" rows="2" maxlength="500"
                        autocomplete="street-address" @error('direccion') aria-invalid="true" aria-describedby="direccion-error" @enderror>{{ old('direccion', $profile->direccion) }}</textarea>
                    @error('direccion')<p class="zaza-field-error small mt-1" id="direccion-error">{{ $message }}</p>@enderror
                    <div class="mt-3">
                        <label class="form-label" for="avatar">Foto de perfil</label>
                        <x-account-avatar :profile="$profile" />
                        <input type="file" name="avatar" id="avatar" class="form-control mt-2" accept="image/jpeg,image/png,image/webp"
                            aria-describedby="avatar-help @error('avatar') avatar-error @enderror">
                        <p class="small mt-1 mb-0" id="avatar-help">JPG, PNG o WebP de hasta 2 MB.</p>
                        @error('avatar')<p class="zaza-field-error small mt-1" id="avatar-error">{{ $message }}</p>@enderror
                        @if ($profile->avatar)
                            <label class="d-flex align-items-center gap-2 mt-2"><input type="checkbox" name="quitar_avatar" value="1"> Quitar foto actual</label>
                            @error('quitar_avatar')<p class="zaza-field-error small mt-1">{{ $message }}</p>@enderror
                        @endif
                    </div>
                    <input type="hidden" name="acepta_marketing" value="0">
                    <label class="d-flex align-items-center gap-2 my-3"><input type="checkbox" name="acepta_marketing" value="1"
                        @checked(old('acepta_marketing', $profile->acepta_marketing))> Acepto recibir novedades y promociones</label>
                    @error('acepta_marketing')<p class="zaza-field-error small">{{ $message }}</p>@enderror
                    <button class="btn btn-light zaza-button" type="submit">Guardar datos</button>
                </form>
            </section>

            <section class="zaza-account-panel" id="contrasena" aria-labelledby="password-heading">
                <h2 class="h4 fw-bold mb-4" id="password-heading">Cambiar contraseña</h2>
                @if (session('password_status'))
                    <p class="zaza-form-status" role="status">{{ session('password_status') }}</p>
                @endif
                <form method="post" action="{{ route('password.update') }}">
                    @csrf
                    @method('put')
                    <x-auth-field name="current_password" label="Contraseña actual" type="password" autocomplete="current-password" error-bag="updatePassword" />
                    <x-auth-field name="password" label="Nueva contraseña (mínimo 8 caracteres)" type="password" minlength="8" autocomplete="new-password" error-bag="updatePassword" />
                    <x-auth-field name="password_confirmation" label="Confirmar contraseña" type="password" minlength="8" autocomplete="new-password" error-bag="updatePassword" />
                    <button class="btn btn-light zaza-button" type="submit">Guardar contraseña</button>
                </form>
            </section>
        </div>
    </div>
</x-store-layout>
