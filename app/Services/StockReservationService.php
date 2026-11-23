<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use Brick\Math\BigInteger;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class StockReservationService
{
    /** Called inside the transaction that persists the order. */
    public function reserve(array $quantities): array
    {
        $total = BigInteger::zero();
        $snapshots = [];
        $variants = $this->lockVariants(array_keys($quantities));

        foreach ($quantities as $id => $quantity) {
            $variant = $variants->get($id);
            if ($quantity < 1 || $quantity > CartService::stock($variant)) {
                throw ValidationException::withMessages(['carrito' => 'Cambió la disponibilidad del carrito. Revisa las cantidades.']);
            }

            $product = $variant->product;
            $snapshots[] = [
                'product_variant_id' => $variant->id, 'nombre' => $product->nombre,
                'color' => $variant->color?->nombre, 'talla' => $variant->talla,
                'cantidad' => $quantity, 'precio_unitario' => $product->precio,
            ];
            $total = $total->plus(BigInteger::of($product->precio)->multipliedBy($quantity));
            $variant->decrement('stock', $quantity);
        }

        if ($total->isLessThanOrEqualTo(0) || $total->isGreaterThan(9_007_199_254_740_991)) {
            throw ValidationException::withMessages(['carrito' => 'El total del carrito está fuera del rango admitido para el pago.']);
        }

        return [$snapshots, $total->toInt()];
    }

    /** Called with the order row locked, after reconciling its known payments. */
    public function release(Order $order): void
    {
        $items = $order->items()->orderBy('product_variant_id')->get();
        $variants = $this->lockVariants($items->pluck('product_variant_id')->filter()->all());
        foreach ($items as $item) {
            $variants->get($item->product_variant_id)?->increment('stock', $item->cantidad);
        }

        $order->update(['estado' => 'EXPIRADO', 'stock_reserved_until' => null]);
    }

    private function lockVariants(array $ids): Collection
    {
        // All inventory writers must acquire product rows before variant rows, in ID order.
        $productIds = ProductVariant::query()->whereKey($ids)->pluck('product_id')->unique()->all();
        Product::withTrashed()->whereKey($productIds)->orderBy('id')->lockForUpdate()->get(['id']);

        return ProductVariant::query()->whereKey($ids)->orderBy('id')->lockForUpdate()
            ->with(['product', 'color'])->get()->keyBy('id');
    }
}
