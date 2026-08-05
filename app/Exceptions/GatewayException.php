<?php
declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/** Transport/protocol failure talking to the subscription gateway. */
class GatewayException extends RuntimeException
{
}
