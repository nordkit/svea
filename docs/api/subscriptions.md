# Subscriptions

Webhook subscriptions are how Svea notifies your application when order lifecycle events occur. Register a HTTPS endpoint once per merchant; Svea pushes a signed JSON payload to that URL whenever a subscribed event fires.

::: warning Subscriptions vs task polling
These are two different mechanisms. Subscriptions are **Svea → you (push)**, fired on lifecycle changes. Task polling is **you → Svea (pull)**, used to wait for an async Admin operation to finish. See the [README disambiguation table](https://github.com/nordkit/svea/blob/main/README.md#subscriptions).
:::

## Event types

| `EventType` case | Svea event string | When it fires |
| --- | --- | --- |
| `CheckoutOrderCreated` | `CheckoutOrder.Created` | Order created |
| `CheckoutOrderUpdated` | `CheckoutOrder.Updated` | Order edited or sync |
| `CheckoutOrderDelivered` | `CheckoutOrder.Delivered` | Captured |
| `CheckoutOrderCreditSucceeded` | `CheckoutOrder.CreditSucceeded` | Credit succeeded |
| `CheckoutOrderCreditFailed` | `CheckoutOrder.CreditFailed` | Credit failed |
| `CheckoutOrderClosed` | `CheckoutOrder.Closed` | Cancelled or expired |
| `CheckoutOrderPendingStatusReleased` | `CheckoutOrder.PendingStatusReleased` | Pending order approved |
| `StandaloneOrderPendingStatusReleased` | `StandaloneOrder.PendingStatusReleased` | Standalone pending order approved |
| `StandaloneOrderClosed` | `StandaloneOrder.Closed` | Standalone order closed |
| `Ping` | `Ping` | Sent by `verify()` to confirm reachability |

::: warning Checkout `Finalized` is not a subscription event
When a customer completes payment, Svea POSTs `{"type": "Finalized"}` to the per-order **`pushUri`** configured on `MerchantSettings` — not via the subscription system. Your `pushUri` endpoint must then call `Svea::admin()->order($orderId)->get()` to read the Payment Admin status.
:::

## Registration

```php
use Svea\Subscriptions\EventType;

$subscription = Svea::subscriptions()->add(
    callbackUrl: 'https://myapp.com/webhooks/svea',
    eventTypes: [
        EventType::CheckoutOrderCreated,
        EventType::CheckoutOrderDelivered,
        EventType::CheckoutOrderCreditSucceeded,
        EventType::CheckoutOrderCreditFailed,
        EventType::CheckoutOrderClosed,
    ],
);

Svea::subscriptions()->verify($subscription->id());
```

Or via the fluent builder (calls `verify()` automatically):

```php
$subscription = Svea::subscriptions()
    ->on(EventType::CheckoutOrderCreated, EventType::CheckoutOrderDelivered)
    ->notifyAt('https://myapp.com/webhooks/svea')
    ->register();
```

## CRUD

```php
Svea::subscriptions()->list();
Svea::subscriptions()->get('sub-id');
Svea::subscriptions()->update('sub-id', 'https://...', [EventType::CheckoutOrderCreated]);
Svea::subscriptions()->verify('sub-id');
Svea::subscriptions()->remove('sub-id');
```

> **Re-verification:** Changing a subscription's URL invalidates verification — call `verify()` again before events resume.

## Inspect

```php
$subscription->id();           // 'fbb6c74a-...'
$subscription->callbackUrl();
$subscription->events();       // EventType[]
$subscription->isVerified();
$subscription->createdAt();
```

## Manage via Artisan

In a Laravel app you can manage subscriptions without writing code — see [Laravel → Artisan commands](../guide/laravel#artisan-commands).

