# Checkout

The Checkout API creates the customer-facing order, returns a JavaScript snippet to embed, and lets you fetch / update / cancel it.

::: tip Source of truth
Until the docs site grows, the canonical reference for every field, optional flag, and edge case is the [README — Checkout](https://github.com/nordkit/svea/blob/main/README.md#checkout). This page summarises the API surface.
:::

## Methods

| Method | Returns | Notes |
| --- | --- | --- |
| `Svea::checkout()->create($order)` | `CheckoutResponse` | Accepts a `CheckoutOrder` instance or a fluent callback. |
| `Svea::checkout()->get($orderId)` | `CheckoutResponse` | Fetch the current state. |
| `Svea::checkout()->update($orderId, $order)` | `CheckoutResponse` | Same input shape as `create()`. |
| `Svea::checkout()->cancel($orderId)` | `void` | |

## Response helpers

```php
$order = Svea::checkout()->get('12345678');

$order->id();      // '12345678'
$order->status();  // 'Created' | 'Final' | 'Cancelled'
$order->snippet(); // '<script>...</script>'
$order->successful();
$order->getLastResponse()->statusCode;
```

## Builders

- `CheckoutOrder` — `currency()`, `locale()`, `countryCode()`, `clientOrderNumber()`, `merchantSettings()`, `addRow()`, plus optional `merchantData()`, `partnerKey()`, `recurring()`, `requireElectronicIdAuthentication()`, `metadata()`
- `MerchantSettings` — `pushUri()`, `termsUri()`, `confirmationUri()`, `checkoutUri()`
- `OrderRow` — `sku()`, `name()`, `quantity()`, `unitPrice()`, `vatPercent()`, `discountPercent()`, `unit()`

All builders use the **minor-unit** convention (`100` = 1 unit, `2500` = 25%).

