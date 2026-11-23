<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Payments\GatewayUnavailable;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Support\Cop;
use App\View\CartDisplay;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function show(Request $request, CartService $cart, CheckoutService $checkout): View
    {
        $profile = $request->user();
        $pending = $checkout->pending($profile)?->load('items');
        [$quantities, $variants] = $cart->snapshot($profile, $request->session());
        $summary = CartDisplay::summary($quantities, $variants, []);
        $canPay = $pending ? $checkout->payable($pending) : $summary['total'] !== '0'
            && ! collect($summary['items'])->contains(fn ($item) => $item['notice'] !== null);
        $token = $pending?->checkout_token ?? Str::uuid()->toString();
        $shipping = $pending?->datos_envio ?? [
            'nombre_completo' => $profile->nombre_completo, 'telefono' => $profile->telefono,
            'direccion' => $profile->direccion, 'ciudad' => null, 'departamento' => null,
        ];
        $total = $pending ? Cop::format($pending->total) : $summary['totalLabel'];

        return view('checkout.show', compact('pending', 'summary', 'canPay', 'token', 'shipping', 'total'));
    }

    public function store(CheckoutRequest $request, CheckoutService $checkout): RedirectResponse
    {
        try {
            $order = $checkout->start($request->user(), $request->validated('checkout_token'), $request->safe()->except('checkout_token'));
            $url = $checkout->preference($order);

            return $url ? redirect()->away($url, 303) : to_route('orders.result', $order, 303);
        } catch (GatewayUnavailable) {
            return to_route('checkout.show', status: 303)->withErrors([
                'pago' => 'No pudimos abrir Mercado Pago. Intenta de nuevo desde el checkout.',
            ])->withInput($request->safe()->except('checkout_token'));
        }
    }
}
