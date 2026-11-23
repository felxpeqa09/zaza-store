<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use App\Payments\GatewayUnavailable;
use App\Payments\PaymentGateway;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function __construct(private StockReservationService $stock, private PaymentGateway $gateway) {}

    public function pending(User $user): ?Order
    {
        return $user->orders()->where('estado', 'PENDIENTE')->whereNotNull('checkout_token')->first();
    }

    public function start(User $user, string $token, array $shipping): Order
    {
        return DB::transaction(function () use ($user, $token, $shipping): Order {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $existing = $user->orders()->where('checkout_token', $token)->first() ?? $this->pending($user);
            if ($existing) {
                return $existing;
            }

            $cart = $user->cart()->first();
            $quantities = $cart?->items()->pluck('cantidad', 'product_variant_id')->all() ?? [];
            if ($quantities === []) {
                throw ValidationException::withMessages(['carrito' => 'Tu carrito está vacío.']);
            }

            $this->gateway->assertReady();
            [$items, $total] = $this->stock->reserve($quantities);
            $order = $user->orders()->create([
                'referencia' => 'ZAZA-'.Str::ulid(), 'checkout_token' => $token,
                'estado' => 'PENDIENTE', 'total' => $total, 'datos_envio' => $shipping,
                'stock_reserved_until' => now()->addMinutes(30),
            ]);
            $order->items()->createMany($items);
            $cart->items()->delete();

            return $order;
        }, 3);
    }

    public function preference(Order $order): ?string
    {
        try {
            return Cache::lock('checkout:preference:'.$order->id, 30)->block(10, function () use ($order): ?string {
                $order->refresh();
                if (! $this->payable($order)) {
                    return null;
                }
                if ($order->checkout_url) {
                    return $order->checkout_url;
                }

                $preference = $this->gateway->createCheckout($order->load('items'));

                return DB::transaction(function () use ($order, $preference): ?string {
                    $current = Order::query()->lockForUpdate()->findOrFail($order->id);
                    if (! $this->payable($current)) {
                        return null;
                    }

                    $current->update(['provider_preference_id' => $preference->id, 'checkout_url' => $preference->url]);

                    return $preference->url;
                }, 3);
            });
        } catch (LockTimeoutException) {
            throw new GatewayUnavailable('Ya se está preparando este pago.');
        }
    }

    public function payable(Order $order): bool
    {
        return $order->estado === 'PENDIENTE' && $order->stock_reserved_until?->isFuture() === true;
    }
}
