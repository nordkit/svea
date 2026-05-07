<?php

declare(strict_types=1);

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Svea\Subscriptions\EventType;
use Svea\Subscriptions\Subscription;
use Svea\Subscriptions\SubscriptionBuilder;
use Svea\Transport\SveaConnector;

// ---------------------------------------------------------------------------
// Helper — creates a SveaConnector wired to a MockHandler and captures
// request history so tests can assert on the outbound HTTP payload.
// ---------------------------------------------------------------------------

/**
 * @param  Response[]  $responses
 * @param  array<int, mixed>  $history
 */
function subscriptionConnector(array $responses, array &$history = []): SveaConnector
{
    $mock = new MockHandler($responses);
    $stack = HandlerStack::create($mock);
    $stack->push(Middleware::history($history));

    return new SveaConnector(
        config: ['merchant_id' => 'test-merchant', 'shared_secret' => 'test-secret'],
        baseUrl: 'https://subscriptionapistage.svea.com',
        handlerStack: $stack,
    );
}

// ---------------------------------------------------------------------------
// SubscriptionBuilder::register()
// ---------------------------------------------------------------------------

test('register sends correct POST payload with CallbackUri and Events', function () {
    $history = [];
    $connector = subscriptionConnector(
        responses: [new Response(200, [], json_encode(['SubscriptionId' => 'new-sub-guid']))],
        history: $history,
    );

    $subscription = (new SubscriptionBuilder($connector))
        ->on(EventType::CheckoutOrderCreated, EventType::CheckoutOrderDelivered)
        ->notifyAt('https://myapp.com/webhooks/svea')
        ->register();

    expect($subscription)->toBeInstanceOf(Subscription::class)
        ->and($subscription->id())->toBe('new-sub-guid')
        ->and($history)->toHaveCount(1)
        ->and($history[0]['request']->getMethod())->toBe('POST')
        ->and((string) $history[0]['request']->getUri()->getPath())->toBe('/api/v2/callbacks/subscriptions')
        ->and(json_decode((string) $history[0]['request']->getBody(), true))->toBe([
            'CallbackUri' => 'https://myapp.com/webhooks/svea',
            'Events' => ['CheckoutOrder.Created', 'CheckoutOrder.Delivered'],
        ]);
});

test('register returns Subscription with correct accessors from API response', function () {
    $connector = subscriptionConnector([
        new Response(200, [], json_encode([
            'SubscriptionId' => 'abc-123',
            'CallbackUri' => 'https://myapp.com/webhooks/svea',
            'Events' => ['CheckoutOrder.Created'],
            'Verified' => false,
        ])),
    ]);

    $subscription = (new SubscriptionBuilder($connector))
        ->on(EventType::CheckoutOrderCreated)
        ->notifyAt('https://myapp.com/webhooks/svea')
        ->register();

    expect($subscription->id())->toBe('abc-123')
        ->and($subscription->callbackUrl())->toBe('https://myapp.com/webhooks/svea')
        ->and($subscription->events())->toBe([EventType::CheckoutOrderCreated])
        ->and($subscription->isVerified())->toBeFalse();
});

test('register attaches the raw HTTP response via getLastResponse', function () {
    $connector = subscriptionConnector([
        new Response(200, [], json_encode(['SubscriptionId' => 'abc-123'])),
    ]);

    $subscription = (new SubscriptionBuilder($connector))
        ->on(EventType::CheckoutOrderCreated)
        ->notifyAt('https://myapp.com/webhooks/svea')
        ->register();

    expect($subscription->getLastResponse())->not->toBeNull()
        ->and($subscription->getLastResponse()->statusCode)->toBe(200);
});

test('on() is chainable and replaces previously set event types', function () {
    $history = [];
    $connector = subscriptionConnector(
        responses: [new Response(200, [], json_encode(['SubscriptionId' => 'x']))],
        history: $history,
    );

    (new SubscriptionBuilder($connector))
        ->on(EventType::CheckoutOrderCreated)
        ->on(EventType::CheckoutOrderClosed)    // replaces previous
        ->notifyAt('https://myapp.com/webhooks/svea')
        ->register();

    $body = json_decode((string) $history[0]['request']->getBody(), true);

    expect($body['Events'])->toBe(['CheckoutOrder.Closed']);
});
