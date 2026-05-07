# Quick Start

::: tip Source of truth
The full quick-start lives in the [README](https://github.com/nordkit/svea/blob/main/README.md#quick-start). This page links to the most common flows.
:::

## Minor-unit convention

All numeric values follow Svea's **minor-unit** convention:

- `quantity` — `100` = 1 unit, `300` = 3 units
- `unitPrice` — `29900` = 299.00 SEK
- `vatPercent` — `2500` = 25%, `1900` = 19%
- `discountPercent` — `1000` = 10%

## Create a checkout order

```php
use Svea\Checkout\Cart;
use Svea\Checkout\CheckoutOrder;
use Svea\Checkout\MerchantSettings;
use Svea\Checkout\OrderRow;

$order = Svea::checkout()->create(new CheckoutOrder(
    currency: 'SEK',
    countryCode: 'SE',
    locale: 'sv-SE',
    clientOrderNumber: 'ORD-001',
    merchantSettings: new MerchantSettings(
        pushUri: route('webhooks.svea'),
        termsUri: route('terms'),
        confirmationUri: route('checkout.confirmation'),
        checkoutUri: route('checkout'),
    ),
    cart: new Cart([
        new OrderRow(quantity: 100, unitPrice: 29900, vatPercent: 2500, sku: 'TSHIRT-BLK-M', name: 'T-Shirt Black M'),
    ]),
));

$order->id();      // '12345678'
$order->snippet(); // '<script>...</script>' — embed in your checkout page
```

See the full [Checkout reference](../api/checkout) for the fluent callback style and all optional fields, and [Fluent Builders & Conditionable](./fluent-builders) for the full pattern across every API surface.

## Deliver and credit

```php
$deliver = Svea::admin()->order('12345678')->deliver();
$task    = Svea::admin()->order('12345678')->delivery($deliver->deliveryId())->creditAmount(9900);
```

See the [Payment Admin reference](../api/admin).

## Verify a webhook

```php
$event = Svea::webhook()->fromRequest($request); // throws SignatureVerificationException on mismatch
```

See the [Webhooks reference](../api/webhooks).

