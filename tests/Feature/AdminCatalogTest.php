<?php

namespace Tests\Feature;

use App\Filament\Resources\Banners\Pages\CreateBanner;
use App\Filament\Resources\Banners\Pages\EditBanner;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\PostgresTestCase;

class AdminCatalogTest extends PostgresTestCase
{
    public function test_administrator_can_create_read_edit_and_soft_delete_a_product_from_filament(): void
    {
        $this->admin();
        Storage::fake('public');
        $category = Category::create(['nombre' => 'ROPA', 'slug' => 'ropa']);
        $data = $this->productData($category);

        Livewire::test(CreateProduct::class)->fillForm($data)->call('create')->assertHasNoFormErrors();
        $product = Product::sole();
        $variant = $product->variants()->sole();
        $image = $product->images()->sole()->ruta;
        Storage::disk('public')->assertExists($image);
        $this->assertSame('Negro', $variant->color->nombre);
        $this->assertSame(5, $variant->stock);
        Livewire::test(ListProducts::class)->searchTable('camisa-filament')->assertCanSeeTableRecords([$product]);
        $this->get('/admin/products/'.$product->id)->assertOk()->assertSee('Camisa Filament')->assertSee('Negro');
        $this->get('/ropa')->assertOk()->assertSee('Camisa Filament');
        $this->get('/producto/camisa-filament')->assertOk()->assertSee('$190.000 COP');

        $page = Livewire::test(EditProduct::class, ['record' => $product->id]);
        $variants = $page->get('data.variants');
        $variants[array_key_first($variants)]['stock'] = 2;
        $page->fillForm(['precio' => 210000, 'variants' => $variants])->call('save')->assertHasNoFormErrors();
        $this->assertSame(210000, $product->fresh()->precio);
        $this->assertSame(2, $variant->fresh()->stock);
        $this->get('/producto/camisa-filament')->assertOk()->assertSee('$210.000 COP');

        $page->mountAction('delete')->assertActionMounted('delete');
        $this->assertNotSoftDeleted($product);
        $page->callMountedAction()->assertHasNoActionErrors();
        $this->assertSoftDeleted($product);
        $this->get('/producto/camisa-filament')->assertNotFound();
        $this->get('/ropa')->assertOk()->assertDontSee('Camisa Filament');
        $this->getJson('/buscar?q=camisa+filament')->assertJsonCount(0, 'results');
        $this->postJson('/carrito/items', ['variant_id' => $variant->id, 'cantidad' => 1])->assertUnprocessable();
        Storage::disk('public')->assertExists($image);
    }

    public function test_product_form_rejects_duplicate_sizes_and_rolls_back_media_and_stock_changes(): void
    {
        $this->admin();
        Storage::fake('public');
        $category = Category::create(['nombre' => 'ROPA', 'slug' => 'ropa']);
        $data = $this->productData($category);
        $data['variants'][] = $data['variants'][0];

        Livewire::test(CreateProduct::class)->fillForm($data)->call('create')->assertHasFormErrors(['variants']);
        $this->assertDatabaseCount('products', 0);
        $this->assertSame([], Storage::disk('public')->allFiles('catalog/products'));
    }

    public function test_invalid_price_subcategory_and_file_path_are_rejected_by_the_resource(): void
    {
        $this->get('/admin/products/create')->assertRedirect(route('filament.admin.auth.login'));
        $this->admin();
        Storage::fake('public');
        $clothes = Category::create(['nombre' => 'ROPA', 'slug' => 'ropa']);
        $shoes = Category::create(['nombre' => 'ZAPATOS', 'slug' => 'zapatos']);
        $subcategory = $shoes->subcategories()->create(['nombre' => 'Tenis', 'slug' => 'tenis']);

        $valid = $this->productData($clothes);
        Livewire::test(CreateProduct::class)->fillForm([...$valid, 'precio' => -1, 'subcategory_id' => $subcategory->id])
            ->call('create')->assertHasFormErrors(['precio', 'subcategory_id']);
        Livewire::test(CreateProduct::class)->fillForm([...$valid, 'gallery' => ['catalog/products/not-owned.png']])
            ->call('create')->assertHasFormErrors(['gallery']);
        $this->assertDatabaseCount('products', 0);
    }

    public function test_banner_changes_are_reflected_in_catalog_and_customer_cannot_access_resources(): void
    {
        $this->admin();
        Storage::fake('public');
        Category::create(['nombre' => 'ROPA', 'slug' => 'ropa']);
        Livewire::test(CreateBanner::class)->fillForm([
            'imagen' => $this->image(), 'texto' => 'Banner Filament', 'enlace' => '/ropa', 'orden' => 0, 'activo' => true,
        ])->call('create')->assertHasNoFormErrors();
        $banner = Banner::sole();
        Storage::disk('public')->assertExists($banner->imagen);
        $this->get('/ropa')->assertOk()->assertSee('Banner Filament');
        Livewire::test(EditBanner::class, ['record' => $banner->id])->fillForm(['activo' => false])
            ->call('save')->assertHasNoFormErrors();
        $this->get('/ropa')->assertOk()->assertDontSee('Banner Filament');

        $this->actingAs(User::factory()->create(), 'admin');
        foreach (['/admin/products', '/admin/products/create', '/admin/banners', '/admin/banners/'.$banner->id.'/edit'] as $url) {
            $this->get($url)->assertForbidden();
        }
    }

    public function test_reserved_variant_keeps_its_stock_and_order_snapshot_when_the_product_is_edited_or_removed(): void
    {
        $this->admin();
        Storage::fake('public');
        $category = Category::create(['nombre' => 'ROPA', 'slug' => 'ropa']);
        Livewire::test(CreateProduct::class)->fillForm($this->productData($category))->call('create')->assertHasNoFormErrors();
        $product = Product::sole();
        $variant = $product->variants()->sole();

        $page = Livewire::test(EditProduct::class, ['record' => $product->id]);
        $customer = User::factory()->create();
        $order = $customer->orders()->create([
            'referencia' => 'ZAZA-HISTORICO', 'estado' => 'PENDIENTE', 'total' => 380000,
            'datos_envio' => [], 'stock_reserved_until' => now()->addMinutes(30),
        ]);
        $order->items()->create([
            'product_variant_id' => $variant->id, 'nombre' => $product->nombre, 'color' => 'Negro',
            'talla' => 'M', 'cantidad' => 2, 'precio_unitario' => 190000,
        ]);
        $variant->decrement('stock', 2);

        $page->fillForm(['precio' => 230000])->call('save')->assertHasNoFormErrors();
        $this->assertSame(3, $variant->fresh()->stock);
        $rows = $page->get('data.variants');
        $rows[array_key_first($rows)]['size'] = 'XL';
        $page->fillForm(['variants' => $rows])->call('save')->assertHasFormErrors(['variants']);
        $this->assertSame('M', $variant->fresh()->talla);
        $page->mountAction('delete')->callMountedAction();
        $this->assertSoftDeleted($product);
        $this->assertSame(190000, $order->items()->sole()->precio_unitario);
        $this->actingAs(Filament::auth()->user(), 'web')->get('/pedidos/'.$order->id)
            ->assertOk()->assertSee('Camisa Filament')->assertSee('$190.000 COP');
    }

    private function admin(): void
    {
        $administrator = User::factory()->create();
        $administrator->is_admin = true;
        $administrator->saveAppAuthenticationSecret(AppAuthentication::make()->generateSecret());
        $this->actingAs($administrator, 'admin');
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    private function productData(Category $category): array
    {
        return [
            'nombre' => 'Camisa Filament', 'slug' => 'camisa-filament', 'descripcion' => 'Producto de prueba',
            'precio' => 190000, 'category_id' => $category->id, 'activo' => true, 'destacado' => false,
            'needs_price_review' => false, 'gallery' => [$this->image()],
            'colors' => [['key' => 'negro-test', 'name' => 'Negro']],
            'variants' => [['color_key' => 'negro-test', 'size' => 'M', 'stock' => 5]],
        ];
    }

    private function image(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('catalogo.png', base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4z8DwHwAFgAI/ScLttAAAAABJRU5ErkJggg==',
        ));
    }
}
