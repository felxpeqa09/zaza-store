<?php

namespace App\Payments;

use Carbon\CarbonImmutable;

final readonly class PaymentStatus
{
    public function __construct(
        public string $id,
        public string $reference,
        public string $status,
        public string $amount,
        public string $currency,
        public bool $correctCollector,
        public ?CarbonImmutable $updatedAt,
        public ?CarbonImmutable $approvedAt,
        public ?string $method,
        public ?string $type,
    ) {}
}
