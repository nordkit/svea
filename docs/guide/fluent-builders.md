# Fluent Builders & Conditionable

A defining feature of `nordkit/svea` is its **fluent, callback-driven API**. Most request-shaping methods accept either:

1. A fully constructed value object (named-constructor style), **or**
2. A closure that receives a pre-constructed builder you mutate via chained setters.

Both forms produce identical HTTP requests. Pick whichever fits the situation.

## Why two styles?

| Style | Best for |
| --- | --- |
| **Named constructor** (`new CheckoutOrder(currency: 'SEK', ...)`) | All data known upfront, single statement, IDE autocomplete on parameters |
| **Fluent callback** (`->create(fn ($o) => $o->currency('SEK')->...)`) | Loops, conditionals, composition across helper methods, dynamic builds |

## Methods that accept a fluent callback

| Surface | Method | Builder type |
| --- | --- | --- |
| Checkout | `Svea::checkout()->create(...)` | `CheckoutOrder` |
| Checkout | `Svea::checkout()->update($id, ...)` | `CheckoutOrder` |
| Checkout | `CheckoutOrder->merchantSettings(...)` | `MerchantSettings` |
| Checkout | `CheckoutOrder->addRow(...)` | `OrderRow` |
| Admin | `AdminOrderRequest->addOrderRow(...)` | `AdminOrderRow` |
| Admin | `AdminOrderRequest->updateOrderRow($id, ...)` | `AdminOrderRow` |
| Admin | `AdminOrderRequest->replaceOrderRows(...)` | `AdminOrderRow` (variadic) |
| Admin | `CreditRequest->newRow(...)` | `AdminOrderRow` |
| Subscriptions | `SubscriptionBuilder->...` | `SubscriptionBuilder` (chainable directly) |

## Worked example — Checkout with loops and conditions

```php
use Svea\Checkout\CheckoutOrder;
use Svea\Checkout\MerchantSettings;
use Svea\Checkout\OrderRow;

$order = Svea::checkout()->create(function (CheckoutOrder $order) use ($cart, $user) {
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
            ->quantity($item->qty * 100)
            ->unitPrice($item->unit_price)
            ->vatPercent($item->vat_percent)
            ->unit('st'));
    }

    $order
        ->when($user->is_b2b, fn ($o) => $o->requireElectronicIdAuthentication())
        ->unless($user->is_b2b, fn ($o) => $o->metadata(['segment' => 'consumer']))
        ->when($cart->has_discount, fn ($o) => $o->addRow(
            fn (OrderRow $r) => $r
                ->sku('DISC')
                ->name('Discount')
                ->unitPrice(-500)
                ->quantity(100)
                ->vatPercent(2500)
        ));
});
```

## `when()` / `unless()` — the `Conditionable` trait

Every fluent builder in the SDK uses the framework-agnostic `Svea\Support\Conditionable` trait — no Laravel dependency required.

```php
$builder->when($condition, $then, $else = null);
$builder->unless($condition, $then, $else = null);
```

- `$condition` — any truthy/falsy value (closures are also evaluated)
- `$then` — `Closure(self): void|self` invoked when the condition matches
- `$else` — *(optional)* `Closure(self): void|self` invoked otherwise

Both methods always return the builder so the chain continues. Use them anywhere — top-level builders, nested row builders, even conditional `merchantSettings` overrides.

```php
Svea::admin()
    ->order($id)
    ->withIdempotencyKey($key)
    ->when($isPartial, fn ($r) => $r->deliver(rows: $rowIds))
    ->unless($isPartial, fn ($r) => $r->deliver());
```

## Both styles in tests

Inside `Svea::fake()` callbacks the builders are passed pre-constructed, so test code typically uses the fluent style. See the [Testing & Fakes guide](./testing.md).

## When the styles diverge

Most builders cover both styles 1:1. A few details to know:

- **`OrderRow` (Checkout)** uses Svea's native minor-unit format. Pass already-scaled values (`100` = 1 item, `2500` = 25%, `1000` = 10%).
- **`AdminOrderRow` (Admin)** uses the same minor-unit format for row mutations. Pass already-scaled values (`100`, `2500`, `1000`).
- **`replaceOrderRows()`** is variadic: pass one closure per replacement row, no array wrapping needed.
- **`CreditRequest`** chains state — call `rows([...])` *and/or* one or more `newRow(...)` callbacks before `->send()`.

See the [Checkout](../api/checkout.md) and [Admin](../api/admin.md) reference pages for surface-specific examples.
