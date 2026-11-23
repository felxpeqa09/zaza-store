<?php

namespace App\Payments;

use App\Models\Order;
use Illuminate\Http\Request;

interface PaymentGateway
{
    public function assertReady(): void;

    public function createCheckout(Order $order): CheckoutPreference;

    public function handleWebhook(Request $request): ?PaymentStatus;

    public function getTransactionStatus(string $paymentId): PaymentStatus;

    /** @return list<string> */
    public function findPaymentIds(string $reference): array;
}
