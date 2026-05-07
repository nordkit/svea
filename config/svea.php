<?php

declare(strict_types=1);

return [
    'merchant_id' => env('SVEA_MERCHANT_ID'),
    'shared_secret' => env('SVEA_SHARED_SECRET'),
    'environment' => env('SVEA_ENVIRONMENT', 'test'), // 'test' | 'production'
    'webhook_secret' => env('SVEA_WEBHOOK_SECRET'),
    'subscription_callback_url' => env('SVEA_SUBSCRIPTION_CALLBACK_URL'),
    'max_retries' => env('SVEA_MAX_RETRIES', 0),
    'timeout' => env('SVEA_TIMEOUT', 10),

    /**
     * Override base URLs per API surface. When null, the built-in defaults are used.
     * Useful for local development pointing at a mock server.
     *
     * SVEA_CHECKOUT_URL=http://localhost:8080
     * SVEA_ADMIN_URL=http://localhost:8081
     * SVEA_SUBSCRIPTIONS_URL=http://localhost:8082
     */
    'base_urls' => [
        'checkout' => env('SVEA_CHECKOUT_URL'),
        'admin' => env('SVEA_ADMIN_URL'),
        'subscriptions' => env('SVEA_SUBSCRIPTIONS_URL'),
    ],

    'webhook' => [
        /**
         * Maximum number of job attempts for processing a webhook notification.
         * Covers Svea's 4002 race condition (order not yet available in Admin API).
         * Default: 4 tries → total window ~3.5 minutes.
         */
        'tries' => (int) env('SVEA_WEBHOOK_TRIES', 4),

        /**
         * Seconds between retry attempts: 10s → 30s → 60s → 120s
         * Expressed as a comma-separated string so it can be overridden via env.
         *
         * @var list<int>
         */
        'backoff' => array_map('intval', explode(',', env('SVEA_WEBHOOK_BACKOFF', '10,30,60,120'))),
    ],
];
