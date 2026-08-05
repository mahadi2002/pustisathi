<?php
declare(strict_types=1);

namespace App\Gateways;

use App\Exceptions\GatewayNotConfiguredException;

/**
 * Prod stub — wired but unusable until real DCB DCB credentials are
 * issued. Throws until every DCB_* key in .env is present, and never
 * falls back to MockGateway on its own.
 */
final class DcbGateway implements SubscriptionGateway
{
    public function __construct()
    {
        $this->assertConfigured();
    }

    public function requestOtp(string $mobile): OtpRequestResult
    {
        throw new GatewayNotConfiguredException('DCB API not yet implemented.');
    }

    public function verifyOtp(string $mobile, string $otp): SubscriptionResult
    {
        throw new GatewayNotConfiguredException('DCB API not yet implemented.');
    }

    public function checkStatus(string $subscriptionRef): SubscriptionStatus
    {
        throw new GatewayNotConfiguredException('DCB API not yet implemented.');
    }

    public function handleWebhook(array $payload): WebhookResult
    {
        throw new GatewayNotConfiguredException('DCB API not yet implemented.');
    }

    private function assertConfigured(): void
    {
        $cfg = config('gateway.dcb');
        foreach (['api_base', 'client_id', 'client_secret', 'sdp_service_id', 'webhook_secret'] as $key) {
            if (($cfg[$key] ?? '') === '') {
                throw new GatewayNotConfiguredException(
                    "DCB_* credentials are missing in .env ('{$key}'). SUBSCRIPTION_GATEWAY=dcb cannot run without them."
                );
            }
        }
    }
}
