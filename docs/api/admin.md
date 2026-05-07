# Payment Admin

The Payment Admin API mutates orders after checkout: deliver (capture), cancel, credit (refund), and modify rows. Mutating operations are **asynchronous** (HTTP 202) — they return a task reference URL you can poll.

::: tip Source of truth
The full reference with every example lives in the [README — Admin](https://github.com/nordkit/svea/blob/main/README.md#admin).
:::

## Order operations

```php
$req = Svea::admin()->order('12345678');

$req->get();                          // AdminOrderResponse
$req->deliver();                      // DeliverResponse — all rows
$req->deliver(rows: [101, 102]);      // partial
$req->cancel();
$req->cancelAmount(50000);
$req->cancelRow(rowId: 101);
$req->withIdempotencyKey('key')->deliver();
```

## Delivery operations (credit / refund)

```php
$delivery = Svea::admin()->order('12345678')->delivery(456);

$delivery->credit()->rows([101, 102])->send();
$delivery->credit()->newRow(fn ($row) => $row->name('Return fee')->unitPrice(5000)->quantity(100)->vatPercent(2500))->send();
$delivery->creditAmount(9900);
```

## Modify order rows

All three row-mutation methods take **fluent callbacks** that build an `AdminOrderRow`:

```php
// Add a new row — returns ['order_row_id' => int, 'task_reference' => string]
$result = Svea::admin()->order('12345678')->addOrderRow(function (AdminOrderRow $row) {
    $row->name('Extra item')
        ->sku('EXTRA-1')
        ->unitPrice(5000)
        ->quantity(100)
        ->vatPercent(2500)
        ->unit('st');
});

// Update one row by ID
Svea::admin()->order('12345678')->updateOrderRow(rowId: 101, callback: function (AdminOrderRow $row) {
    $row->unitPrice(4500)->name('Updated name');
});

// Replace ALL rows — pass one callback per replacement row
Svea::admin()->order('12345678')->replaceOrderRows(
    fn (AdminOrderRow $row) => $row->name('Widget')->sku('WGT-1')->unitPrice(9900)->quantity(100)->vatPercent(2500),
    fn (AdminOrderRow $row) => $row->name('Shipping')->sku('SHIP')->unitPrice(4900)->quantity(100)->vatPercent(2500),
);
```

`AdminOrderRow` does **not** apply the SDK-level minor-unit conversion that Checkout's `OrderRow` does — pass already-scaled values (`100` = 1 unit, `2500` = 25%).

## Conditional builders — `when()` / `unless()`

`AdminOrderRequest` and `AdminOrderRow` both use the `Conditionable` trait. Inline branching without breaking the chain:

```php
Svea::admin()
    ->order($externalOrderId)
    ->withIdempotencyKey('capture-' . $payment->id)
    ->when(
        ! empty($partialRows),
        fn ($req) => $req->deliver(rows: $partialRows),
        fn ($req) => $req->deliver(),  // optional else branch
    );

// Inside row callbacks too
Svea::admin()->order('12345678')->addOrderRow(fn (AdminOrderRow $row) => $row
    ->name($item->name)
    ->unitPrice($item->price)
    ->quantity(100)
    ->vatPercent(2500)
    ->when($item->is_discounted, fn ($r) => $r->discountPercent(1000))
    ->unless($item->is_taxable, fn ($r) => $r->vatPercent(0))
);
```

`when($condition, $then, $else = null)` calls `$then($builder)` if truthy, otherwise the optional `$else($builder)`. `unless()` is the inverse. Both return the builder.

## Polling tasks

```php
$deliver = Svea::admin()->order('12345678')->deliver();
$taskUrl = $deliver->taskReference();

do {
    sleep(1);
    $task = Svea::admin()->task($taskUrl);
} while ($task->pending());

$task->completed(); // bool
$task->failed();    // bool
$task->resource;    // string|null — URL to the resulting resource
```

> **In production** run the poll loop inside a queued job with retries rather than blocking an HTTP request.

## `AdminOrderResponse` helpers

```php
$adminOrder = Svea::admin()->order('12345678')->get();

$adminOrder->status();             // SveaOrderStatus enum
$adminOrder->actions();            // string[]
$adminOrder->canDeliver();
$adminOrder->canCredit();
$adminOrder->canCancel();
$adminOrder->deliveries();         // array
$adminOrder->delivery(456);
$adminOrder->deliveryRowIds(456);
$adminOrder->hasAction('CanDeliverOrder');
$adminOrder->hasStatus('Open');
```

