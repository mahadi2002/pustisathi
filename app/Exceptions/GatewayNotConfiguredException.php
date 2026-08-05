<?php
declare(strict_types=1);

namespace App\Exceptions;

/**
 * Thrown by DcbGateway until real DCB credentials are present in
 * .env. Nothing catches this and falls back to MockGateway — a
 * misconfigured production deploy should fail loudly, not quietly bill
 * nobody.
 */
class GatewayNotConfiguredException extends GatewayException
{
}
