<?php
declare(strict_types=1);

namespace App\Gateways;

final class GatewayFactory
{
    public static function make(): SubscriptionGateway
    {
        return match (config('gateway.driver')) {
            'dcb' => new DcbGateway(),
            default  => new MockGateway(),
        };
    }
}
