<?php
declare(strict_types=1);

namespace App\Gateways;

/** Contract both MockGateway (active dev) and DcbGateway (prod stub) must satisfy. */
interface SubscriptionGateway
{
    public function requestOtp(string $mobile): OtpRequestResult;

    public function verifyOtp(string $mobile, string $otp): SubscriptionResult;

    public function checkStatus(string $subscriptionRef): SubscriptionStatus;

    /** @param array<string,mixed> $payload */
    public function handleWebhook(array $payload): WebhookResult;
}
