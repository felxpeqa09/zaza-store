<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['ropa', 'camisas', 'Camisa blanca Emotions', 190000, true, false],
            ['ropa', 'camisas', 'Camisa negra Emotions', 170000, true, false],
            ['ropa', 'hoodies', 'Hoodie Coach', 270000, true, false],
            ['ropa', 'hoodies', 'Hoodie Louis Vuitton', 210000, true, false],
            ['ropa', 'hoodies', 'Hoodie Jordan', 210000, false, false],
            ['ropa', 'bermudas', 'Bermuda Overzy', 120000, false, false],
            ['ropa', 'camisas', 'Camisa Good Speed', 170000, false, false],
            ['ropa', 'camisas', 'Camisa boxy fit Zuwon', 170000, false, false],
            ['ropa', 'pantalones', 'Pantalón descosido Overzy', 210000, false, false],
            ['ropa', 'pantalones', 'Pantalón DSquared', 180000, false, false],
            ['ropa', 'pantalones', 'Pantalón Slim Fit', 210000, false, false],
            ['zapatos', null, 'Asics Gel 1130', 270000, false, false],
            ['zapatos', null, 'Nocta Hot Step 2', 220000, false, false],
            ['zapatos', null, 'Osiris D3 Black', 270000, false, false],
            ['zapatos', null, 'Nike Air Max Plus', 200000, false, false],
            ['zapatos', null, 'Puma La France', 270000, false, false],
            ['zapatos', null, 'Asics Gel-NYC', 210000, false, false],
            ['zapatos', null, 'Air Force 1', 160000, false, false],
            ['zapatos', null, 'Puma 180', 220000, false, false],
            ['zapatos', null, 'Bapesta', 250000, false, false],
            ['zapatos', null, 'Nike Cortez', 210000, false, false],
            ['zapatos', null, 'LV Skate', 230000, false, false],
            ['accesorios', 'carrieles', 'Carriel Amiri', 70000, false, false],
            ['accesorios', 'gorras', 'Gorra roja Amiri', 80000, false, false],
            ['accesorios', 'gorras', 'Gorra Clemont', 70000, false, false],
            ['accesorios', 'gorras', 'Gorra New Era', 90000, false, false],
            ['accesorios', 'gorras', 'Gorra Lone Wolf', 270000, false, true],
            ['accesorios', 'carrieles', 'Carriel Gucci', 210000, false, true],
            ['accesorios', 'carrieles', 'Carriel Clemont', 60000, false, false],
            ['accesorios', 'carrieles', 'Carriel Hunter', 110000, false, false],
            ['accesorios', 'carrieles', 'Carriel Coach', 150000, false, false],
            ['accesorios', 'gorras', 'Gorra Gucci', 70000, false, false],
            ['accesorios', 'carrieles', 'Carriel Burberry', 80000, false, false],
            ['accesorios', 'gorras', 'Gorra Louis Vuitton', 110000, false, false],
            ['accesorios', 'gorras', 'Gorra cerrada Hellstar', 90000, false, false],
        ];

        $categories = Category::query()->with('subcategories')->get()->keyBy('slug');

        DB::transaction(function () use ($products, $categories): void {
            foreach ($products as [$group, $subgroup, $name, $price, $featured, $review]) {
                $category = $categories->get($group);
                $product = Product::withTrashed()->firstOrCreate(['slug' => Str::slug($name)], [
                    'nombre' => $name,
                    'descripcion' => $name === 'Camisa blanca Emotions'
                        ? 'Camisa blanca de la marca Emotions elaborada en tela premium 70% algodón 30% poliéster. Construcción antiarrugas con acabado solido.'
                        : $name,
                    'precio' => $price,
                    'category_id' => $category->id,
                    'subcategory_id' => $category->subcategories->firstWhere('slug', $subgroup)?->id,
                    'activo' => true,
                    'destacado' => $featured,
                    'needs_price_review' => $review,
                ]);

                if (! $product->wasRecentlyCreated) {
                    continue;
                }

                $product->images()->create(['ruta' => 'img/producto-placeholder.svg']);

                $sizes = match ($group) {
                    'ropa' => ['XS', 'S', 'M', 'L', 'XL'],
                    'zapatos' => range(38, 43),
                    'accesorios' => ['Única'],
                };

                foreach ($sizes as $size) {
                    $product->variants()->create(['talla' => (string) $size, 'stock' => 5]);
                }
            }
        });
    }
}
