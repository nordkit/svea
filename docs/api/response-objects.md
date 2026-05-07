# Response Objects

Every API call returns a `SveaResource` — a typed, **read-only**, array-accessible object.

## Access patterns

```php
$order = Svea::checkout()->get('12345678');

$order->status();                      // named getter (preferred in typed code)
$order->status;                        // magic property access
$order['status'];                      // ArrayAccess read
$order->successful();                  // bool helper
$order->getLastResponse()->statusCode; // int — raw HTTP status
$order->getLastResponse()->headers;    // array
$order->getLastResponse()->body;       // string
```

## Read-only

Attempting `$order['key'] = $value` or `unset($order['key'])` throws `\BadMethodCallException`. Response objects are immutable snapshots.

## Common subclasses

| Class | Returned by |
| --- | --- |
| `CheckoutResponse` | `Svea::checkout()->create() / get() / update()` |
| `AdminOrderResponse` | `Svea::admin()->order(...)->get()` |
| `DeliverResponse` | `Svea::admin()->order(...)->deliver(...)` |
| `TaskResponse` | `Svea::admin()->task(...)` |
| `Subscription` | `Svea::subscriptions()->get() / add() / update()` |
| `WebhookEvent` | `Svea::webhook()->fromRequest()` / `Webhook::constructEvent()` |

