<?php

namespace App\View;

use App\Services\CartService;
use App\Support\Cop;
use Brick\Math\BigInteger;
use Illuminate\Support\Collection;

final class CartDisplay
{
    public static function summary(array $items, Collection $variants, array $notices): array
    {
        $lines = [];
        $units = 0;
        $total = BigInteger::zero();

        foreach ($items as $id => $quantity) {
            $variant = $variants->get($id);
            $stock = CartService::stock($variant);
            $available = $quantity <= $stock;
            $product = $variant?->product;
            $price = $product ? BigInteger::of($product->precio) : null;
            $subtotal = $available ? $price->multipliedBy($quantity) : null;
            $units += $quantity;
            $total = $total->plus($subtotal ?? 0);
            $lines[] = [
                'id' => (string) $id,
                'name' => $product?->nombre ?? 'Artículo no disponible',
                'image' => ProductDisplay::image($variant?->color?->imagen ?: $product?->images->first()?->ruta),
                'size' => $variant?->talla,
                'color' => $variant?->color?->nombre,
                'quantity' => $quantity,
                'stock' => $stock,
                'price' => $price === null ? null : Cop::format((string) $price),
                'subtotal' => $subtotal === null ? null : Cop::format((string) $subtotal),
                'notice' => $available ? null : ($stock > 0 ? "Solo hay {$stock} unidades disponibles." : 'Artículo sin disponibilidad.'),
            ];
        }

        return [
            'items' => $lines,
            'units' => $units,
            'total' => (string) $total,
            'totalLabel' => Cop::format((string) $total),
            'notices' => $notices,
        ];
    }
}
