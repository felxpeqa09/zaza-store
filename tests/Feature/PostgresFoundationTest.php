<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\PostgresTestCase;

class PostgresFoundationTest extends PostgresTestCase
{
    public function test_every_public_table_has_rls_and_the_connection_is_the_owner(): void
    {

        $unsafeTables = DB::select(<<<'SQL'
            SELECT c.relname
            FROM pg_class AS c
            JOIN pg_namespace AS n ON n.oid = c.relnamespace
            WHERE n.nspname = 'public' AND c.relkind IN ('r', 'p')
              AND (NOT c.relrowsecurity OR c.relforcerowsecurity OR pg_get_userbyid(c.relowner) <> current_user)
            SQL);

        $this->assertSame([], $unsafeTables);
        $this->assertFalse(DB::table('pg_policies')->where('schemaname', 'public')->exists());
    }

    public function test_public_registration_cannot_assign_admin_role(): void
    {
        $this->post('/registro', [
            'nombre_completo' => 'Cliente de prueba',
            'email' => 'cliente@example.test',
            'telefono' => '3000000000',
            'password' => 'clave-de-prueba',
            'password_confirmation' => 'clave-de-prueba',
            'is_admin' => true,
        ])->assertRedirect('/');

        $this->assertDatabaseHas('users', ['email' => 'cliente@example.test', 'is_admin' => false]);
    }

    public function test_health_records_valid_pings(): void
    {
        config(['zaza.health_token' => 'secreto-de-prueba']);
        $this->get('/health/db', ['X-Health-Token' => 'secreto-de-prueba'])->assertOk()->assertJson(['status' => 'ok']);
        $this->assertDatabaseHas('keepalive_pings', ['source' => 'http']);
    }

    public function test_admin_seeding_preserves_existing_credentials_and_rejects_conflicts(): void
    {
        config(['zaza.admin' => [
            'email' => 'admin@example.test', 'name' => 'Administración', 'password' => 'contraseña-inicial',
        ]]);
        $this->seed(AdminSeeder::class);
        config(['zaza.admin.password' => 'contraseña-distinta']);
        $this->seed(AdminSeeder::class);

        $admin = User::where('email', 'admin@example.test')->firstOrFail();
        $this->assertTrue($admin->is_admin);
        $this->assertTrue(Hash::check('contraseña-inicial', $admin->password));

        $customer = User::factory()->create();
        config(['zaza.admin.email' => $customer->email]);
        $this->expectException(RuntimeException::class);
        $this->seed(AdminSeeder::class);
    }
}
