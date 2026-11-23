<?php

namespace App\Services;

use App\Mail\OrderConfirmation;
use App\Models\Order;
use App\Models\Payment;
use App\Payments\PaymentStatus;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PaymentReconciliation
{
    public function apply(PaymentStatus $status, ?string $expectedReference = null): void
    {
        DB::transaction(function () use ($status, $expectedReference): void {
            $order = Order::query()->where('referencia', $status->reference)->lockForUpdate()->first();
            if (! $order || ($expectedReference !== null && $expectedReference !== $status->reference)
                || $status->amount !== (string) $order->total || $status->currency !== 'COP' || ! $status->correctCollector
                || ! preg_match('/^[a-z_]{1,40}$/D', $status->status)) {
                Log::warning('Pago Mercado Pago discrepante.', ['payment_id' => $status->id, 'order_id' => $order?->id]);

                return;
            }

            $payment = $this->attempt($order, $status);
            if (! $payment) {
                return;
            }

            $reason = $payment->raw_payload['conciliacion'] ?? null;
            if ($reason === null && $status->status === 'approved' && $order->approved_payment_id !== $status->id) {
                $reason = $this->approve($order, $status);
            }
            $payment->update(['estado' => $status->status, 'raw_payload' => [
                'date_last_updated' => $status->updatedAt?->toISOString(),
                'date_approved' => $status->approvedAt?->toISOString(),
                'payment_method_id' => $status->method, 'payment_type_id' => $status->type,
                'conciliacion' => $reason,
            ]]);
        }, 3);
    }

    private function attempt(Order $order, PaymentStatus $status): ?Payment
    {
        $payment = Payment::query()->firstOrCreate([
            'proveedor' => 'mercadopago', 'provider_payment_id' => $status->id,
        ], [
            'order_id' => $order->id, 'estado' => $status->status,
            'importe' => $order->total, 'moneda' => 'COP', 'raw_payload' => [],
        ]);

        if ($payment->order_id !== $order->id) {
            Log::warning('Pago Mercado Pago asociado a otro pedido.', ['payment_id' => $status->id]);

            return null;
        }
        $previous = $payment->raw_payload['date_last_updated'] ?? null;
        if (($previous && (! $status->updatedAt || $status->updatedAt->lt(CarbonImmutable::parse($previous))))
            || ($payment->estado === 'approved' && in_array($status->status, ['pending', 'in_process', 'rejected', 'cancelled'], true))) {
            return null;
        }

        return $payment;
    }

    private function approve(Order $order, PaymentStatus $status): ?string
    {
        if ($order->estado !== 'PENDIENTE' || ! $order->stock_reserved_until) {
            $reason = $order->approved_payment_id ? 'aprobacion_adicional' : 'reserva_liberada';
            Log::warning('Pago Mercado Pago requiere conciliación.', [
                'order_id' => $order->id, 'payment_id' => $status->id, 'reason' => $reason,
            ]);

            return $reason;
        }

        $order->update([
            'estado' => 'PAGADO', 'approved_payment_id' => $status->id, 'medio_pago' => $status->method,
            'forma_pago' => $status->type, 'fecha_pago' => $status->approvedAt ?? now(), 'stock_reserved_until' => null,
        ]);
        $order->loadMissing('user');
        // The database queue row commits with this payment, so a worker can only see it after commit.
        Mail::to($order->user->email)->queue((new OrderConfirmation($order))
            ->onConnection('database')->beforeCommit());

        return null;
    }
}
