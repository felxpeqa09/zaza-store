<?php

namespace App\View;

use App\Models\Order;

final class OrderDisplay
{
    public const STEPS = [
        'PENDIENTE' => 'Pendiente de pago', 'PAGADO' => 'Pagado', 'EN_PREPARACION' => 'En preparación',
        'ENVIADO' => 'Enviado', 'ENTREGADO' => 'Entregado',
    ];

    public static function status(Order $order): string
    {
        return self::STEPS[$order->estado] ?? match ($order->estado) {
            'EXPIRADO' => 'Expirado', 'CANCELADO' => 'Cancelado', default => $order->estado,
        };
    }

    public static function method(?string $value): ?string
    {
        return match ($value) {
            'credit_card' => 'Tarjeta de crédito', 'debit_card' => 'Tarjeta débito', 'bank_transfer' => 'Transferencia bancaria',
            'ticket' => 'Pago en efectivo', 'account_money' => 'Saldo de Mercado Pago',
            null => null, default => strtoupper($value),
        };
    }
}
