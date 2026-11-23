<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\CheckoutService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Tests\PostgresTestCase;

class CheckoutTest extends PostgresTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'app.url' => 'https://zaza.example.test',
            'mercadopago.environment' => 'test',
            'mercadopago.access_token' => 'token-simulado',
            'mercadopago.webhook_secret' => 'firma-simulada',
        ]);
        Http::preventStrayRequests();
    }

    public function test_checkout_uses_cop_snapshots_and_recovers_a_lost_preference_without_reserving_twice(): void
    {
        Http::fake(['https://api.mercadopago.com/checkout/preferences' => Http::sequence()
            ->pushStatus(503)->push($this->preference())]);
        [$user, $variant] = $this->cart();
        $shipping = $this->shipping();
        $this->actingAs($user)->get('/checkout')->assertOk()->assertSee('Pagar con Mercado Pago')->assertSee('$190.000 COP');
        $this->post('/checkout', $shipping + ['total' => 1])->assertRedirect('/checkout');
        $order = Order::sole();
        $this->assertNull($order->provider_preference_id);
        $this->assertSame(0, $variant->fresh()->stock);

        $this->post('/checkout', $shipping)->assertStatus(303)->assertRedirect($this->preference()['sandbox_init_point']);
        $this->post('/checkout', $shipping)->assertStatus(303)->assertRedirect($this->preference()['sandbox_init_point']);
        $this->post('/checkout', [...$shipping, 'checkout_token' => (string) Str::uuid()])->assertStatus(303);
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 1);
        $this->assertSame('PREF-TEST', $order->fresh()->provider_preference_id);
        $this->assertSame(190000, $order->total);
        $this->assertSame(0, $variant->fresh()->stock);
        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => $request['items'][0]['unit_price'] === 190000
            && $request['items'][0]['currency_id'] === 'COP'
            && $request['external_reference'] === $order->referencia
            && str_starts_with($request['notification_url'], 'https://zaza.example.test/webhooks/mercadopago')
            && $request['back_urls']['success'] === 'https://zaza.example.test/pedidos/'.$order->id.'/resultado'
            && $request['expires'] === true);
    }

    public function test_browser_return_does_not_accredit_and_order_access_is_scoped(): void
    {
        [$user] = $this->cart();
        $this->get('/checkout')->assertRedirect(route('login'));
        $unverified = User::factory()->unverified()->create();
        $this->actingAs($unverified)->get('/checkout')->assertRedirect(route('verification.notice'));
        $order = $this->order($user);
        $this->actingAs($user)->get(route('orders.result', $order).'?status=approved&payment_id=1')
            ->assertOk()->assertSee('Estamos verificando tu pago');
        $this->assertSame('PENDIENTE', $order->fresh()->estado);
        $other = User::factory()->create();
        $this->actingAs($other)->get(route('orders.show', $order))->assertForbidden();
        $this->get(route('orders.result', $order))->assertForbidden();
        $other->is_admin = true;
        $this->get(route('orders.show', $order))->assertOk()->assertSee('Camisa de prueba');
        Http::assertNothingSent();
    }

    public function test_webhook_checks_signature_reference_amount_currency_and_receiver(): void
    {
        [$user] = $this->cart();
        $order = $this->order($user);
        Http::fake([
            'https://api.mercadolibre.com/users/me' => Http::response(['id' => 72]),
            'https://api.mercadopago.com/v1/payments/100' => Http::sequence()
                ->push($this->payment($order, extra: ['external_reference' => 'no-coincide']))
                ->push($this->payment($order, extra: ['transaction_amount' => 1900]))
                ->push($this->payment($order, extra: ['transaction_amount' => 190000.01]))
                ->push($this->payment($order, extra: ['currency_id' => 'USD']))
                ->push($this->payment($order, extra: ['collector_id' => 99])),
        ]);
        $this->notification($order, signature: 'incorrecta')->assertUnauthorized();
        Http::assertNothingSent();
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->notification($order)->assertOk();
        }
        $this->assertSame('PENDIENTE', $order->fresh()->estado);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('jobs', 0);
    }

    public function test_pending_payment_can_be_approved_and_duplicates_enqueue_one_confirmation(): void
    {
        [$user, $variant] = $this->cart();
        $order = $this->order($user);
        Http::fake([
            'https://api.mercadolibre.com/users/me' => Http::response(['id' => 72]),
            'https://api.mercadopago.com/v1/payments/100' => Http::sequence()
                ->push($this->payment($order, 'pending'))
                ->push($this->payment($order))
                ->push($this->payment($order)),
            'https://api.mercadopago.com/v1/payments/101' => Http::response($this->payment($order, 'rejected', '101')),
            'https://api.mercadopago.com/v1/payments/102' => Http::response($this->payment($order, 'approved', '102')),
        ]);
        $this->notification($order)->assertOk();
        $this->assertSame('PENDIENTE', $order->fresh()->estado);
        $this->notification($order)->assertOk();
        $this->notification($order)->assertOk();
        $this->notification($order, '101')->assertOk();
        $this->notification($order, '102')->assertOk();

        $this->assertSame('PAGADO', $order->fresh()->estado);
        $this->assertSame('100', $order->fresh()->approved_payment_id);
        $this->assertSame(0, $variant->fresh()->stock);
        $this->assertDatabaseCount('jobs', 1);
        $this->assertDatabaseCount('payments', 3);
        $this->assertArrayNotHasKey('card', Payment::query()->where('provider_payment_id', '100')->sole()->raw_payload);
        $this->assertSame('approved', Payment::query()->where('provider_payment_id', '100')->sole()->estado);
        $this->assertSame('aprobacion_adicional', Payment::query()->where('provider_payment_id', '102')->sole()->raw_payload['conciliacion']);
        $this->actingAs($user)->get(route('orders.result', $order))->assertOk()->assertSee('Pago confirmado');
    }

    public function test_expiration_releases_once_and_late_approval_requires_reconciliation(): void
    {
        [$user, $variant] = $this->cart();
        $order = $this->order($user);
        $this->travel(31)->minutes();
        Http::fake([
            'https://api.mercadopago.com/v1/payments/search*' => Http::response(['results' => [], 'paging' => ['total' => 0]]),
            'https://api.mercadolibre.com/users/me' => Http::response(['id' => 72]),
            'https://api.mercadopago.com/v1/payments/100' => Http::response($this->payment($order)),
        ]);
        $this->artisan('orders:release-expired')->assertSuccessful();
        $this->artisan('orders:release-expired')->assertSuccessful();
        $this->assertSame(1, $variant->fresh()->stock);
        $this->notification($order)->assertOk();
        $this->assertSame('EXPIRADO', $order->fresh()->estado);
        $this->assertSame('reserva_liberada', Payment::sole()->raw_payload['conciliacion']);
        $this->assertDatabaseCount('jobs', 0);
        $this->assertSame(1, $variant->fresh()->stock);
    }

    public function test_expiration_queries_known_and_discovered_attempts_before_releasing(): void
    {
        [$user, $variant] = $this->cart();
        $order = $this->order($user);
        Http::fake([
            'https://api.mercadopago.com/v1/payments/search*' => Http::response(['results' => [['id' => 101]], 'paging' => ['total' => 1]]),
            'https://api.mercadolibre.com/users/me' => Http::response(['id' => 72]),
            'https://api.mercadopago.com/v1/payments/100' => Http::sequence()
                ->push($this->payment($order, 'pending'))->push($this->payment($order, 'rejected')),
            'https://api.mercadopago.com/v1/payments/101' => Http::response($this->payment($order, id: '101')),
        ]);
        $this->notification($order)->assertOk();
        $this->travel(31)->minutes();
        $this->artisan('orders:release-expired')->assertSuccessful();
        $this->assertSame('PAGADO', $order->fresh()->estado);
        $this->assertSame(0, $variant->fresh()->stock);
        $this->assertDatabaseCount('jobs', 1);
    }

    public function test_provider_unavailability_keeps_stock_reserved_until_reconciliation_can_run(): void
    {
        [$user, $variant] = $this->cart();
        $order = $this->order($user);
        $this->travel(31)->minutes();
        Http::fake([
            'https://api.mercadopago.com/v1/payments/search*' => Http::sequence()
                ->pushStatus(503)->push(['results' => [], 'paging' => ['total' => 0]]),
        ]);

        $this->artisan('orders:release-expired')->assertFailed();
        $this->assertSame('PENDIENTE', $order->fresh()->estado);
        $this->assertSame(0, $variant->fresh()->stock);
        $this->artisan('orders:release-expired')->assertSuccessful();
        $this->assertSame('EXPIRADO', $order->fresh()->estado);
        $this->assertSame(1, $variant->fresh()->stock);
    }

    public function test_concurrent_checkouts_cannot_both_reserve_the_last_unit(): void
    {
        [$first, $variant] = $this->cart();
        $second = User::factory()->create();
        $second->cart()->create()->items()->create(['product_variant_id' => $variant->id, 'cantidad' => 1]);
        $script = <<<'CODE'
            require 'vendor/autoload.php';
            $app = require 'bootstrap/app.php';
            $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
            config(['app.url' => 'https://zaza.example.test', 'mercadopago.access_token' => 'fake', 'mercadopago.webhook_secret' => 'fake']);
            try {
                $app->make(App\Services\CheckoutService::class)->start(App\Models\User::findOrFail($argv[1]), Illuminate\Support\Str::uuid()->toString(), []);
                echo 'reserved';
            } catch (Illuminate\Validation\ValidationException) {
                echo 'unavailable';
            }
            CODE;
        $database = config('database.connections.pgsql');
        $env = [
            'APP_ENV' => 'testing', 'DB_CONNECTION' => 'pgsql', 'DB_URL' => '',
            'DB_HOST' => $database['host'], 'DB_PORT' => (string) $database['port'],
            'DB_DATABASE' => $database['database'], 'DB_USERNAME' => $database['username'],
            'DB_PASSWORD' => $database['password'], 'DB_SSLMODE' => $database['sslmode'],
        ];
        $firstProcess = new Process([PHP_BINARY, '-r', $script, (string) $first->id], base_path(), $env);
        $secondProcess = new Process([PHP_BINARY, '-r', $script, (string) $second->id], base_path(), $env);
        $firstProcess->start();
        $secondProcess->start();
        $firstProcess->wait();
        $secondProcess->wait();
        $this->assertTrue($firstProcess->isSuccessful(), $firstProcess->getErrorOutput());
        $this->assertTrue($secondProcess->isSuccessful(), $secondProcess->getErrorOutput());
        $this->assertEqualsCanonicalizing(['reserved', 'unavailable'], [$firstProcess->getOutput(), $secondProcess->getOutput()]);
        $this->assertDatabaseCount('orders', 1);
        $this->assertSame(0, $variant->fresh()->stock);
    }

    private function order(User $user): Order
    {
        $shipping = $this->shipping();

        return app(CheckoutService::class)->start($user, $shipping['checkout_token'], $shipping);
    }

    private function cart(): array
    {
        $user = User::factory()->create();
        $category = Category::create(['nombre' => 'ROPA', 'slug' => 'ropa']);
        $product = $category->products()->create([
            'nombre' => 'Camisa de prueba', 'slug' => 'camisa-de-prueba', 'descripcion' => 'Camisa',
            'precio' => 190000, 'activo' => true,
        ]);
        $variant = $product->variants()->create(['stock' => 1, 'talla' => 'M']);
        $user->cart()->create()->items()->create(['product_variant_id' => $variant->id, 'cantidad' => 1]);

        return [$user, $variant];
    }

    private function shipping(): array
    {
        return ['checkout_token' => (string) Str::uuid(), 'nombre_completo' => 'Comprador', 'telefono' => '3001234567',
            'direccion' => 'Cra 7', 'ciudad' => 'Ibagué', 'departamento' => 'Tolima'];
    }

    private function preference(): array
    {
        return ['id' => 'PREF-TEST', 'sandbox_init_point' => 'https://sandbox.mercadopago.com.co/checkout/v1/redirect?pref_id=PREF-TEST'];
    }

    private function payment(Order $order, string $status = 'approved', string $id = '100', array $extra = []): array
    {
        return $extra + [
            'id' => (int) $id, 'external_reference' => $order->referencia, 'status' => $status,
            'transaction_amount' => 190000, 'currency_id' => 'COP', 'collector_id' => 72,
            'date_last_updated' => now()->toIso8601String(), 'date_approved' => $status === 'approved' ? now()->toIso8601String() : null,
            'payment_method_id' => 'visa', 'payment_type_id' => 'credit_card',
            'card' => ['last_four_digits' => '1234'],
        ];
    }

    private function notification(Order $order, string $id = '100', ?string $signature = null)
    {
        $ts = (string) now()->timestamp;
        $requestId = 'prueba-webhook';
        $signature ??= hash_hmac('sha256', "id:{$id};request-id:{$requestId};ts:{$ts};", 'firma-simulada');

        return $this->postJson('/webhooks/mercadopago?data.id='.$id.'&type=payment', [
            'type' => 'payment', 'data' => ['id' => '999'], 'external_reference' => $order->referencia,
        ], ['x-signature' => "ts={$ts},v1={$signature}", 'x-request-id' => $requestId]);
    }
}
