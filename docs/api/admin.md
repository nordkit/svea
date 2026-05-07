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

```php
Svea::admin()->order('12345678')->addOrderRow(fn ($row) => $row->name('Extra')->unitPrice(5000)->quantity(100)->vatPercent(2500));
Svea::admin()->order('12345678')->updateOrderRow(rowId: 101, callback: fn ($row) => $row->unitPrice(4500));
Svea::admin()->order('12345678')->replaceOrderRows(
    fn ($row) => $row->name('Widget')->sku('WGT-1')->unitPrice(9900)->quantity(100)->vatPercent(2500),
);
```

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

