<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Payments\GatewayUnavailable;
use App\Services\ExpiredOrderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReleaseExpiredOrders extends Command
{
    protected $signature = 'orders:release-expired';

    protected $description = 'Concilia pagos y libera las reservas vencidas de pedidos pendientes';

    public function handle(ExpiredOrderService $orders): int
    {
        $failures = 0;
        $released = 0;
        Order::query()->where('estado', 'PENDIENTE')->where('stock_reserved_until', '<=', now())
            ->chunkById(50, function ($batch) use ($orders, &$released, &$failures): void {
                foreach ($batch as $order) {
                    try {
                        $released += (int) $orders->release($order);
                    } catch (GatewayUnavailable) {
                        $failures++;
                        Log::warning('Conciliación de vencimiento aplazada.', ['order_id' => $order->id]);
                    }
                }
            });

        $this->info("Reservas liberadas: {$released}. Conciliaciones aplazadas: {$failures}.");

        return $failures ? self::FAILURE : self::SUCCESS;
    }
}
