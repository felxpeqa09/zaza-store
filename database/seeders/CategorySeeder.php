<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            'ROPA' => ['Camisas', 'Hoodies', 'Pantalones', 'Bermudas'],
            'ZAPATOS' => [],
            'ACCESORIOS' => ['Gorras', 'Carrieles'],
        ];

        foreach ($groups as $name => $subcategories) {
            $category = Category::firstOrCreate(['slug' => Str::slug($name)], ['nombre' => $name]);

            foreach ($subcategories as $subcategory) {
                $category->subcategories()->firstOrCreate(
                    ['slug' => Str::slug($subcategory)], ['nombre' => $subcategory],
                );
            }
        }
    }
}
