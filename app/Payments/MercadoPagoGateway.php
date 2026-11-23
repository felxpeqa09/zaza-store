<?php

namespace App\Payments;

use App\Models\Order;
use Brick\Math\BigDecimal;
use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class MercadoPagoGateway implements PaymentGateway
{
    private const API = 'https://api.mercadopago.com';

    public function assertReady(): void
    {
        $this->environment();
        $this->httpsRoute('mercadopago.webhook');
        if (blank(config('mercadopago.access_token')) || blank(config('mercadopago.webhook_secret'))) {
            throw new GatewayUnavailable('Configura el acceso y los Webhooks de Mercado Pago.');
        }
    }

    public function createCheckout(Order $order): CheckoutPreference
    {
        $return = $this->httpsRoute('orders.result', $order);
        $data = $this->send('POST', '/checkout/preferences', [
            'items' => $order->items->map(fn ($item) => [
                'title' => $item->nombre,
                'quantity' => $item->cantidad,
                'currency_id' => 'COP',
                'unit_price' => $item->precio_unitario,
            ])->all(),
            'external_reference' => $order->referencia,
            'notification_url' => $this->httpsRoute('mercadopago.webhook'),
            'back_urls' => ['success' => $return, 'pending' => $return, 'failure' => $return],
            'auto_return' => 'approved',
            'expires' => true,
            'expiration_date_from' => $order->created_at->format('Y-m-d\TH:i:s.vP'),
            'expiration_date_to' => $order->stock_reserved_until->format('Y-m-d\TH:i:s.vP'),
        ]);
        $url = $this->environment() === 'test'
            ? ($data['sandbox_init_point'] ?? $data['init_point'] ?? null)
            : ($data['init_point'] ?? null);
        $id = $data['id'] ?? null;
        if (! is_string($id) || $id === '' || strlen($id) > 255 || ! $this->safeCheckoutUrl($url)) {
            throw new GatewayUnavailable('Respuesta de preferencia inválida.');
        }

        return new CheckoutPreference($id, $url);
    }

    public function handleWebhook(Request $request): ?PaymentStatus
    {
        $id = $this->signedPaymentId($request);
        if ($request->query('type', $request->input('type')) !== 'payment') {
            return null;
        }

        return $this->getTransactionStatus($id);
    }

    public function getTransactionStatus(string $paymentId): PaymentStatus
    {
        if (! preg_match('/^[0-9]{1,40}$/D', $paymentId)) {
            throw new GatewayUnavailable('Identificador de pago inválido.');
        }

        $data = $this->send('GET', '/v1/payments/'.$paymentId);
        if ((string) ($data['id'] ?? '') !== $paymentId || ! is_string($data['status'] ?? null)) {
            throw new GatewayUnavailable('Respuesta de pago inválida.');
        }

        $amount = '';
        try {
            $value = $data['transaction_amount'] ?? '';
            $decimal = is_float($value) ? json_encode($value, JSON_THROW_ON_ERROR) : (string) $value;
            $amount = (string) BigDecimal::of($decimal)->toBigInteger();
        } catch (Throwable) {
            // Fractional or malformed COP amounts are rejected during reconciliation.
        }

        return new PaymentStatus(
            $paymentId, (string) ($data['external_reference'] ?? ''), $data['status'], $amount,
            (string) ($data['currency_id'] ?? ''), $this->collector() === (string) ($data['collector_id'] ?? ''),
            $this->date($data['date_last_updated'] ?? null), $this->date($data['date_approved'] ?? null),
            $this->code($data['payment_method_id'] ?? null), $this->code($data['payment_type_id'] ?? null),
        );
    }

    public function findPaymentIds(string $reference): array
    {
        $ids = [];
        $offset = 0;
        do {
            $page = $this->send('GET', '/v1/payments/search', [
                'external_reference' => $reference, 'sort' => 'date_created', 'criteria' => 'asc',
                'limit' => 50, 'offset' => $offset,
            ]);
            $rows = $page['results'] ?? null;
            $total = $page['paging']['total'] ?? null;
            if (! is_array($rows) || ! is_int($total) || ($rows === [] && $total > $offset)) {
                throw new GatewayUnavailable('Respuesta de búsqueda inválida.');
            }
            foreach ($rows as $row) {
                $id = (string) ($row['id'] ?? '');
                if (! preg_match('/^[0-9]{1,40}$/D', $id)) {
                    throw new GatewayUnavailable('Identificador de pago inválido.');
                }
                $ids[] = $id;
            }
            $offset += count($rows);
        } while ($offset < $total);

        return array_values(array_unique($ids));
    }

    private function signedPaymentId(Request $request): string
    {
        $ids = [];
        foreach (explode('&', (string) $request->server('QUERY_STRING')) as $value) {
            [$key, $value] = array_pad(explode('=', $value, 2), 2, '');
            if (urldecode($key) === 'data.id') {
                $ids[] = strtolower(urldecode($value));
            }
        }
        $parts = [];
        foreach (explode(',', (string) $request->header('x-signature')) as $value) {
            [$key, $value] = array_map('trim', array_pad(explode('=', $value, 2), 2, ''));
            if (in_array($key, ['ts', 'v1'], true)) {
                if (isset($parts[$key])) {
                    throw new InvalidWebhook;
                }
                $parts[$key] = $value;
            }
        }
        $secret = (string) config('mercadopago.webhook_secret');
        $requestId = (string) $request->header('x-request-id');
        $id = $ids[0] ?? '';
        $timestamp = $parts['ts'] ?? '';
        $signature = $parts['v1'] ?? '';
        if ($secret === '' || count($ids) !== 1 || ! preg_match('/^[0-9]{1,40}$/D', $id)
            || ! preg_match('/^[0-9]{1,16}$/D', $timestamp) || ! preg_match('/^[a-zA-Z0-9_-]{1,128}$/D', $requestId)
            || ! hash_equals(hash_hmac('sha256', "id:{$id};request-id:{$requestId};ts:{$timestamp};", $secret), $signature)) {
            throw new InvalidWebhook;
        }

        return $id;
    }

    private function collector(): string
    {
        $key = hash('sha256', (string) config('mercadopago.access_token'));

        return Cache::remember('mercadopago:collector:'.$key, 600, function (): string {
            $id = (string) ($this->send('GET', '/users/me', origin: 'https://api.mercadolibre.com')['id'] ?? '');
            if (! preg_match('/^[0-9]+$/D', $id)) {
                throw new GatewayUnavailable('Cuenta receptora no identificada.');
            }

            return $id;
        });
    }

    private function send(string $method, string $path, array $parameters = [], string $origin = self::API): array
    {
        $this->environment();
        $token = (string) config('mercadopago.access_token');
        if ($token === '') {
            throw new GatewayUnavailable('Configura las credenciales de Mercado Pago.');
        }

        try {
            $http = Http::baseUrl($origin)->withToken($token)->acceptJson()->connectTimeout(3)->timeout(8)
                ->withoutRedirecting();
            $response = $method === 'GET' ? $http->get($path, $parameters) : $http->post($path, $parameters);
        } catch (ConnectionException) {
            throw new GatewayUnavailable('Mercado Pago no respondió.');
        }

        if (! $response->successful() || ! is_array($result = $response->json())) {
            throw new GatewayUnavailable('Mercado Pago no procesó la solicitud.');
        }

        return $result;
    }

    private function environment(): string
    {
        $value = config('mercadopago.environment');
        if (! in_array($value, ['test', 'production'], true)) {
            throw new GatewayUnavailable('Ambiente de Mercado Pago inválido.');
        }

        return $value;
    }

    private function httpsRoute(string $name, mixed $parameters = []): string
    {
        // Job, web and webhook contexts all use the configured canonical origin.
        $origin = rtrim((string) config('app.url'), '/');
        if (parse_url($origin, PHP_URL_SCHEME) !== 'https' || ! parse_url($origin, PHP_URL_HOST)) {
            throw new GatewayUnavailable('Mercado Pago requiere APP_URL pública con HTTPS.');
        }

        return $origin.route($name, $parameters, absolute: false);
    }

    private function safeCheckoutUrl(mixed $url): bool
    {
        if (! is_string($url) || strlen($url) > 2048 || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }
        $host = (string) parse_url($url, PHP_URL_HOST);

        return parse_url($url, PHP_URL_SCHEME) === 'https'
            && ! parse_url($url, PHP_URL_USER)
            && preg_match('/^(?:[a-z0-9-]+\.)*mercadopago\.(?:com\.co|com)$/iD', $host);
    }

    private function date(mixed $value): ?CarbonImmutable
    {
        try {
            return is_string($value) && $value !== '' ? CarbonImmutable::make($value) : null;
        } catch (InvalidFormatException) {
            throw new GatewayUnavailable('Mercado Pago devolvió una fecha inválida.');
        }
    }

    private function code(mixed $value): ?string
    {
        return is_string($value) && preg_match('/^[a-z0-9_-]{1,80}$/iD', $value) ? $value : null;
    }
}
