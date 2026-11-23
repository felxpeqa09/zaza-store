<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\ProductVariant;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CartService
{
    public const SESSION_KEY = 'guest_cart';

    public function snapshot(?User $user, Session $session): array
    {
        $items = $user
            ? ($user->cart()->first()?->items()->pluck('cantidad', 'product_variant_id')->all() ?? [])
            : $session->get(self::SESSION_KEY.'.items', []);

        return [$items, $this->variants(array_keys($items))];
    }

    public function add(?User $user, Session $session, int $id, int $quantity): void
    {
        $this->change($user, $session, $id, fn (int $current) => $current + $quantity);
    }

    public function update(?User $user, Session $session, int $id, int $quantity): void
    {
        $this->change($user, $session, $id, function (int $current) use ($quantity): int {
            abort_unless($current > 0, 404);

            return $quantity;
        });
    }

    public function remove(?User $user, Session $session, int $id): void
    {
        if ($user) {
            $this->withCart($user, fn (Cart $cart) => $cart->items()->where('product_variant_id', $id)->delete());

            return;
        }

        $session->forget(self::SESSION_KEY.'.items.'.$id);
    }

    public function mergeGuest(User $user, Session $session): void
    {
        $guest = $session->get(self::SESSION_KEY);
        if (empty($guest['items'])) {
            return;
        }

        $notices = $this->withCart($user, function (Cart $cart) use ($guest): array {
            $receipt = DB::table('cart_merges')->where('cart_id', $cart->id)->where('batch', $guest['batch'])->value('notices');
            if ($receipt !== null) {
                return json_decode($receipt, true, flags: JSON_THROW_ON_ERROR);
            }

            $notices = $this->mergeItems($cart, $guest['items']);
            DB::table('cart_merges')->insert([
                'cart_id' => $cart->id,
                'batch' => $guest['batch'],
                'notices' => json_encode($notices, JSON_THROW_ON_ERROR),
            ]);

            return $notices;
        });

        $session->forget(self::SESSION_KEY);
        if ($notices !== []) {
            $session->put('cart_merge_notices', $notices);
        }
    }

    private function mergeItems(Cart $cart, array $guestItems): array
    {
        $stored = $cart->items()->pluck('cantidad', 'product_variant_id');
        $variants = $this->variants(array_keys($guestItems));
        $notices = [];

        foreach ($guestItems as $id => $count) {
            $variant = $variants->get($id);
            $requested = ($stored[$id] ?? 0) + $count;
            $allowed = min($requested, self::stock($variant));
            $this->saveItem($cart, $id, $allowed);

            if ($allowed < $requested) {
                $name = $variant ? "{$variant->product->nombre} ({$variant->talla})" : 'Un artículo';
                $notices[] = $allowed
                    ? "{$name}: ajustamos la cantidad a {$allowed} por disponibilidad."
                    : "{$name} se retiró del carrito por falta de disponibilidad.";
            }
        }

        return $notices;
    }

    private function change(?User $user, Session $session, int $id, Closure $quantity): void
    {
        if ($user) {
            $this->withCart($user, function (Cart $cart) use ($id, $quantity): void {
                $current = (int) $cart->items()->where('product_variant_id', $id)->value('cantidad');
                $this->saveItem($cart, $id, $this->validatedQuantity($id, $quantity($current), lock: true));
            });

            return;
        }

        $guest = $session->get(self::SESSION_KEY, ['batch' => (string) Str::uuid(), 'items' => []]);
        $guest['items'][$id] = $this->validatedQuantity($id, $quantity($guest['items'][$id] ?? 0));
        $session->put(self::SESSION_KEY, $guest);
    }

    private function validatedQuantity(int $id, int $quantity, bool $lock = false): int
    {
        $variant = ProductVariant::query()->with('product')->when($lock, fn ($query) => $query->sharedLock())->find($id);
        $stock = self::stock($variant);
        if ($quantity > $stock) {
            throw ValidationException::withMessages([
                'cantidad' => [$stock > 0 ? "Solo hay {$stock} unidades disponibles." : 'Esta variante ya no está disponible.'],
            ]);
        }

        return $quantity;
    }

    private function saveItem(Cart $cart, int $variant, int $quantity): void
    {
        if ($quantity === 0) {
            $cart->items()->where('product_variant_id', $variant)->delete();

            return;
        }

        $cart->items()->updateOrCreate(['product_variant_id' => $variant], ['cantidad' => $quantity]);
    }

    private function withCart(User $user, Closure $callback): mixed
    {
        return DB::transaction(function () use ($user, $callback) {
            User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();
            $cart = Cart::firstOrCreate(['user_id' => $user->getKey()]);

            return $callback($cart);
        }, 3);
    }

    private function variants(array $ids): Collection
    {
        return $ids === [] ? collect() : ProductVariant::query()
            ->with(['product.images', 'color'])->whereKey($ids)->get()->keyBy('id');
    }

    public static function stock(?ProductVariant $variant): int
    {
        if (! $variant || ! $variant->product?->activo || $variant->product->trashed()) {
            return 0;
        }

        return $variant->stock;
    }
}
