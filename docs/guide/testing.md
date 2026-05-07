# Testing & Fakes

`Svea::fake()` mirrors Laravel's `Http::fake()` pattern — swap the real client for a fake, seed responses, and assert what was called.

## Basic usage

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

// run code under test...

Svea::assertDelivered('99999999');
Svea::assertDelivered('99999999', rows: [101, 102]);
Svea::assertCredited('99999999');
Svea::assertCheckoutCreated();
Svea::assertTaskPolled('https://paymentadminapi.svea.com/api/v1/tasks/123');
Svea::assertNothingSent();
```

## All assertion methods

```
Svea::assertCheckoutCreated()
Svea::assertDelivered($orderId, ?rows)
Svea::assertCredited($orderId)
Svea::assertCancelledOrder($orderId)
Svea::assertTaskPolled($taskUrl)
Svea::assertSubscriptionRegistered($url)
Svea::assertSubscriptionAdded($url)
Svea::assertSubscriptionFetched($id)
Svea::assertSubscriptionsListed()
Svea::assertSubscriptionUpdated($id)
Svea::assertSubscriptionRemoved($id)
Svea::assertSubscriptionVerified($id)
Svea::assertNothingSent()
```

## `preventStrayRequests()`

```php
Svea::fake()->preventStrayRequests(); // throws on any non-faked call
```

## Generic call assertions

```php
$assertions = Svea::fake();

$assertions->assertCalled('admin.deliver');
$assertions->assertCalledTimes('admin.deliver', 1);
$assertions->assertNotCalled('checkout.create');
```

## Low-level: Guzzle `MockHandler`

For integration-style tests that exercise the full HTTP layer:

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
```

