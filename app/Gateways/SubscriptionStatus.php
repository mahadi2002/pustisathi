<?php
declare(strict_types=1);

namespace App\Gateways;

final class SubscriptionStatus
{
    public function __construct(
        public readonly ?string $status,
        public readonly ?string $nextChargeAt = null,
    ) {
    }
}
