<?php
declare(strict_types=1);

namespace App\Gateways;

final class OtpRequestResult
{
    public function __construct(
        public readonly bool $sent,
        public readonly ?int $retryAfterSeconds = null,
        public readonly ?string $message = null,
    ) {
    }
}
