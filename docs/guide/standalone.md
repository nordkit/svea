# Standalone (no Laravel)

The core SDK has zero Laravel dependencies. Use it in Symfony, plain PHP, or any other framework.

```php
use Svea\SveaClient;

$svea = new SveaClient([
    'merchant_id'    => 'abc',
    'shared_secret'  => 'xyz',
    'environment'    => 'test',
    'webhook_secret' => 'whsec_...',
]);

// Property access (services are lazily instantiated)
$checkout = $svea->checkout->create(...);
$svea->admin->order('12345678')->deliver();
$svea->subscriptions->list();
$svea->webhook; // WebhookService
```

## Verifying webhooks without a framework

```php
use Svea\Webhooks\Webhook;
use Svea\Exceptions\SignatureVerificationException;

try {
    $event = Webhook::constructEvent(
        payload:   file_get_contents('php://input'),
        signature: $_SERVER['HTTP_SVEA_SIGNATURE'] ?? '',
        secret:    getenv('SVEA_WEBHOOK_SECRET'),
    );
} catch (SignatureVerificationException $e) {
    http_response_code(400);
    exit;
}
```

See the full [Webhooks reference](../api/webhooks).

