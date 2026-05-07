# Webhooks

Inbound webhook verification uses HMAC-SHA256 with timing-safe comparison.

## Framework-agnostic

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

## Laravel

```php
use Svea\Laravel\WebhookService;

$event = $webhookService->fromRequest($request); // throws SignatureVerificationException
```

Or via the facade: `Svea::webhook()->fromRequest($request)`.

## Working with the event

```php
$event->type;         // EventType enum
$event->orderId;      // string
$event->deliveryId;   // string|null
$event->occurredAt;   // \DateTimeImmutable

match ($event->type()) {
    EventType::CheckoutOrderDelivered       => $this->handleDelivered($event),
    EventType::CheckoutOrderCreditSucceeded => $this->handleCredited($event),
    EventType::CheckoutOrderClosed          => $this->handleClosed($event),
    default                                 => null,
};
```

## Decoupling with a Laravel event

```php
use Svea\Laravel\Events\SveaWebhookReceived;

SveaWebhookReceived::dispatch($event);
```

Register listeners as usual; see [Laravel integration](../guide/laravel#webhook-event).

