<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\PostgresTestCase;

class ShoppingTest extends PostgresTestCase
{
    public function test_guest_cart_validates_stock_and_calculates_prices_on_the_server(): void
    {
        $variant = $this->variant();
        $url = '/carrito/items/'.$variant->id;
        $this->postJson('/carrito/items', [
            'variant_id' => $variant->id, 'cantidad' => 2, 'precio' => 1,
        ])->assertOk()->assertJsonPath('units', 2)->assertJsonPath('total', '380000')
            ->assertJsonPath('items.0.subtotal', '$380.000 COP');

        $this->postJson('/carrito/items', ['variant_id' => $variant->id, 'cantidad' => 4])
            ->assertUnprocessable()->assertJsonValidationErrors('cantidad');
        $this->patchJson($url, ['cantidad' => 3])->assertOk()->assertJsonPath('total', '570000');
        $this->assertDatabaseCount('carts', 0);

        $variant->update(['stock' => 2]);
        $this->getJson('/carrito')->assertOk()->assertJsonPath('total', '0')
            ->assertJsonPath('items.0.notice', 'Solo hay 2 unidades disponibles.');
        $this->patchJson($url, ['cantidad' => 2])->assertOk()->assertJsonPath('units', 2);
        $this->deleteJson($url)->assertOk()->assertJsonPath('units', 0);
        $this->getJson('/carrito')->assertOk()->assertJsonCount(0, 'items');
    }

    public function test_login_merges_by_variant_and_reports_stock_adjustments_only_once_per_batch(): void
    {
        $variant = $this->variant();
        $unavailable = $this->variant(name: 'Hoodie');
        $gone = $unavailable->product;
        $gone->delete();

        $user = User::factory()->create();
        $user->cart()->create()->items()->create(['product_variant_id' => $variant->id, 'cantidad' => 2]);
        $guest = [
            'batch' => (string) Str::uuid(), 'items' => [$variant->id => 4, $unavailable->id => 1],
        ];
        $this->withSession([CartService::SESSION_KEY => $guest])->post('/iniciar-sesion', [
            'email' => $user->email, 'password' => 'password',
        ])->assertRedirect('/')->assertSessionMissing(CartService::SESSION_KEY);
        $this->assertAuthenticatedAs($user);
        $this->getJson('/carrito')->assertOk()->assertJsonPath('units', 5)->assertJsonPath('total', '950000')
            ->assertJsonCount(2, 'notices')->assertJsonPath('items.0.size', 'M');
        $this->getJson('/carrito')->assertOk()->assertJsonCount(0, 'notices');

        $this->patchJson('/carrito/items/'.$variant->id, ['cantidad' => 1])->assertOk();
        $this->withSession([CartService::SESSION_KEY => $guest])->postJson('/carrito/items', [
            'variant_id' => $variant->id, 'cantidad' => 1,
        ])->assertOk()->assertJsonPath('units', 2)->assertJsonCount(2, 'notices');
        $this->assertDatabaseCount('cart_merges', 1);
        $this->assertDatabaseCount('cart_items', 1);
    }

    public function test_cart_uses_only_the_authenticated_users_items_and_rejects_inactive_variants(): void
    {
        $variant = $this->variant();
        $inactive = $this->variant(name: 'Inactivo');
        $inactive->product->update(['activo' => false]);
        $customer = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($customer)->postJson('/carrito/items', [
            'variant_id' => $variant->id, 'cantidad' => 1,
        ])->assertOk()->assertJsonPath('units', 1);
        $this->postJson('/carrito/items', ['variant_id' => $inactive->id, 'cantidad' => 1])->assertUnprocessable();
        $this->actingAs($other)->getJson('/carrito')->assertOk()->assertJsonPath('units', 0);
        $this->patchJson('/carrito/items/'.$variant->id, ['cantidad' => 2])->assertNotFound();
        $this->deleteJson('/carrito/items/'.$variant->id)->assertOk();
        $this->actingAs($customer)->getJson('/carrito')->assertOk()->assertJsonPath('units', 1);
    }

    public function test_favorites_require_authentication_are_idempotent_and_hide_inactive_products(): void
    {
        $variant = $this->variant();
        $product = $variant->product;
        $url = '/favoritos/'.$product->id;
        $this->get('/favoritos')->assertRedirect(route('login'));
        $this->putJson($url)->assertUnauthorized();

        $customer = User::factory()->create();
        $this->actingAs($customer)->putJson($url)->assertOk()->assertJsonPath('saved', true);
        $this->putJson($url)->assertOk();
        $this->assertDatabaseCount('wishlists', 1);
        $this->get('/favoritos')->assertOk()->assertSee('Camisa de prueba');
        $this->getJson('/favoritos')->assertOk()->assertJsonPath('ids.0', (string) $product->id);
        $this->actingAs(User::factory()->create())->getJson('/favoritos')->assertOk()->assertJsonCount(0, 'ids');
        $this->deleteJson($url)->assertOk();
        $this->assertDatabaseCount('wishlists', 1);

        $product->update(['activo' => false]);
        $this->actingAs($customer)->getJson('/favoritos')->assertOk()->assertJsonCount(0, 'ids');
        $this->putJson($url)->assertNotFound();
        $this->deleteJson($url)->assertOk();
        $this->assertDatabaseCount('wishlists', 0);
    }

    public function test_totals_remain_exact_for_large_cop_values_and_merge_receipts_have_rls(): void
    {
        $variant = $this->variant(price: PHP_INT_MAX);
        $this->postJson('/carrito/items', ['variant_id' => $variant->id, 'cantidad' => 2])
            ->assertOk()->assertJsonPath('total', '18446744073709551614')
            ->assertJsonPath('totalLabel', '$18.446.744.073.709.551.614 COP');

        $this->assertTrue(DB::table('pg_class')->where('relname', 'cart_merges')->where('relrowsecurity', true)->exists());
    }

    private function variant(int $price = 190000, string $name = 'Camisa de prueba'): ProductVariant
    {
        $category = Category::firstOrCreate(['slug' => 'ropa'], ['nombre' => 'ROPA']);
        $product = Product::create([
            'category_id' => $category->id, 'nombre' => $name, 'slug' => Str::slug($name),
            'descripcion' => 'Descripción', 'precio' => $price, 'activo' => true,
        ]);

        return $product->variants()->create(['talla' => 'M', 'stock' => 5]);
    }
}
