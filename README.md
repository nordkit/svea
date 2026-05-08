# `nordkit/svea` — Modern PHP SDK for Svea Checkout

A ground-up PHP SDK for Svea's APIs: **Checkout**, **Payment Admin**, **Webhook Subscriptions**, and **inbound Webhook verification** — with a fluent, expressive API, a full Laravel integration, and a first-class testing layer.

[![Packagist Version](https://img.shields.io/packagist/v/nordkit/svea.svg?style=flat-square)](https://packagist.org/packages/nordkit/svea)
[![Total Downloads](https://img.shields.io/packagist/dt/nordkit/svea.svg?style=flat-square)](https://packagist.org/packages/nordkit/svea)
[![Tests](https://img.shields.io/github/actions/workflow/status/nordkit/svea/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/nordkit/svea/actions/workflows/tests.yml)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%206-brightgreen.svg?style=flat-square)](phpstan.neon)
[![PHP Version](https://img.shields.io/packagist/php-v/nordkit/svea.svg?style=flat-square)](composer.json)
[![Laravel](https://img.shields.io/badge/Laravel-11%20%7C%2012%20%7C%2013-red.svg?style=flat-square)](composer.json)
[![License](https://img.shields.io/packagist/l/nordkit/svea.svg?style=flat-square)](LICENSE.md)

> 📖 **Official Svea API documentation:** [paymentsdocs.svea.com](https://paymentsdocs.svea.com/)

---

## At a glance

| Feature | Status |
|---|---|
| **Checkout API** — create, get, update, cancel orders | ✅ |
| **Payment Admin API** — deliver, cancel, credit, modify rows | ✅ |
| **Webhook Subscriptions** — full CRUD + verification | ✅ |
| **Inbound Webhook verification** — HMAC-SHA256, timing-safe | ✅ |
| **Laravel integration** — service provider, facade, Artisan commands | ✅ |
| **Test doubles** — `Svea::fake()` with assertion helpers (Http::fake-style) | ✅ |
| **Idempotency keys** — safe queue retries on Admin operations | ✅ |
| **Retries** — opt-in exponential backoff on 429 / 5xx | ✅ |
| **Async task polling** — typed `TaskResponse` for HTTP 202 operations | ✅ |
| **Conditionable** — `when()` / `unless()` for fluent branching | ✅ |
| **Typed exceptions** — `SveaApiException` hierarchy with status code & body | ✅ |
| **Strict types & `final readonly` value objects** — PHPStan level 6, zero errors | ✅ |
| **PHP support** — 8.2, 8.3, 8.4, 8.5 | ✅ |
| **Framework-agnostic core** — Laravel optional, runs anywhere | ✅ |

---

## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Authentication](#authentication)
- [Configuration](#configuration)
- [Laravel Integration](#laravel-integration)
  - [Artisan Commands](#artisan-commands)
- [API Reference](#api-reference)
  - [Checkout](#checkout)
  - [Admin](#admin)
  - [Subscriptions](#subscriptions)
  - [Webhooks](#webhooks)
- [Testing](#testing)
- [Advanced Usage](#advanced-usage)
- [Error Handling](#error-handling)
- [Response Objects](#response-objects)
- [Package Structure](#package-structure)
- [Contributing](#contributing)

---

## Requirements

| Dependency | Version |
|---|---|
| PHP | ^8.2 |
| `guzzlehttp/guzzle` | ^7.8 |
| `illuminate/support` *(optional)* | ^11.0 \| ^12.0 \| ^13.0 — required for the Laravel facade and service provider |

---

## Installation

```bash
composer require nordkit/svea
```

**Laravel** — the service provider and facade are auto-discovered. Publish the config file:

```bash
php artisan vendor:publish --tag=svea-config
```

**Standalone (no Laravel)** — instantiate `SveaClient` directly with a config array (see [Configuration](#configuration)).

---

## Quick Start

> 💡 **Prefer learning by example?** Check out [`nordkit/svea-example-laravel`](https://github.com/nordkit/svea-example-laravel) — a minimal Laravel 13 app demonstrating the full **cart → checkout → webhook** flow, with a feature test suite using `Svea::fake()`.

### Create a checkout order

All numeric values follow Svea's **minor-unit convention**:
- `quantity` — `100` = 1 unit, `300` = 3 units
- `unitPrice` — `29900` = 299.00 SEK (minor currency, e.g. öre)
- `vatPercent` — `2500` = 25%, `1900` = 19%
- `discountPercent` — `1000` = 10%

Common Nordic checkout defaults:

| Market | Currency | Locale | Country code |
|---|---|---|---|
| Sweden | `SEK` | `sv-SE` | `SE` |
| Norway | `NOK` | `nn-NO` | `NO` |
| Denmark | `DKK` | `da-DK` | `DK` |
| Finland | `EUR` | `fi-FI` | `FI` |

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
        new OrderRow(quantity: 200, unitPrice: 89900, vatPercent: 2500, sku: 'SNEAKER-WHT-42', name: 'Sneakers White 42'),
    ]),
));

$order->id();        // '12345678' — store this as your Svea order ID
$order->snippet();   // '<script>...</script>' — embed in your checkout page
$order->status();    // 'Created' | 'Final' | 'Cancelled'
```

### Fluent callback style

Great for composable builds and `when()` branches:

```php
$order = Svea::checkout()->create(function (CheckoutOrder $order) use ($cart) {
    $order
        ->currency('SEK')
        ->locale('sv-SE')
        ->countryCode('SE')
        ->clientOrderNumber($cart->order_number)
        ->merchantSettings(fn (MerchantSettings $s) => $s
            ->pushUri(route('webhooks.svea'))
            ->termsUri(route('terms'))
            ->confirmationUri(route('checkout.confirmation'))
            ->checkoutUri(route('checkout')));

    foreach ($cart->items as $item) {
        $order->addRow(function (OrderRow $row) use ($item) {
            $row->sku($item->sku)
                ->name($item->name)
                ->quantity($item->qty)
                ->unitPrice($item->unit_price)   // incl. VAT, minor currency (öre)
                ->vatPercent($item->vat_percent) // minor units: 2500 = 25%
                ->unit('st');
        });
    }
});
```

### Conditional chaining with `when()`

```php
Svea::admin()->order('12345678')
    ->withIdempotencyKey($payment->id)
    ->when($isPartialDelivery,
        fn ($req) => $req->deliver(rows: $rowIds),
        fn ($req) => $req->deliver(),  // else branch
    );
```

### Standalone (no Laravel)

```php
use Svea\SveaClient;

$svea = new SveaClient([
    'merchant_id'    => 'abc',
    'shared_secret'  => 'xyz',
    'environment'    => 'test',
    'webhook_secret' => 'whsec_...',
]);

$svea->checkout->create(...);
$svea->admin->order('12345678')->deliver();
```

---

## Authentication

### Outbound API requests

All three outbound APIs (Checkout, Admin, Subscriptions) use Svea's **HMAC-SHA512** digest:

```
Authorization: SveaCheckoutGateway {merchantId} {base64(sha512(body + sharedSecret))}
```

`SveaConnector` computes and attaches this header automatically on every request using `merchant_id` and `shared_secret` from config.

### Inbound webhook verification

`webhook_secret` is a **separate** secret used only to verify the `Svea-Signature` header on inbound webhook pushes — it is **not** the same as `shared_secret`.

```
Svea-Signature: HMAC-SHA256(raw body, webhook_secret)
```

---

## Configuration

### Environment variables

Add these to your `.env` file:

| Variable | Required | Description |
|---|---|---|
| `SVEA_MERCHANT_ID` | ✅ | Your Svea merchant ID |
| `SVEA_SHARED_SECRET` | ✅ | Outbound API HMAC secret |
| `SVEA_ENVIRONMENT` | ✅ | `test` or `production` |
| `SVEA_WEBHOOK_SECRET` | ✅ | Inbound webhook signature secret |
| `SVEA_SUBSCRIPTION_CALLBACK_URL` | — | Default callback URL for subscriptions |
| `SVEA_MAX_RETRIES` | — | Retry attempts on 429/500/503 (default: `0`) |
| `SVEA_TIMEOUT` | — | HTTP timeout in seconds (default: `10`) |
| `SVEA_CHECKOUT_URL` | — | Override Checkout API base URL |
| `SVEA_ADMIN_URL` | — | Override Admin API base URL |
| `SVEA_SUBSCRIPTIONS_URL` | — | Override Subscriptions API base URL |

### `config/svea.php`

```php
return [
    'merchant_id'    => env('SVEA_MERCHANT_ID'),
    'shared_secret'  => env('SVEA_SHARED_SECRET'),
    'environment'    => env('SVEA_ENVIRONMENT', 'test'), // 'test' | 'production'
    'webhook_secret' => env('SVEA_WEBHOOK_SECRET'),
    'subscription_callback_url' => env('SVEA_SUBSCRIPTION_CALLBACK_URL'),
    'max_retries'    => env('SVEA_MAX_RETRIES', 0),
    'timeout'        => env('SVEA_TIMEOUT', 10),

    // Override base URLs per API surface — useful for pointing at a local mock server.
    // When null the built-in environment defaults are used.
    'base_urls' => [
        'checkout'      => env('SVEA_CHECKOUT_URL'),      // default: https://checkoutapistage.svea.com (test)
        'admin'         => env('SVEA_ADMIN_URL'),         // default: https://paymentadminapistage.svea.com (test)
        'subscriptions' => env('SVEA_SUBSCRIPTIONS_URL'), // default: https://paymentadminapistage.svea.com (test)
    ],
];
```

---

## Laravel Integration

### Auto-discovery

`SveaServiceProvider` is auto-discovered via the `extra.laravel` key in `composer.json`. To register manually:

```php
// bootstrap/providers.php
Svea\Laravel\SveaServiceProvider::class,
```

### Facade

```php
use Svea\Laravel\Svea;

Svea::checkout()->create(...);
Svea::admin()->order('12345678')->deliver();
Svea::subscriptions()->list();
```

### Laravel webhook event

Dispatch `SveaWebhookReceived` from your webhook controller to decouple event handling:

```php
use Svea\Laravel\Events\SveaWebhookReceived;
use Svea\Laravel\WebhookService;

class SveaWebhookController
{
    public function __invoke(Request $request, WebhookService $webhookService): Response
    {
        $event = $webhookService->fromRequest($request); // throws SignatureVerificationException on mismatch
        SveaWebhookReceived::dispatch($event);

        return response()->noContent();
    }
}
```

### HTTP tracing with Wiretap (optional)

[nordkit/wiretap](https://github.com/nordkit/wiretap) is a framework-agnostic, configurable HTTP tracing package that captures inbound and outbound HTTP requests and responses — recording headers, payloads, status codes, and timing — with built-in filtering and redaction controls. It works great with Laravel. Integrating it with `SveaClient` gives you full visibility into every API call made to Svea, and with inbound tracing enabled (`WIRETAP_INBOUND=true`) it also keeps a full log of all incoming webhook pushes and payment callbacks — useful for debugging and auditing the complete order lifecycle.

Override the `SveaClient` singleton to inject a `HandlerStack` with Wiretap or any Guzzle middleware:

```php
use GuzzleHttp\HandlerStack;
use Nordkit\Wiretap\Guzzle\WiretapMiddleware;
use Nordkit\Wiretap\Wiretap;
use Svea\SveaClient;

// In your AppServiceProvider::register():
$this->app->singleton(SveaClient::class, function ($app): SveaClient {
    $stack = HandlerStack::create();
    $stack->push(WiretapMiddleware::make($app->make(Wiretap::class)));

    return new SveaClient(
        config: (array) $app['config']['svea'],
        handlerStack: $stack,
    );
});
```

See [Advanced Usage — Custom Middleware](#custom-middleware) for other middleware examples.

### Artisan Commands

Six commands cover the full subscription lifecycle. All API calls go out from the machine running the command — run them **locally** if the server cannot reach Svea (e.g. Laravel Cloud with no outbound firewall exception).

#### `svea:subscription:add`

```bash
# Register for all event types using the default callback URL from config
php artisan svea:subscription:add

# Override the callback URL
php artisan svea:subscription:add --url=https://staging.myapp.com/v2/webhooks/svea/subscription

# Subscribe to specific events only
php artisan svea:subscription:add --events=CheckoutOrder.Created,CheckoutOrder.Delivered

# Skip the automatic verification Ping
php artisan svea:subscription:add --no-verify
```

Default callback URL: `app.url` + `/v2/webhooks/svea/subscription`. Default events: all except `Ping`.

#### `svea:subscription:list`

```bash
php artisan svea:subscription:list
```

Outputs a table of ID, Callback URL, Verified status, and subscribed event types.

#### `svea:subscription:get`

```bash
php artisan svea:subscription:get {id}
```

#### `svea:subscription:verify`

```bash
php artisan svea:subscription:verify {id}
```

Required after `--no-verify` or after changing a URL via `svea:subscription:update`.

#### `svea:subscription:update`

```bash
# Change the URL (requires re-verification)
php artisan svea:subscription:update {id} --url=https://new.myapp.com/v2/webhooks/svea/subscription

# Change events
php artisan svea:subscription:update {id} --events=CheckoutOrder.Created,CheckoutOrder.Closed

# Change URL and re-verify in one step
php artisan svea:subscription:update {id} --url=https://new.myapp.com/... --verify
```

#### `svea:subscription:remove`

```bash
php artisan svea:subscription:remove {id}

# Skip the confirmation prompt
php artisan svea:subscription:remove {id} --force
```

---

## API Reference

### Checkout

#### Create

All numeric values follow Svea's **minor-unit convention**: `quantity` (`100` = 1 unit), `unitPrice` (minor currency, e.g. `29900` = 299.00 SEK), `vatPercent` (`2500` = 25%), `discountPercent` (`1000` = 10%).

**Named constructor style** — best when all data is available upfront:

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

$order->id();                          // '12345678'
$order->snippet();                     // '<div>...</div>' — embed in checkout page
$order->status();                      // 'Created' | 'Final' | 'Cancelled'
$order->successful();                  // bool
$order->getLastResponse()->statusCode; // 201
```

**Fluent callback style** — better for loops, conditional rows, and composable builds:

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
            ->quantity($item->qty * 100)   // minor units: 100 = 1 unit
            ->unitPrice($item->unit_price) // incl. VAT, minor currency (öre)
            ->vatPercent($item->vat_percent) // minor units: 2500 = 25%
            ->unit('st'));
    }

    $order->when($cart->has_discount, fn ($o) => $o->addRow(
        fn (OrderRow $r) => $r->sku('DISC')->name('Discount')->unitPrice(-500)->quantity(100)->vatPercent(2500)
    ));
});
```

**Supported locales:** `sv-SE`, `da-DK`, `de-DE`, `en-US`, `fi-FI`, `nn-NO`.

**Optional fields** — chain on either style:

```php
$order->merchantData('ref:order-42')           // opaque metadata (max 6000 chars)
      ->partnerKey('uuid-from-svea')           // Svea partner key
      ->recurring()                            // create a recurring token on finalisation
      ->requireElectronicIdAuthentication()    // require BankID or equivalent
      ->metadata(['orderId' => 'ORD-001']);    // key-value pairs visible in Svea portal (45-day TTL)
```

#### Get

```php
$order = Svea::checkout()->get('12345678');

$order->id();      // '12345678'
$order->status();  // 'Created' | 'Cancelled' | 'Final'
$order->snippet(); // '<div>...</div>'
```

#### Update

`update()` accepts the same named-constructor or fluent callback as `create()` — only set the fields you want to change:

```php
$order = Svea::checkout()->update('12345678', function (CheckoutOrder $order) use ($extraItem) {
    $order->addRow(fn (OrderRow $row) => $row
        ->sku($extraItem->sku)
        ->name($extraItem->name)
        ->quantity(100)
        ->unitPrice(5000)
        ->vatPercent(2500));
});

$order->id();      // '12345678'
$order->status();  // 'Created' | 'Cancelled' | 'Final'
```

#### Cancel

```php
Svea::checkout()->cancel('12345678'); // void
```

---

### Admin

#### Deliver (capture)

`deliver()` returns a `DeliverResponse` with the new delivery ID and an async task reference URL.

```php
// Deliver all rows
$deliver = Svea::admin()->order('12345678')->deliver();

// Deliver specific rows with an idempotency key (safe for queue retries)
$deliver = Svea::admin()
    ->order('12345678')
    ->withIdempotencyKey('deliver-' . $paymentEventId)
    ->deliver(rows: [101, 102]);

$deliver->deliveryId();    // int — store to reference this delivery in credit calls
$deliver->taskReference(); // 'https://paymentadminapi.svea.com/api/v1/tasks/456' — poll for completion
$deliver->getLastResponse()->statusCode; // 202
```

#### Cancel

```php
Svea::admin()->order('12345678')->cancel();
Svea::admin()->order('12345678')->cancelAmount(50000);
Svea::admin()->order('12345678')->cancelRow(rowId: 101);
```

#### Credit (refund)

```php
// Credit specific rows on a delivery
$task = Svea::admin()
    ->order('12345678')
    ->delivery(456)
    ->credit()
    ->rows([101, 102])
    ->send();

// Credit a fixed amount
$task = Svea::admin()->order('12345678')->delivery(456)->creditAmount(9900);

// Credit a new row — fluent callback style
$task = Svea::admin()
    ->order('12345678')
    ->delivery(456)
    ->credit()
    ->newRow(fn (AdminOrderRow $row) => $row->name('Return fee')->unitPrice(5000)->quantity(100)->vatPercent(2500))
    ->send();
```

#### Get order details

```php
$adminOrder = Svea::admin()->order('12345678')->get();

$adminOrder->status();             // SveaOrderStatus enum
$adminOrder->actions();            // string[] — e.g. ['CanDeliverOrder', 'CanCancelOrder']
$adminOrder->canDeliver();         // bool
$adminOrder->canCredit();          // bool
$adminOrder->canCancel();          // bool
$adminOrder->deliveries();         // array<int, array<string, mixed>> — all deliveries on the order
$adminOrder->delivery(456);        // array<string, mixed>|null — specific delivery by ID
$adminOrder->deliveryRowIds(456);  // int[] — row IDs belonging to delivery 456 (useful before crediting)
$adminOrder->hasAction('CanDeliverOrder'); // bool — check any action string
$adminOrder->hasStatus('Open');    // bool — check status string directly
```

#### Modify order rows

```php
// Add a new row — returns the new row ID and a task reference
$result = Svea::admin()->order('12345678')->addOrderRow(function (AdminOrderRow $row) {
    $row->name('Extra item')
        ->sku('EXTRA-1')
        ->unitPrice(5000)
        ->quantity(100)
        ->vatPercent(2500)
        ->unit('st');
});

$result['order_row_id'];   // int
$result['task_reference']; // string — async task URL

// Update a single existing row by its row ID
Svea::admin()->order('12345678')->updateOrderRow(rowId: 101, callback: function (AdminOrderRow $row) {
    $row->unitPrice(4500)->name('Updated name');
});

// Replace all rows at once — each callback builds one replacement row
Svea::admin()->order('12345678')->replaceOrderRows(
    fn (AdminOrderRow $row) => $row->name('Widget')->sku('WGT-1')->unitPrice(9900)->quantity(100)->vatPercent(2500),
    fn (AdminOrderRow $row) => $row->name('Shipping')->sku('SHIP')->unitPrice(4900)->quantity(100)->vatPercent(2500),
);
```

#### Poll a task

Admin operations that mutate order state (`deliver()`, `creditAmount()`, `credit()->send()`) are **asynchronous** — Svea accepts the request immediately (HTTP 202) and processes it in the background. The response carries a task reference URL; poll it until the task completes or fails.

```php
// deliver() returns a DeliverResponse with the task URL
$deliver = Svea::admin()->order('12345678')->deliver();
$taskUrl = $deliver->taskReference(); // 'https://paymentadminapi.svea.com/api/v1/tasks/456'

// Poll until done (simple loop — use a queued job in production)
do {
    sleep(1);
    $task = Svea::admin()->task($taskUrl);
} while ($task->pending());

if ($task->failed()) {
    // handle failure
}

$task->completed(); // bool
$task->failed();    // bool
$task->pending();   // bool — true while still processing
$task->resource;    // string|null — URL to the resulting resource (e.g. the delivery) once complete
```

> **In production** run the poll loop inside a queued job with retries rather than blocking an HTTP request. Store `$deliver->taskReference()` and `$deliver->deliveryId()` immediately after calling `deliver()`.

#### Conditional chaining with `when()` / `unless()`

```php
Svea::admin()
    ->order($externalOrderId)
    ->when(! empty($partialRows), fn ($o) => $o->deliver(rows: $partialRows))
    ->unless(! empty($partialRows), fn ($o) => $o->deliver());
```

---

### Subscriptions

**Webhook subscriptions** are how Svea notifies your application when order lifecycle events occur — a payment is captured, a credit succeeds, an order is closed. You register a HTTPS endpoint once per merchant; Svea pushes a signed JSON payload to that URL whenever a subscribed event fires.

> **Subscriptions vs task polling** — These are two separate mechanisms:
>
> | | Subscriptions | Task polling |
> |---|---|---|
> | **What** | Svea pushes order lifecycle events to your URL | You poll an async Admin API operation until it completes |
> | **When** | Order created, delivered, credited, closed, etc. | After `deliver()`, `creditAmount()`, etc. return a `TaskResponse` |
> | **Direction** | Svea → your server (push) | Your server → Svea (pull) |
> | **Setup** | Register once, stays active | Per-operation, URL returned in the response |
>
> See [Poll a task](#poll-a-task) under Admin for the task-polling API.

#### Available event types

| `EventType` case | Svea event string | When it fires |
|---|---|---|
| `CheckoutOrderCreated` | `CheckoutOrder.Created` | Order created; `IsPending` = true if awaiting Svea approval |
| `CheckoutOrderUpdated` | `CheckoutOrder.Updated` | Order edited or explicit sync — use GET to refresh your state |
| `CheckoutOrderDelivered` | `CheckoutOrder.Delivered` | Order partially or fully captured |
| `CheckoutOrderCreditSucceeded` | `CheckoutOrder.CreditSucceeded` | Credit (refund) processed successfully |
| `CheckoutOrderCreditFailed` | `CheckoutOrder.CreditFailed` | An accepted credit operation subsequently failed |
| `CheckoutOrderClosed` | `CheckoutOrder.Closed` | Order cancelled or expired (`CloseReason`: `Cancelled` / `Expired`) |
| `CheckoutOrderPendingStatusReleased` | `CheckoutOrder.PendingStatusReleased` | Pending order approved by Svea |
| `StandaloneOrderPendingStatusReleased` | `StandaloneOrder.PendingStatusReleased` | Standalone pending order approved |
| `StandaloneOrderClosed` | `StandaloneOrder.Closed` | Standalone order closed |
| `Ping` | `Ping` | Sent by `verify()` to confirm your endpoint is reachable — handle it, don't subscribe to it |

> **⚠️ Checkout order finalized is not a subscription event.** When a customer completes payment, Svea POSTs a `{"type": "Finalized"}` payload to the **merchant push** (`pushUri`) configured on `MerchantSettings` per order — it is **not** delivered via the subscription webhook system. Your `pushUri` endpoint receives the push with the order ID in the URL path; you must then call `Svea::admin()->order($orderId)->get()` to read the Payment Admin status and determine next steps (e.g. `Open` → capture, `Cancelled` → cancel). Note that the checkout order status `Final` (status code `100`) only means the checkout session is closed — it does **not** indicate the order is ready for delivery.

#### Registration workflow

A new subscription must be **verified** before Svea will deliver events to it. `add()` + `verify()` in one go is the recommended path:

> **Tip:** In a Laravel application you can manage subscriptions via Artisan instead of writing code — see [Artisan Commands](#artisan-commands) under Laravel Integration for `svea:subscription:add`, `svea:subscription:verify`, and related commands.

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

// Svea sends a Ping to your endpoint — it must respond 2xx within the timeout
Svea::subscriptions()->verify($subscription->id());
```

Or via the fluent builder (calls `verify()` automatically after `register()`):

```php
$subscription = Svea::subscriptions()
    ->on(EventType::CheckoutOrderCreated, EventType::CheckoutOrderDelivered)
    ->notifyAt('https://myapp.com/webhooks/svea')
    ->register(); // registers and verifies
```

> **Re-verification:** Changing a subscription's URL via `update()` invalidates verification — call `verify()` again before events will resume.

#### Inspect a subscription

```php
$subscription->id();           // 'fbb6c74a-...'
$subscription->callbackUrl();  // 'https://myapp.com/webhooks/svea'
$subscription->events();       // EventType[]
$subscription->isVerified();   // bool — false means events are not being delivered
$subscription->createdAt();    // \DateTimeImmutable|null
```

#### Get / List / Update / Remove

```php
$subscription = Svea::subscriptions()->get('sub-id');

$subscriptions = Svea::subscriptions()->list(); // array<int, Subscription>

// Update URL or events — URL change requires re-verification
$updated = Svea::subscriptions()->update(
    'sub-id',
    'https://myapp.com/webhooks/svea-new',
    [EventType::CheckoutOrderCreated]
);
Svea::subscriptions()->verify('sub-id'); // required after URL change

Svea::subscriptions()->remove('sub-id');
```

---

### Webhooks

#### Verify and parse inbound events

```php
use Svea\Webhooks\Webhook;
use Svea\Exceptions\SignatureVerificationException;

// Framework-agnostic (pure static — works anywhere):
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

// Laravel shorthand via facade:
$event = Svea::webhook()->fromRequest($request);
```

#### Working with the event

```php
$event->type;         // EventType enum
$event->orderId;      // string
$event->deliveryId;   // string|null
$event->occurredAt;   // \DateTimeImmutable

match ($event->type()) {
    EventType::CheckoutOrderDelivered       => $this->handleDelivered($event),
    EventType::CheckoutOrderCreditSucceeded => $this->handleCredited($event),
    EventType::CheckoutOrderClosed         => $this->handleClosed($event),
    default                                => null,
};
```

---


## Testing

### `Svea::fake()`

Swap the real client for a fake in Pest/PHPUnit tests. Mirrors Laravel's `Http::fake()` pattern.

> **Tip:** All fluent builders (`CheckoutOrder`, `OrderRow`, `MerchantSettings`, `AdminOrderRow`) expose a `make()` named constructor that returns a blank instance — identical to `new ClassName()`. Inside `Svea::fake()` callbacks the builders are passed pre-constructed, so you never need to call `make()` directly in test code.

```php
use Svea\Admin\AdminOrderResponse;
use Svea\Admin\TaskResponse;
use Svea\Checkout\CheckoutResponse;

Svea::fake([
    'checkout.create' => CheckoutResponse::make(['OrderId' => '99999999', 'Gui' => ['Snippet' => '<div>...</div>']]),
    'admin.get'       => AdminOrderResponse::make(['OrderStatus' => 'Open', 'Actions' => ['CanDeliverOrder']]),
    'admin.deliver'   => TaskResponse::pending('https://paymentadminapi.svea.com/api/v1/tasks/123'),
    'admin.task'      => TaskResponse::completed(),
]);

// Run code under test
$result = (new CaptureOrder($paymentManager))->execute($payment);

// Assert what was called
Svea::assertDelivered('99999999');
Svea::assertDelivered('99999999', rows: [101, 102]);
Svea::assertCredited('99999999');
Svea::assertCancelledOrder('99999999');
Svea::assertCheckoutCreated();
Svea::assertTaskPolled('https://paymentadminapi.svea.com/api/v1/tasks/123');
Svea::assertSubscriptionRegistered('https://myapp.com/webhooks/svea');
Svea::assertSubscriptionAdded('https://myapp.com/webhooks/svea');
Svea::assertSubscriptionFetched('sub-guid');
Svea::assertSubscriptionsListed();
Svea::assertSubscriptionUpdated('sub-guid');
Svea::assertSubscriptionRemoved('sub-guid');
Svea::assertSubscriptionVerified('sub-guid');
Svea::assertNothingSent();
```

### `preventStrayRequests()`

```php
Svea::fake()->preventStrayRequests(); // throws on any non-faked call
```

### Generic call assertions

```php
$assertions = Svea::fake();
// run code
$assertions->assertCalled('admin.deliver');
$assertions->assertCalledTimes('admin.deliver', 1);
$assertions->assertNotCalled('checkout.create');
```

### Low-level: Guzzle `MockHandler`

For integration-style tests that exercise the full HTTP layer without hitting the real API:

```php
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Svea\SveaClient;

$mock = new MockHandler([
    new Response(201, [], json_encode(['OrderId' => 12345678, 'Gui' => ['Snippet' => '<div/>']])),
]);

$svea = new SveaClient(
    config: ['merchant_id' => 'test', 'shared_secret' => 'secret', 'environment' => 'test'],
    handlerStack: HandlerStack::create($mock),
);

$order = $svea->checkout->create(...);
expect($order->id())->toBe('12345678');
```

---

## Advanced Usage

### Retries with exponential backoff

```php
$svea = new SveaClient([
    'merchant_id'   => '...',
    'shared_secret' => '...',
    'environment'   => 'production',
    'max_retries'   => 2,  // default: 0 (opt-in)
    'timeout'       => 10,
]);
```

`RetryMiddleware` retries on `ConnectionException` and HTTP 429/500/503 with exponential backoff and random jitter. With `max_retries=2`: attempt 1 → ~2 s, attempt 2 → ~4 s.

### Per-request idempotency keys

Prevent double-captures on queue retries:

```php
$deliver = Svea::admin()
    ->order('12345678')
    ->withIdempotencyKey('capture-' . $paymentEvent->id)
    ->deliver(rows: [101, 102]);

$deliver->deliveryId();    // int
$deliver->taskReference(); // string|null — poll via Svea::admin()->task(...)
```

### Custom middleware

```php
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;

$stack = HandlerStack::create();
$stack->push(Middleware::retry(/* ... */));

$svea = new SveaClient(
    config: config('svea'),
    handlerStack: $stack,
);
```

### Override base URLs

Useful for pointing at a local mock server during development:

```env
SVEA_CHECKOUT_URL=http://localhost:8080
SVEA_ADMIN_URL=http://localhost:8080
SVEA_SUBSCRIPTIONS_URL=http://localhost:8080
```

---

## Error Handling

```
SveaException                            (base)
├── SveaApiException                     (any non-2xx — carries ->statusCode, ->sveaCode, ->sveaMessage, ->getLastResponse())
│   ├── SveaAuthenticationException      (401 — bad credentials)
│   ├── SveaInvalidRequestException      (400 — validation failed, carries ->errors[])
│   ├── SveaNotFoundException            (404 — order not found)
│   └── SveaRateLimitException           (429 — triggers auto-retry if max_retries > 0)
├── SveaConnectionException              (network failure / timeout — triggers auto-retry)
└── SignatureVerificationException        (inbound webhook HMAC mismatch)
```

```php
use Svea\Exceptions\SveaApiException;
use Svea\Exceptions\SveaNotFoundException;

try {
    $order = Svea::admin()->order('12345678')->get();
} catch (SveaNotFoundException $e) {
    // 404 — order not found
} catch (SveaApiException $e) {
    $e->statusCode;        // int
    $e->sveaCode;          // string|null
    $e->sveaMessage;       // string|null
    $e->getLastResponse(); // SveaResponse
}
```

---

## Response Objects

Every API call returns a `SveaResource` — a typed, read-only, array-accessible object:

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

> **Read-only:** Attempting `$order['key'] = value` or `unset($order['key'])` throws `\BadMethodCallException`.

---

## Package Structure

```
src/
├── SveaClient.php          # Main entry point — lazy service properties
├── SveaResource.php        # Base response class: ArrayAccess, magic __get, getLastResponse()
├── Checkout/               # CheckoutService, CheckoutOrder, OrderRow, CheckoutResponse, …
├── Admin/                  # AdminService, AdminOrderRequest, AdminOrderResponse, CreditRequest, …
├── Subscriptions/          # SubscriptionService, SubscriptionBuilder, Subscription, EventType
├── Webhooks/               # Webhook, WebhookService (PSR-7), WebhookEvent, SignatureVerifier
├── Transport/              # SveaConnector (HMAC auth), SveaResponse, RetryMiddleware
├── Contracts/              # CheckoutServiceInterface, AdminServiceInterface, SubscriptionServiceInterface
├── Testing/                # FakeSveaClient, FakeCheckoutService, FakeAdminService, SveaFakeAssertions, …
├── Exceptions/             # SveaException hierarchy (8 classes)
├── Support/                # Conditionable trait (when/unless)
└── Laravel/                # SveaServiceProvider, Svea facade, WebhookService bridge, Events/
```

For architecture decisions, internal implementation notes, and contributor setup see [CONTRIBUTING.md](CONTRIBUTING.md).

---

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for architecture decisions, internal implementation notes, and development setup.

**License:** MIT
