<?php
declare(strict_types=1);

namespace App\Gateways;

final class WebhookResult
{
    public function __construct(
        public readonly bool $handled,
        public readonly ?string $eventType = null,
    ) {
    }
}
