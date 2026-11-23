<?php

namespace App\Services;

use App\Models\Order;
use App\Payments\PaymentGateway;
use Illuminate\Support\Facades\DB;

class ExpiredOrderService
{
    public function __construct(
        private PaymentGateway $gateway,
        private PaymentReconciliation $payments,
        private StockReservationService $stock,
    ) {}

    public function release(Order $order): bool
    {
        $ids = $order->payments()->where('proveedor', 'mercadopago')->pluck('provider_payment_id')->all();
        // A missing preference ID can mean the creation response was lost.
        $ids = array_unique([...$ids, ...$this->gateway->findPaymentIds($order->referencia)]);
        foreach ($ids as $id) {
            $this->payments->apply($this->gateway->getTransactionStatus((string) $id), $order->referencia);
        }

        return DB::transaction(function () use ($order, $ids): bool {
            $current = Order::query()->lockForUpdate()->findOrFail($order->id);
            if ($current->estado !== 'PENDIENTE' || ! $current->stock_reserved_until?->isPast()) {
                return false;
            }
            $known = $current->payments()->where('proveedor', 'mercadopago')->pluck('provider_payment_id')->all();
            if (array_diff($known, $ids)) {
                return false;
            }
            $this->stock->release($current);

            return true;
        }, 3);
    }
}
