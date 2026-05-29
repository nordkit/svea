# Migration from `sveaekonomi/checkout`

This guide helps existing projects move from Svea's older official PHP Checkout
SDK, [`sveaekonomi/checkout`](https://github.com/sveawebpay/php-checkout), to
`nordkit/svea`.

The high-level migration is:

1. replace the old connector/client setup with package configuration;
2. convert array payloads to typed builders or fluent callbacks;
3. move post-checkout operations from `CheckoutAdminClient` to `Svea::admin()`;
4. add webhook verification through `Svea::webhook()` or `Webhook::constructEvent()`;
5. cover the migrated flows with `Svea::fake()` tests.

## Why migrate

- PHP 8.2+ support with strict types and modern value objects.
- Fluent builders for checkout, admin, subscription, and webhook flows.
- Laravel-native service provider, facade, config publishing, and test fakes.
- Typed exceptions such as `SveaApiException` and `SignatureVerificationException`.
- Framework-agnostic core for non-Laravel applications.
- Built-in retry, timeout, idempotency, and fake-client testing support.

## Install and configure

Replace the old package:

```bash
composer remove sveaekonomi/checkout
composer require nordkit/svea
```

For Laravel applications, publish the config file:

```bash
php artisan vendor:publish --tag=svea-config
```

Then move the old connector values into environment variables:

| Old SDK value | New config / environment |
| --- | --- |
| `checkoutMerchantId` passed to `Connector::init(...)` | `SVEA_MERCHANT_ID` |
| `checkoutSecret` passed to `Connector::init(...)` | `SVEA_SHARED_SECRET` |
| `Connector::TEST_BASE_URL` / `TEST_ADMIN_BASE_URL` | `SVEA_ENVIRONMENT=test` |
| `Connector::PROD_BASE_URL` / `PROD_ADMIN_BASE_URL` | `SVEA_ENVIRONMENT=production` |
| custom checkout base URL | `SVEA_CHECKOUT_URL` |
| custom admin base URL | `SVEA_ADMIN_URL` |
| API timeout constant | `SVEA_TIMEOUT` |
| application-level webhook secret (no old SDK built-in equivalent) | `SVEA_WEBHOOK_SECRET` |

See [Configuration](./configuration.md) for the full `config/svea.php` shape.

## Side-by-side cheat sheet

### Creating a checkout order

Old SDK:

```php
$connector = \Svea\Checkout\Transport\Connector::init(
    $checkoutMerchantId,
    $checkoutSecret,
    \Svea\Checkout\Transport\Connector::TEST_BASE_URL,
);

$client = new \Svea\Checkout\CheckoutClient($connector);

$response = $client->create([
    'countryCode' => 'SE',
    'currency' => 'SEK',
    'locale' => 'sv-SE',
    'clientOrderNumber' => 'ORD-001',
    'merchantSettings' => [
        'termsUri' => 'https://example.com/terms',
        'checkoutUri' => 'https://example.com/checkout',
        'confirmationUri' => 'https://example.com/checkout/confirm',
        'pushUri' => 'https://example.com/webhooks/svea?order={checkout.order.uri}',
    ],
    'cart' => [
        'items' => [
            [
                'articleNumber' => 'SKU-1',
                'name' => 'T-Shirt',
                'quantity' => 100,
                'unitPrice' => 29900,
                'vatPercent' => 2500,
                'unit' => 'st',
            ],
        ],
    ],
]);

$orderId = $response['OrderId'];
$snippet = $response['Gui']['Snippet'];
```

New SDK:

```php
use Svea\Checkout\CheckoutOrder;
use Svea\Checkout\MerchantSettings;
use Svea\Checkout\OrderRow;

$response = Svea::checkout()->create(function (CheckoutOrder $order) {
    $order
        ->countryCode('SE')
        ->currency('SEK')
        ->locale('sv-SE')
        ->clientOrderNumber('ORD-001')
        ->merchantSettings(fn (MerchantSettings $settings) => $settings
            ->termsUri('https://example.com/terms')
            ->checkoutUri('https://example.com/checkout')
            ->confirmationUri('https://example.com/checkout/confirm')
            ->pushUri('https://example.com/webhooks/svea?order={checkout.order.uri}'))
        ->addRow(fn (OrderRow $row) => $row
            ->sku('SKU-1')
            ->name('T-Shirt')
            ->quantity(100)
            ->unitPrice(29900)
            ->vatPercent(2500)
            ->unit('st'));
});

$orderId = $response->id();
$snippet = $response->snippet();
```

### Getting a checkout order

Old SDK:

```php
$response = $client->get(['orderId' => $orderId]);
$status = $response['Status'];
$snippet = $response['Gui']['Snippet'];
```

New SDK:

```php
$response = Svea::checkout()->get($orderId);
$status = $response->status();
$snippet = $response->snippet();
```

### Updating cart rows

Old SDK:

```php
$response = $client->update([
    'orderId' => $orderId,
    'merchantData' => 'updated cart',
    'cart' => [
        'items' => [
            [
                'articleNumber' => 'SKU-1',
                'name' => 'Updated T-Shirt',
                'quantity' => 200,
                'unitPrice' => 29900,
                'vatPercent' => 2500,
            ],
        ],
    ],
]);
```

New SDK:

```php
$response = Svea::checkout()->update($orderId, function (CheckoutOrder $order) {
    $order
        ->merchantData('updated cart')
        ->addRow(fn (OrderRow $row) => $row
            ->sku('SKU-1')
            ->name('Updated T-Shirt')
            ->quantity(200)
            ->unitPrice(29900)
            ->vatPercent(2500));
});
```

### Delivering an order

Old SDK:

```php
$admin = new \Svea\Checkout\CheckoutAdminClient($adminConnector);

$response = $admin->deliverOrder([
    'orderId' => $orderId,
    'orderRowIds' => [],
]);
```

New SDK:

```php
$response = Svea::admin()->order($orderId)->deliver();

// Partial delivery:
$response = Svea::admin()->order($orderId)->deliver(rows: [101, 102]);

$taskUrl = $response->taskReference();
```

### Crediting / refunding

Old SDK:

```php
$response = $admin->creditOrderAmount([
    'orderId' => $orderId,
    'deliveryId' => $deliveryId,
    'creditedAmount' => 2000,
]);

$response = $admin->creditOrderRows([
    'orderId' => $orderId,
    'deliveryId' => $deliveryId,
    'orderRowIds' => [101, 102],
]);
```

New SDK:

```php
Svea::admin()
    ->order($orderId)
    ->delivery($deliveryId)
    ->creditAmount(2000);

Svea::admin()
    ->order($orderId)
    ->delivery($deliveryId)
    ->credit()
    ->rows([101, 102])
    ->send();
```

### Cancelling

Old SDK:

```php
$admin->cancelOrder([
    'orderId' => $orderId,
    'IsCancelled' => true,
]);

$admin->cancelOrderAmount([
    'orderId' => $orderId,
    'cancelledAmount' => 5000,
]);

$admin->cancelOrderRow([
    'orderId' => $orderId,
    'orderRowId' => 101,
]);
```

New SDK:

```php
Svea::admin()->order($orderId)->cancel();
Svea::admin()->order($orderId)->cancelAmount(5000);
Svea::admin()->order($orderId)->cancelRow(101);
```

### Verifying webhook signatures

Old SDK projects often handled webhook verification in application code around
the push endpoint.

New SDK, Laravel:

```php
use Svea\Exceptions\SignatureVerificationException;

try {
    $event = Svea::webhook()->fromRequest($request);
} catch (SignatureVerificationException) {
    abort(400);
}
```

New SDK, framework-agnostic:

```php
use Svea\Webhooks\Webhook;

$event = Webhook::constructEvent(
    payload: file_get_contents('php://input'),
    signature: $_SERVER['HTTP_SVEA_SIGNATURE'] ?? '',
    secret: getenv('SVEA_WEBHOOK_SECRET'),
);
```

## Response mapping

The old SDK returns arrays. The new SDK wraps responses in small helper objects:

| Old array access | New helper |
| --- | --- |
| `$response['OrderId']` | `$response->id()` |
| `$response['Gui']['Snippet']` | `$response->snippet()` |
| `$response['Status']` | `$response->status()` |
| admin order `Actions` array checks | `$adminOrder->canDeliver()`, `canCredit()`, `canCancel()` |
| async task header / location | `$response->taskReference()` |

## Gotchas

- **Minor units stay important.** Checkout and Admin examples use Svea's minor
  units (`100` = one quantity unit, `2500` = 25% VAT, `29900` = 299.00 SEK).
- **Checkout and Admin row builders differ.** `OrderRow` is for checkout order
  creation/update. `AdminOrderRow` is for post-checkout row mutations.
- **Admin operations may be asynchronous.** Deliver, credit, and some row
  mutations can return a task reference. Poll it with `Svea::admin()->task($url)`
  in a queue/job instead of blocking the checkout request.
- **Check available actions before mutating final orders.** Use
  `Svea::admin()->order($id)->get()` and helpers such as `canDeliver()` before
  calling delivery, credit, or cancel operations.
- **Cancel is a `PATCH` operation in Svea Payment Admin.** Use the SDK method
  (`cancel()`, `cancelAmount()`, or `cancelRow()`) instead of hard-coding HTTP
  verbs in migrated code.
- **Exception handling changes.** Replace old `SveaInputValidationException`,
  `SveaConnectorException`, and generic array error handling with the
  `Svea\Exceptions\*` hierarchy documented in [Error Handling](./error-handling.md).
- **Webhook secret is separate.** `SVEA_WEBHOOK_SECRET` verifies inbound
  webhook signatures. It is not the same as `SVEA_SHARED_SECRET`, which signs
  outbound API calls.

## Testing the migrated flow

For Laravel applications, replace live checkout calls in tests with
`Svea::fake()`:

```php
use Svea\Checkout\CheckoutResponse;

Svea::fake([
    'checkout.create' => CheckoutResponse::make([
        'OrderId' => '12345678',
        'Gui' => ['Snippet' => '<div id="svea-checkout"></div>'],
    ]),
]);

// Run your checkout action...

Svea::assertCheckoutCreated();
```

See [Testing & Fakes](./testing.md) for admin, task, subscription, and
`preventStrayRequests()` examples.
