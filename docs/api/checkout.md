# Checkout

The Checkout API creates the customer-facing order, returns a JavaScript snippet to embed, and lets you fetch / update / cancel it.

::: tip Source of truth
Until the docs site grows, the canonical reference for every field, optional flag, and edge case is the [README — Checkout](https://github.com/nordkit/svea/blob/main/README.md#checkout). This page summarises the API surface.
:::

## Methods

| Method | Returns | Notes |
| --- | --- | --- |
| `Svea::checkout()->create($order)` | `CheckoutResponse` | Accepts a `CheckoutOrder` instance **or** a fluent callback (see below). |
| `Svea::checkout()->get($orderId)` | `CheckoutResponse` | Fetch the current state. |
| `Svea::checkout()->update($orderId, $order)` | `CheckoutResponse` | Same input shape as `create()`. |
| `Svea::checkout()->cancel($orderId)` | `void` | |

## Two ways to build a request

Every method that accepts a `CheckoutOrder` supports two equivalent input styles. Use whichever fits the situation.

### 1. Named-constructor style

Best when all the data is known upfront and you want a single statement:

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
```

### 2. Fluent callback style

Best when you need loops, conditionals, or to compose the order across multiple methods. Pass a closure that receives a pre-constructed `CheckoutOrder` and chain setters on it:

```php
$order = Svea::checkout()->create(function (CheckoutOrder $order) use ($cart) {
    $order
        ->currency('SEK')
        ->countryCode('SE')
        ->locale('sv-SE')
        ->clientOrderNumber($cart->reference)
        ->merchantSettings(fn (MerchantSettings $s) => $s
            ->pushUri(route('webhooks.svea'))
            ->termsUri(route('terms'))
            ->confirmationUri(route('checkout.confirmation'))
            ->checkoutUri(route('checkout')));

    foreach ($cart->items as $item) {
        $order->addRow(fn (OrderRow $row) => $row
            ->sku($item->sku)
            ->name($item->name)
            ->quantity($item->qty * 100)     // minor units: 100 = 1 unit
            ->unitPrice($item->unit_price)   // incl. VAT, minor currency (öre)
            ->vatPercent($item->vat_percent) // 2500 = 25%
            ->unit('st'));
    }

    // Conditional rows — see Conditionable below
    $order->when($cart->has_discount, fn ($o) => $o->addRow(
        fn (OrderRow $r) => $r->sku('DISC')->name('Discount')->unitPrice(-500)->quantity(100)->vatPercent(2500)
    ));
});
```

Both forms produce identical HTTP requests. Mix freely across `create()`, `update()`, and tests.

### Currencies & locales

Svea Checkout supports several Nordic markets. Set `currency()`, `locale()`, and `countryCode()` together so the checkout matches the merchant market:

```php
// Sweden
CheckoutOrder::make()->currency('SEK')->locale('sv-SE')->countryCode('SE');

// Norway
CheckoutOrder::make()->currency('NOK')->locale('nn-NO')->countryCode('NO');

// Denmark
CheckoutOrder::make()->currency('DKK')->locale('da-DK')->countryCode('DK');

// Finland
CheckoutOrder::make()->currency('EUR')->locale('fi-FI')->countryCode('FI');
```

## Conditional builders — `when()` / `unless()`

Every builder (`CheckoutOrder`, `MerchantSettings`, `OrderRow`, `AdminOrderRequest`, `AdminOrderRow`, `CreditRequest`) uses the `Conditionable` trait. Inline branching without breaking the chain:

```php
Svea::checkout()->create(function (CheckoutOrder $order) use ($cart, $isB2B) {
    $order
        ->currency('SEK')
        ->locale('sv-SE')
        ->when($isB2B, fn ($o) => $o->requireElectronicIdAuthentication())
        ->unless($isB2B, fn ($o) => $o->metadata(['segment' => 'consumer']))
        ->when(
            $cart->has_promo,
            fn ($o) => $o->merchantData("promo:{$cart->promo_code}"),
            fn ($o) => $o->merchantData('no-promo'), // optional else branch
        );
});
```

`when($condition, $then, $else = null)` calls `$then($builder)` if the condition is truthy, otherwise the optional `$else($builder)`. `unless()` is the inverse. Both return the builder so chaining continues seamlessly.

## Response helpers

```php
$order = Svea::checkout()->get('12345678');

$order->id();                          // '12345678'
$order->status();                      // 'Created' | 'Final' | 'Cancelled'
$order->snippet();                     // '<script>...</script>'
$order->successful();
$order->getLastResponse()->statusCode;
```

## Builders reference

- **`CheckoutOrder`** — `currency()`, `locale()`, `countryCode()`, `clientOrderNumber()`, `merchantSettings()`, `addRow()`, plus optional `merchantData()`, `partnerKey()`, `recurring()`, `requireElectronicIdAuthentication()`, `metadata()`
- **`MerchantSettings`** — `pushUri()`, `termsUri()`, `confirmationUri()`, `checkoutUri()`
- **`OrderRow`** — `sku()`, `name()`, `quantity()`, `unitPrice()`, `vatPercent()`, `discountPercent()`, `unit()`

All builders use the **minor-unit** convention (`100` = 1 unit, `2500` = 25%).
