<?php
declare(strict_types=1);

namespace App\Gateways;

final class SubscriptionResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?int $userId = null,
        public readonly ?int $subscriptionId = null,
        public readonly bool $isNewUser = false,
        public readonly ?string $message = null,
    ) {
    }
}
