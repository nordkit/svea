<?php

declare(strict_types=1);

use PHPUnit\Framework\AssertionFailedError;
use Svea\Admin\TaskResponse;
use Svea\Checkout\CheckoutResponse;
use Svea\Subscriptions\EventType;
use Svea\Testing\FakeSveaClient;
use Svea\Testing\SveaFakeAssertions;
use Svea\Webhooks\WebhookService;

// ---------------------------------------------------------------------------
// FakeSveaClient — basics
// ---------------------------------------------------------------------------

test('fake returns assertions instance', function (): void {
    $fake = new FakeSveaClient;
    expect($fake->assertions())->toBeInstanceOf(SveaFakeAssertions::class);
});

test('checkout create is recorded and asserted', function (): void {
    $fake = new FakeSveaClient;
    $fake->checkout()->create(fn ($o) => $o->currency('SEK'));
    $fake->assertions()->assertCheckoutCreated();
});

test('admin deliver is recorded and asserted', function (): void {
    $fake = new FakeSveaClient;
    $fake->admin()->order('12345678')->deliver();
    $fake->assertions()->assertDelivered('12345678');
});

test('admin cancel is recorded and asserted', function (): void {
    $fake = new FakeSveaClient;
    $fake->admin()->order('99999999')->cancel();
    $fake->assertions()->assertCancelledOrder('99999999');
});

test('subscription register is recorded and asserted', function (): void {
    $fake = new FakeSveaClient;
    $fake->subscriptions()->on(EventType::CheckoutOrderCreated)->notifyAt('https://myapp.com/webhooks/svea')->register();
    $fake->assertions()->assertSubscriptionRegistered('https://myapp.com/webhooks/svea');
});

test('assertNothingSent passes when no calls made', function (): void {
    (new FakeSveaClient)->assertions()->assertNothingSent();
});

test('seeded fake response is returned', function (): void {
    $seeded = CheckoutResponse::make(['OrderId' => 'seeded-id', 'Status' => 'Created']);
    $fake = new FakeSveaClient(['checkout.create' => $seeded]);
    $result = $fake->checkout()->create(fn ($o) => $o->currency('SEK'));
    expect($result->id())->toBe('seeded-id');
});

test('preventStrayRequests throws when no fake seeded', function (): void {
    $fake = new FakeSveaClient;
    $fake->assertions()->preventStrayRequests();
    expect(fn () => $fake->checkout()->create(fn ($o) => $o->currency('SEK')))
        ->toThrow(RuntimeException::class);
});

test('webhook returns a WebhookService instance', function (): void {
    $fake = new FakeSveaClient;
    expect($fake->webhook())->toBeInstanceOf(WebhookService::class);
});

// ---------------------------------------------------------------------------
// SveaFakeAssertions — generic methods
// ---------------------------------------------------------------------------

test('assertCalled passes after the method has been called', function (): void {
    $fake = new FakeSveaClient;
    $fake->admin()->order('12345678')->deliver();
    $fake->assertions()->assertCalled('admin.deliver');
});

test('assertNotCalled passes when the method was never called', function (): void {
    $fake = new FakeSveaClient;
    $fake->assertions()->assertNotCalled('admin.deliver');
});

test('assertCalledTimes passes with exact call count', function (): void {
    $fake = new FakeSveaClient;
    $fake->checkout()->create(fn ($o) => $o->currency('SEK'));
    $fake->checkout()->create(fn ($o) => $o->currency('SEK'));
    $fake->assertions()->assertCalledTimes('checkout.create', 2);
});

test('assertCalledTimes fails with incorrect count', function (): void {
    $fake = new FakeSveaClient;
    $fake->admin()->order('12345678')->deliver();
    expect(fn () => $fake->assertions()->assertCalledTimes('admin.deliver', 3))
        ->toThrow(AssertionFailedError::class);
});

// ---------------------------------------------------------------------------
// SveaFakeAssertions — subscription specifics
// ---------------------------------------------------------------------------

test('assertSubscriptionAdded records direct add call', function (): void {
    $fake = new FakeSveaClient;
    $fake->subscriptions()->add('https://myapp.com/webhooks', [EventType::CheckoutOrderCreated]);
    $fake->assertions()->assertSubscriptionAdded('https://myapp.com/webhooks');
});

test('assertSubscriptionFetched records get call', function (): void {
    $fake = new FakeSveaClient;
    $fake->subscriptions()->get('sub-guid-123');
    $fake->assertions()->assertSubscriptionFetched('sub-guid-123');
});

test('assertSubscriptionsListed records list call', function (): void {
    $fake = new FakeSveaClient;
    $fake->subscriptions()->list();
    $fake->assertions()->assertSubscriptionsListed();
});

test('assertSubscriptionUpdated records update call', function (): void {
    $fake = new FakeSveaClient;
    $fake->subscriptions()->update('sub-guid-123', 'https://new.example.com/hook', [EventType::CheckoutOrderCreated]);
    $fake->assertions()->assertSubscriptionUpdated('sub-guid-123');
});

test('assertSubscriptionRemoved records remove call', function (): void {
    $fake = new FakeSveaClient;
    $fake->subscriptions()->remove('sub-guid-123');
    $fake->assertions()->assertSubscriptionRemoved('sub-guid-123');
});

test('assertSubscriptionVerified records verify call', function (): void {
    $fake = new FakeSveaClient;
    $fake->subscriptions()->verify('sub-guid-123');
    $fake->assertions()->assertSubscriptionVerified('sub-guid-123');
});

// ---------------------------------------------------------------------------
// SveaFakeAssertions — admin credit path
// ---------------------------------------------------------------------------

test('assertCredited records credit send call', function (): void {
    $fake = new FakeSveaClient([
        'admin.credit' => TaskResponse::pending('https://paymentadminapi.svea.com/api/v1/tasks/fake'),
    ]);
    $fake->admin()->order('12345678')->delivery(456)->credit()->rows([101, 102])->send();
    $fake->assertions()->assertCredited('12345678');
});

test('assertTaskPolled records admin task call', function (): void {
    $taskUrl = 'https://paymentadminapi.svea.com/api/v1/tasks/fake-456';
    $fake = new FakeSveaClient;
    $fake->admin()->task($taskUrl);
    $fake->assertions()->assertTaskPolled($taskUrl);
});
