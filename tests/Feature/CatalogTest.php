<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use Tests\PostgresTestCase;

class CatalogTest extends PostgresTestCase
{
    public function test_home_shows_only_four_active_featured_products(): void
    {
        $category = Category::create(['nombre' => 'ROPA', 'slug' => 'ropa']);
        $this->product($category, 'Primer destacado', ['destacado' => true]);
        foreach (range(1, 4) as $number) {
            $this->product($category, "Prenda visible {$number}", ['destacado' => true]);
        }
        $this->product($category, 'Prenda regular');
        $this->product($category, 'Prenda inactiva', ['activo' => false, 'destacado' => true]);
        $this->product($category, 'Prenda retirada', ['destacado' => true])->delete();

        $this->get('/')->assertOk()->assertSee('Prenda visible 4')
            ->assertSee('Prenda visible 1')->assertDontSee('Primer destacado')
            ->assertDontSee('Prenda regular')->assertDontSee('Prenda inactiva')->assertDontSee('Prenda retirada')
            ->assertViewHas('featured', fn ($items) => $items->count() === 4);
    }

    public function test_catalog_filters_and_paginates_with_query_string(): void
    {
        $category = Category::create(['nombre' => 'ROPA', 'slug' => 'ropa']);
        $subcategory = $category->subcategories()->create(['nombre' => 'Camisas', 'slug' => 'camisas']);

        foreach (range(1, 13) as $number) {
            $product = $this->product($category, "Camiseta {$number}", [
                'precio' => 100000 + $number * 1000, 'subcategory_id' => $subcategory->id,
            ]);
            $product->variants()->create(['talla' => 'M', 'stock' => 5]);
        }

        $this->product($category, 'Sin subcategoría');
        $soldOut = $this->product($category, 'Agotada', ['subcategory_id' => $subcategory->id]);
        $soldOut->variants()->create(['talla' => 'M', 'stock' => 0]);
        Banner::create(['imagen' => 'banner.jpg', 'texto' => 'Oculto', 'activo' => false, 'orden' => 0]);
        Banner::create(['imagen' => 'banner.jpg', 'texto' => 'Banner principal', 'enlace' => '/ropa', 'activo' => true, 'orden' => 1]);

        $filters = '/ropa?subcategoria=camisas&precio_min=100000&precio_max=130000&talla=M&orden=menor-precio';
        $this->get($filters)->assertOk()->assertSee('Banner principal')->assertDontSee('Oculto')
            ->assertViewHas('products', fn ($page) => $page->total() === 13
                && $page->count() === 12
                && $page->items()[0]['name'] === 'Camiseta 1'
                && str_contains($page->nextPageUrl(), 'talla=M'));
        $this->get($filters.'&page=2')->assertOk()
            ->assertViewHas('products', fn ($page) => $page->items()[0]['name'] === 'Camiseta 13');
        $this->getJson('/ropa?precio_min=10&precio_max=5')->assertUnprocessable();
    }

    public function test_search_and_detail_hide_inactive_and_deleted_products(): void
    {
        $category = Category::create(['nombre' => 'ROPA', 'slug' => 'ropa']);
        $product = $this->product($category, 'Camisa blanca');
        $product->images()->create(['ruta' => 'camisa.jpg']);
        $color = $product->colors()->create(['nombre' => 'Blanco', 'imagen' => 'blanco.jpg']);
        $product->variants()->create(['talla' => 'M', 'product_color_id' => $color->id, 'stock' => 2]);
        $this->product($category, 'Camisa oculta', ['activo' => false]);
        $this->product($category, 'Camisa retirada')->delete();

        $this->getJson('/buscar?q=CAMISA')->assertOk()->assertJsonCount(1, 'results')
            ->assertJsonPath('results.0.name', 'Camisa blanca');
        $this->getJson('/buscar?q=%25')->assertOk()->assertJsonCount(0, 'results');
        $this->get('/buscar?q=camisa')->assertOk()->assertSee('Camisa blanca');
        $this->get('/producto/camisa-blanca')->assertOk()->assertSee('Camisa blanca')
            ->assertViewHas('variants', fn ($variants) => $variants->first()['stock'] === 2);
        $this->get('/producto/camisa-oculta')->assertNotFound();
        $this->get('/producto/camisa-retirada')->assertNotFound();
    }

    private function product(Category $category, string $name, array $attributes = []): Product
    {
        return $category->products()->create($attributes + [
            'nombre' => $name,
            'slug' => str($name)->slug()->toString(),
            'descripcion' => 'Descripción de prueba',
            'precio' => 100000,
            'activo' => true,
        ]);
    }
}
