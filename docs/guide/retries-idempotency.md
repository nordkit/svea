# Retries & Idempotency

## Opt-in retries with exponential backoff

```php
$svea = new SveaClient([
    'merchant_id'   => '...',
    'shared_secret' => '...',
    'environment'   => 'production',
    'max_retries'   => 2,  // default: 0 (opt-in)
    'timeout'       => 10,
]);
```

`RetryMiddleware` retries on `ConnectionException` and HTTP **429 / 500 / 503** with exponential backoff and random jitter. With `max_retries=2`: attempt 1 → ~2 s, attempt 2 → ~4 s.

## Per-request idempotency keys

Prevent double-captures on queue retries:

```php
$deliver = Svea::admin()
    ->order('12345678')
    ->withIdempotencyKey('capture-' . $paymentEvent->id)
    ->deliver(rows: [101, 102]);

$deliver->deliveryId();    // int
$deliver->taskReference(); // string|null — poll via Svea::admin()->task(...)
```

Idempotency keys are forwarded as the `Idempotency-Key` header on all mutating Admin operations.

