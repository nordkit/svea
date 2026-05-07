# Configuration

## Environment variables

| Variable | Required | Description |
| --- | --- | --- |
| `SVEA_MERCHANT_ID` | ✅ | Your Svea merchant ID |
| `SVEA_SHARED_SECRET` | ✅ | Outbound API HMAC secret |
| `SVEA_ENVIRONMENT` | ✅ | `test` or `production` |
| `SVEA_WEBHOOK_SECRET` | ✅ | Inbound webhook signature secret |
| `SVEA_SUBSCRIPTION_CALLBACK_URL` | — | Default callback URL for subscriptions |
| `SVEA_MAX_RETRIES` | — | Retry attempts on 429/500/503 (default: `0`) |
| `SVEA_TIMEOUT` | — | HTTP timeout in seconds (default: `10`) |
| `SVEA_CHECKOUT_URL` | — | Override Checkout API base URL |
| `SVEA_ADMIN_URL` | — | Override Admin API base URL |
| `SVEA_SUBSCRIPTIONS_URL` | — | Override Subscriptions API base URL |

## `config/svea.php`

```php
return [
    'merchant_id'    => env('SVEA_MERCHANT_ID'),
    'shared_secret'  => env('SVEA_SHARED_SECRET'),
    'environment'    => env('SVEA_ENVIRONMENT', 'test'),
    'webhook_secret' => env('SVEA_WEBHOOK_SECRET'),
    'subscription_callback_url' => env('SVEA_SUBSCRIPTION_CALLBACK_URL'),
    'max_retries'    => env('SVEA_MAX_RETRIES', 0),
    'timeout'        => env('SVEA_TIMEOUT', 10),

    'base_urls' => [
        'checkout'      => env('SVEA_CHECKOUT_URL'),
        'admin'         => env('SVEA_ADMIN_URL'),
        'subscriptions' => env('SVEA_SUBSCRIPTIONS_URL'),
    ],
];
```

## Override base URLs

Useful for pointing at a local mock server during development:

```ini
SVEA_CHECKOUT_URL=http://localhost:8080
SVEA_ADMIN_URL=http://localhost:8080
SVEA_SUBSCRIPTIONS_URL=http://localhost:8080
```

