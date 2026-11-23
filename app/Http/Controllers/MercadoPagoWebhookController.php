<?php

namespace App\Http\Controllers;

use App\Payments\GatewayUnavailable;
use App\Payments\InvalidWebhook;
use App\Payments\PaymentGateway;
use App\Services\PaymentReconciliation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MercadoPagoWebhookController extends Controller
{
    public function __invoke(Request $request, PaymentGateway $gateway, PaymentReconciliation $payments): JsonResponse
    {
        try {
            if ($status = $gateway->handleWebhook($request)) {
                $payments->apply($status);
            }
        } catch (InvalidWebhook) {
            Log::warning('Firma inválida en webhook de Mercado Pago.');

            return response()->json(['ok' => false], 401);
        } catch (GatewayUnavailable) {
            Log::warning('Consulta de Mercado Pago aplazada tras una notificación.');

            return response()->json(['ok' => false], 503);
        }

        return response()->json(['ok' => true]);
    }
}
