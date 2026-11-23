<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $data = config('zaza.admin');

        if (blank($data['email']) && blank($data['name']) && blank($data['password'])) {
            $this->command?->warn('Configura ADMIN_EMAIL, ADMIN_NAME y ADMIN_PASSWORD para crear el administrador.');

            return;
        }

        $email = strtolower(trim((string) $data['email']));
        Validator::make(['email' => $email] + $data, ['email' => 'required|email|max:255'])->validate();

        $existing = User::query()->whereRaw('lower(email) = ?', [$email])->first();
        if ($existing) {
            if (! $existing->is_admin) {
                throw new RuntimeException('ADMIN_EMAIL pertenece a una cuenta existente sin permisos administrativos.');
            }

            return;
        }

        Validator::make($data, ['name' => 'required|string|max:255', 'password' => 'required|string|min:8'])->validate();

        $admin = new User([
            'nombre_completo' => $data['name'],
            'email' => $email,
            'password' => $data['password'],
        ]);
        $admin->is_admin = true;
        $admin->email_verified_at = now();
        $admin->save();
    }
}
