<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\QueuedEmailVerification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\PostgresTestCase;

class AccountTest extends PostgresTestCase
{
    public function test_account_shows_only_own_information_and_admin_navigation_is_scoped_to_role(): void
    {
        $user = User::factory()->create(['nombre_completo' => 'Cliente de prueba']);
        $admin = User::factory()->create();
        $admin->is_admin = true;
        $admin->save();
        $this->order($user, 'PEDIDO-PROPIO');
        $this->order($admin, 'PEDIDO-AJENO');

        $this->get('/cuenta')->assertRedirect(route('login'));
        $this->actingAs($user)->get('/cuenta')->assertOk()->assertSee('Cliente de prueba')
            ->assertSee('PEDIDO-PROPIO')->assertDontSee('PEDIDO-AJENO')
            ->assertDontSee('Panel de administración')->assertDontSee($user->password);
        $this->actingAs($admin)->get('/cuenta')->assertOk()->assertSee('Panel de administración')
            ->assertSee('PEDIDO-AJENO')->assertDontSee('PEDIDO-PROPIO');
    }

    public function test_profile_changes_are_allowlisted_and_email_changes_require_verification(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $password = $user->password;

        $this->actingAs($user)->patch('/cuenta/configuracion', [
            ...$this->profile($user),
            'email' => 'NUEVO@example.test', 'direccion' => 'Calle de ejemplo',
            'acepta_marketing' => true, 'is_admin' => true, 'password' => 'extra-no-permitido',
        ])->assertRedirect(route('account.edit'));

        $user->refresh();
        $this->assertFalse($user->is_admin);
        $this->assertSame($password, $user->password);
        $this->assertSame('nuevo@example.test', $user->email);
        $this->assertSame('Calle de ejemplo', $user->direccion);
        $this->assertTrue($user->acepta_marketing);
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, QueuedEmailVerification::class);

        $taken = User::factory()->create();
        $this->patch('/cuenta/configuracion', [...$this->profile($user), 'email' => $taken->email])
            ->assertSessionHasErrors('email');
        $this->assertSame('nuevo@example.test', $user->fresh()->email);
    }

    public function test_avatar_is_private_and_can_be_replaced_or_removed(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($user)->patch('/cuenta/configuracion', [
            ...$this->profile($user), 'avatar' => $this->image(),
        ])->assertRedirect(route('account.edit'))->assertSessionHasNoErrors();
        $oldPath = $user->fresh()->avatar;
        Storage::disk('local')->assertExists($oldPath);
        $this->actingAs($user->fresh())->get('/cuenta/avatar')->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->actingAs($other)->get('/cuenta/avatar')->assertNotFound();
        $this->actingAs($user)->patch('/cuenta/configuracion', [
            ...$this->profile($user), 'avatar' => $this->image(),
        ])->assertRedirect(route('account.edit'))->assertSessionHasNoErrors();
        $replacement = $user->fresh()->avatar;
        Storage::disk('local')->assertMissing($oldPath);
        Storage::disk('local')->assertExists($replacement);

        $this->patch('/cuenta/configuracion', [...$this->profile($user), 'quitar_avatar' => '1'])
            ->assertRedirect(route('account.edit'));
        Storage::disk('local')->assertMissing($replacement);
        $this->assertNull($user->fresh()->avatar);
    }

    public function test_password_change_requires_current_password_and_keeps_current_session(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/cuenta/configuracion')->assertOk();
        $payload = [
            'current_password' => 'incorrecta', 'password' => 'nueva-clave-2026',
            'password_confirmation' => 'nueva-clave-2026',
        ];
        $this->put('/password', $payload)->assertSessionHasErrorsIn('updatePassword', 'current_password');
        $this->assertTrue(Hash::check('password', $user->fresh()->password));

        $this->put('/password', [...$payload, 'current_password' => 'password'])
            ->assertRedirect(route('account.edit').'#contrasena');
        $this->assertTrue(Hash::check('nueva-clave-2026', $user->fresh()->password));
        $this->get('/cuenta')->assertOk()->assertDontSee('nueva-clave-2026');
    }

    public function test_configured_recaptcha_checks_provider_result_and_hostname_before_login(): void
    {
        config([
            'zaza.recaptcha.enabled' => true,
            'zaza.recaptcha.site_key' => 'sitio-de-prueba',
            'zaza.recaptcha.secret' => 'secreto-simulado',
            'zaza.recaptcha.hostname' => 'zaza.example.test',
        ]);
        $google = 'https://www.google.com/recaptcha/api/siteverify';
        Http::preventStrayRequests();
        Http::fake([$google => Http::sequence()
            ->push(['success' => true, 'hostname' => 'otro.example.test'])
            ->push(['success' => true, 'hostname' => 'zaza.example.test'])]);

        $user = User::factory()->create();
        $credentials = ['email' => $user->email, 'password' => 'password'];
        $this->get('/iniciar-sesion')->assertOk()->assertSee('sitio-de-prueba')->assertDontSee('secreto-simulado');
        $this->post('/iniciar-sesion', $credentials)->assertSessionHasErrors('g-recaptcha-response');
        Http::assertNothingSent();
        $this->post('/iniciar-sesion', $credentials + ['g-recaptcha-response' => 'respuesta-simulada'])
            ->assertSessionHasErrors('g-recaptcha-response');
        $this->assertGuest();
        $this->post('/iniciar-sesion', $credentials + ['g-recaptcha-response' => 'respuesta-simulada'])
            ->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
        Http::assertSent(fn ($request) => $request->url() === $google
            && $request['response'] === 'respuesta-simulada' && $request['secret'] === 'secreto-simulado');
    }

    private function profile(User $user): array
    {
        return ['nombre_completo' => $user->nombre_completo, 'email' => $user->email, 'telefono' => '3000000000'];
    }

    private function image(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('avatar.png', base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4z8DwHwAFgAI/ScLttAAAAABJRU5ErkJggg==',
        ));
    }

    private function order(User $user, string $reference): void
    {
        $user->orders()->create(['referencia' => $reference, 'estado' => 'PAGADO', 'total' => 190000, 'datos_envio' => []]);
    }
}
