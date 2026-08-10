<?php
declare(strict_types=1);

/** SubscriptionGateway selection + Direct Carrier Billing (DCB) settings. `driver` picks MockGateway vs DcbGateway in GatewayFactory. */
return [
    'driver' => env('SUBSCRIPTION_GATEWAY', 'mock'), // mock | dcb
    'amount' => (float) env('SUBSCRIPTION_PRICE_BDT', 2.78),

    'dcb' => [
        'api_base'       => env('DCB_API_BASE', ''),
        'client_id'      => env('DCB_CLIENT_ID', ''),
        'client_secret'  => env('DCB_CLIENT_SECRET', ''),
        'sdp_service_id' => env('DCB_SDP_SERVICE_ID', ''),
        'webhook_secret' => env('DCB_WEBHOOK_SECRET', ''),
    ],
];
