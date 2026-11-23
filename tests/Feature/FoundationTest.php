<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\Product;
use App\Models\User;
use App\Notifications\QueuedPasswordReset;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;
use PragmaRX\Google2FAQRCode\Google2FA;
use Tests\TestCase;

class FoundationTest extends TestCase
{
    public function test_authentication_pages_render(): void
    {
        $this->get('/registro')->assertOk()->assertSee('Nombre y apellidos');
        $this->get('/iniciar-sesion')->assertOk()->assertSee('Iniciar sesión');
    }

    public function test_registration_blocks_excess_attempts_per_ip_and_reopens_after_a_minute(): void
    {
        $this->freezeTime();
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.10']);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/registro')->assertUnprocessable()->assertJsonValidationErrors('email');
        }

        $this->postJson('/registro')->assertTooManyRequests();
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.11'])
            ->postJson('/registro')->assertUnprocessable();

        $this->travel(61)->seconds();
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.10'])
            ->postJson('/registro')->assertUnprocessable();
    }

    public function test_only_administrators_can_access_the_panel_and_catalog_policies(): void
    {
        $this->get('/admin')->assertRedirect(route('filament.admin.auth.login'));

        $user = new User(['nombre_completo' => 'Cliente', 'is_admin' => true]);
        $this->assertFalse($user->is_admin);
        $this->actingAs($user, 'admin')->get('/admin')->assertForbidden();

        foreach ([new Product, new Banner] as $record) {
            foreach (['viewAny', 'create', 'view', 'update', 'delete'] as $ability) {
                $subject = in_array($ability, ['viewAny', 'create'], true) ? $record::class : $record;
                $this->assertFalse(Gate::forUser($user)->allows($ability, $subject));
            }
        }
    }

    public function test_admin_session_is_separate_and_requires_an_authenticator(): void
    {
        $admin = new User(['nombre_completo' => 'Administrador', 'password' => 'clave-de-prueba']);
        $admin->is_admin = true;
        $this->assertTrue(Hash::check('clave-de-prueba', $admin->password));
        $admin->app_authentication_secret = AppAuthentication::make()->generateSecret();

        $this->actingAs($admin, 'web')->get('/admin')->assertRedirect(route('filament.admin.auth.login'));
        Auth::guard('web')->forgetUser();
        $admin->app_authentication_secret = null;

        $this->actingAs($admin, 'admin')->get('/admin')
            ->assertRedirect(route('filament.admin.auth.multi-factor-authentication.set-up-required'));
        $this->get('/admin/products')->assertRedirect(route('filament.admin.auth.multi-factor-authentication.set-up-required'));
        $this->get(route('filament.admin.auth.multi-factor-authentication.set-up-required'))->assertOk();

        $admin->app_authentication_secret = AppAuthentication::make()->generateSecret();
        $this->get('/admin')->assertOk();
    }

    public function test_enrolled_admin_must_complete_the_authenticator_challenge_at_login(): void
    {
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        $this->artisan('migrate', [
            '--path' => [
                'database/migrations/0001_01_01_000000_create_users_table.php',
                'database/migrations/2026_11_23_163024_add_app_authentication_to_users_table.php',
            ],
            '--no-interaction' => true,
        ])->assertSuccessful();

        $admin = User::factory()->create(['password' => 'clave-de-prueba']);
        $admin->is_admin = true;
        $secret = AppAuthentication::make()->generateSecret();
        $admin->saveAppAuthenticationSecret($secret);
        $this->assertNotSame($secret, $admin->fresh()->getRawOriginal('app_authentication_secret'));

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $login = Livewire::test(Login::class)->fillForm([
            'email' => $admin->email,
            'password' => 'clave-de-prueba',
        ])->call('authenticate')->assertHasNoFormErrors();
        $this->assertGuest('admin');

        $authenticator = app(Google2FA::class);
        $login->set('data.multiFactor.app.code', $authenticator->getCurrentOtp($secret))
            ->call('authenticate')->assertHasNoErrors()->assertRedirect();
        $this->assertAuthenticatedAs($admin, 'admin');
        $this->assertGuest('web');
    }

    public function test_recovery_is_generic_throttled_and_uses_a_single_use_token_valid_for_sixty_minutes(): void
    {
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        $this->artisan('migrate', [
            '--path' => ['database/migrations/0001_01_01_000000_create_users_table.php'],
            '--no-interaction' => true,
        ])->assertSuccessful();

        $this->freezeSecond();
        Notification::fake();
        $user = User::factory()->create();
        $notice = 'Comprueba tu bandeja de entrada';
        $this->from('/olvide-mi-contrasena')->post('/olvide-mi-contrasena', ['email' => 'ausente@example.test'])
            ->assertRedirect('/olvide-mi-contrasena')->assertSessionHas('status', $notice);
        Notification::assertNothingSent();
        $this->post('/olvide-mi-contrasena', ['email' => $user->email])
            ->assertRedirect('/olvide-mi-contrasena')->assertSessionHas('status', $notice);
        Notification::assertSentTo($user, QueuedPasswordReset::class, fn (QueuedPasswordReset $notification) => $notification instanceof ShouldBeEncrypted);
        $notification = Notification::sent($user, QueuedPasswordReset::class)->sole();

        for ($attempt = 0; $attempt < 4; $attempt++) {
            $this->post('/olvide-mi-contrasena', ['email' => 'ausente@example.test'])->assertRedirect();
        }
        $this->post('/olvide-mi-contrasena', ['email' => 'ausente@example.test'])->assertTooManyRequests();

        $this->get('/restablecer-contrasena/'.$notification->token.'?email='.$user->email)->assertOk();
        $this->travel(3599)->seconds();
        $payload = [
            'token' => $notification->token, 'email' => $user->email,
            'password' => 'clave-recuperada', 'password_confirmation' => 'clave-recuperada',
        ];
        $this->post('/restablecer-contrasena', $payload)->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('clave-recuperada', $user->fresh()->password));
        $this->post('/restablecer-contrasena', $payload)->assertSessionHasErrors('email');

        $token = Password::broker()->createToken($user);
        $this->travel(3601)->seconds();
        $this->post('/restablecer-contrasena', [...$payload, 'token' => $token])->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('clave-recuperada', $user->fresh()->password));
    }

    public function test_db_health_rejects_invalid_and_empty_secrets(): void
    {
        config(['zaza.health_token' => 'secreto-de-prueba']);
        $this->get('/health/db', ['X-Health-Token' => 'incorrecto'])->assertNotFound();

        config(['zaza.health_token' => '']);
        $this->get('/health/db', ['X-Health-Token' => ''])->assertNotFound();
    }
}
