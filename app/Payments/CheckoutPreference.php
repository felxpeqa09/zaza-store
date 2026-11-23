<?php

namespace App\Payments;

final readonly class CheckoutPreference
{
    public function __construct(public string $id, public string $url) {}
}
