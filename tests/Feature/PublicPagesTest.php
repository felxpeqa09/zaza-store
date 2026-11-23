<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Support\Facades\URL;
use Tests\PostgresTestCase;

class PublicPagesTest extends PostgresTestCase
{
    public function test_location_legal_pages_and_seo_are_exposed_without_private_routes_in_sitemap(): void
    {
        config(['app.url' => 'https://zaza.example.test']);
        URL::forceRootUrl('https://zaza.example.test');
        $category = Category::create(['nombre' => 'ROPA', 'slug' => 'ropa']);
        foreach (['publico' => true, 'inactivo' => false, 'eliminado' => true] as $slug => $active) {
            $product = $category->products()->create([
                'nombre' => ucfirst($slug), 'slug' => $slug, 'descripcion' => 'Descripción SEO',
                'precio' => 190000, 'activo' => $active,
            ]);
            if ($slug === 'eliminado') {
                $product->delete();
            }
        }

        $this->get('/quienes-somos')->assertOk()->assertSee('Cra 7 # 16-25, centro, Ibagué')->assertSee('Cargar mapa');
        $this->get('/politica-de-devolucion-cambios-y-garantias')->assertOk()->assertSee('Garantía');
        $this->get('/politica-de-privacidad')->assertOk()->assertSee('Ley 1581 de 2012');
        $this->get('/cookies')->assertOk()->assertSee('Solo esenciales');
        $this->get('/robots.txt')->assertOk()->assertSee('Sitemap: https://zaza.example.test/sitemap.xml');
        $this->get('/producto/publico')->assertOk()->assertSee('property="og:title"', false)
            ->assertSee('https://zaza.example.test/producto/publico')->assertHeader('X-Content-Type-Options', 'nosniff');

        $sitemap = $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $xml = simplexml_load_string($sitemap->streamedContent());
        $urls = [];
        foreach ($xml->url as $entry) {
            $urls[] = (string) $entry->loc;
        }
        $this->assertContains('https://zaza.example.test/producto/publico', $urls);
        $this->assertNotContains('https://zaza.example.test/producto/inactivo', $urls);
        $this->assertNotContains('https://zaza.example.test/producto/eliminado', $urls);
        $this->assertNotContains('https://zaza.example.test/admin', $urls);
    }
}
