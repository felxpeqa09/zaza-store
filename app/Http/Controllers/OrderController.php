<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Support\Cop;
use App\View\OrderDisplay;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function show(Order $order): View
    {
        Gate::authorize('view', $order);
        $order->load('items');
        $status = OrderDisplay::status($order);
        $steps = OrderDisplay::STEPS;
        $currentStep = array_search($order->estado, array_keys($steps), true);
        $method = OrderDisplay::method($order->medio_pago);
        $type = OrderDisplay::method($order->forma_pago);
        $total = Cop::format($order->total);
        $paid = $order->fecha_pago !== null;
        $items = $order->items->map(fn ($item) => [
            'name' => $item->nombre, 'color' => $item->color, 'size' => $item->talla,
            'quantity' => $item->cantidad, 'price' => Cop::format($item->precio_unitario),
        ]);

        return view('orders.show', compact('order', 'status', 'steps', 'currentStep', 'method', 'type', 'total', 'paid', 'items'));
    }

    public function result(Order $order): View
    {
        Gate::authorize('view', $order);

        return view('orders.result', ['order' => $order, 'status' => OrderDisplay::status($order)]);
    }
}
