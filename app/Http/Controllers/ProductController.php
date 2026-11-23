<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\View\ProductDisplay;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __invoke(string $slug): View
    {
        $product = Product::query()->activo()->with(['category', 'images', 'colors', 'variants'])
            ->where('slug', $slug)->firstOrFail();

        $images = $product->images->map(fn ($image) => ProductDisplay::image($image->ruta))->values()->all();
        $images = $images ?: [ProductDisplay::image(null)];
        $colors = $product->colors->map(fn ($color) => [
            'id' => $color->id,
            'name' => $color->nombre,
            'image' => $color->imagen ? ProductDisplay::image($color->imagen) : null,
        ])->values();
        $variants = $product->variants->map(fn ($variant) => [
            'id' => $variant->id,
            'color' => $variant->product_color_id,
            'size' => $variant->talla,
            'stock' => $variant->stock,
        ])->values();

        $openGraph = [
            'type' => 'product', 'title' => $product->nombre.' | ZAZA STORE',
            'description' => str($product->descripcion)->stripTags()->limit(160)->toString(),
            'url' => rtrim(config('app.url'), '/').route('products.show', $product->slug, false),
            'image' => $images[0],
        ];

        return view('catalog.show', compact('product', 'images', 'colors', 'variants', 'openGraph'));
    }
}
