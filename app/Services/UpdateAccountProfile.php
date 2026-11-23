<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class UpdateAccountProfile
{
    public function update(User $account, array $attributes, ?UploadedFile $avatar, bool $removeAvatar): void
    {
        $path = $avatar ? $avatar->store('avatars', 'local') : null;
        if ($path === false) {
            throw ValidationException::withMessages(['avatar' => 'No se pudo guardar la imagen. Inténtalo de nuevo.']);
        }

        try {
            [$user, $oldAvatar] = DB::transaction(fn () => $this->save($account, $attributes, $path, $removeAvatar), 3);
        } catch (Throwable $error) {
            if ($path !== null) {
                Storage::disk('local')->delete($path);
            }
            if ($error instanceof UniqueConstraintViolationException) {
                throw ValidationException::withMessages(['email' => 'Ese correo ya está en uso.']);
            }
            throw $error;
        }

        if ($oldAvatar !== null && str_starts_with($oldAvatar, 'avatars/')) {
            Storage::disk('local')->delete($oldAvatar);
        }
        if ($user->wasChanged('email')) {
            $user->sendEmailVerificationNotification();
        }
    }

    private function save(User $account, array $attributes, ?string $path, bool $removeAvatar): array
    {
        $user = User::query()->whereKey($account->getKey())->lockForUpdate()->firstOrFail();
        $oldAvatar = null;
        $user->fill(Arr::only($attributes, ['nombre_completo', 'email', 'telefono', 'genero', 'direccion', 'acepta_marketing']));
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }
        if ($path !== null || $removeAvatar) {
            $oldAvatar = $user->avatar;
            $user->avatar = $path;
        }
        $user->save();

        return [$user, $oldAvatar];
    }
}
