<?php

namespace App\View;

use App\Models\Product;
use Illuminate\Support\Facades\Storage;

final class ProductDisplay
{
    public static function image(?string $path): string
    {
        if (! $path || $path === 'img/producto-placeholder.svg') {
            return asset('img/producto-placeholder.svg');
        }

        return Storage::disk(config('zaza.catalog_disk'))->url($path);
    }

    public static function card(Product $product): array
    {
        return [
            'id' => (string) $product->id,
            'name' => $product->nombre,
            'url' => route('products.show', $product->slug),
            'price' => $product->precio_formateado,
            'image' => self::image($product->images->first()?->ruta),
        ];
    }
}
