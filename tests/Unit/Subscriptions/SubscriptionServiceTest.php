<?php

declare(strict_types=1);

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Svea\Exceptions\SveaAuthenticationException;
use Svea\Subscriptions\EventType;
use Svea\Subscriptions\Subscription;
use Svea\Subscriptions\SubscriptionService;
use Svea\Transport\SveaConnector;

// ---------------------------------------------------------------------------
// Helper — same pattern as SubscriptionBuilderTest
// ---------------------------------------------------------------------------

/**
 * @param  Response[]  $responses
 * @param  array<int, mixed>  $history
 */
function subscriptionServiceConnector(array $responses, array &$history = []): SveaConnector
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
// list()  — GET /api/v2/callbacks/subscriptions/
// ---------------------------------------------------------------------------

test('list returns an array of Subscription objects', function () {
    $connector = subscriptionServiceConnector([
        new Response(200, [], json_encode([
            ['SubscriptionId' => 'sub-1', 'CallbackUri' => 'https://a.com', 'Events' => ['CheckoutOrder.Created'], 'Verified' => true],
            ['SubscriptionId' => 'sub-2', 'CallbackUri' => 'https://b.com', 'Events' => ['CheckoutOrder.Closed'], 'Verified' => false],
        ])),
    ]);

    $subscriptions = (new SubscriptionService($connector))->list();

    expect($subscriptions)->toHaveCount(2)
        ->and($subscriptions[0])->toBeInstanceOf(Subscription::class)
        ->and($subscriptions[0]->id())->toBe('sub-1')
        ->and($subscriptions[1]->id())->toBe('sub-2');
});

test('list sends GET to correct endpoint', function () {
    $history = [];
    $connector = subscriptionServiceConnector(
        responses: [new Response(200, [], '[]')],
        history: $history,
    );

    (new SubscriptionService($connector))->list();

    expect($history[0]['request']->getMethod())->toBe('GET')
        ->and((string) $history[0]['request']->getUri()->getPath())->toBe('/api/v2/callbacks/subscriptions/');
});

test('list returns empty array when response is empty', function () {
    $connector = subscriptionServiceConnector([new Response(200, [], '[]')]);

    expect((new SubscriptionService($connector))->list())->toBe([]);
});

// ---------------------------------------------------------------------------
// get()  — GET /api/v2/callbacks/subscriptions/{subscriptionId}
// ---------------------------------------------------------------------------

test('get returns a Subscription for the given ID', function () {
    $history = [];
    $connector = subscriptionServiceConnector(
        responses: [
            new Response(200, [], json_encode([
                'SubscriptionId' => 'fbb6c74a-cc06-4ab7-100e-08daa861c517',
                'CallbackUri' => 'https://myapp.com/webhooks/svea',
                'Events' => ['CheckoutOrder.Created'],
                'Verified' => true,
            ])),
        ],
        history: $history,
    );

    $subscription = (new SubscriptionService($connector))->get('fbb6c74a-cc06-4ab7-100e-08daa861c517');

    expect($subscription)->toBeInstanceOf(Subscription::class)
        ->and($subscription->id())->toBe('fbb6c74a-cc06-4ab7-100e-08daa861c517')
        ->and($subscription->isVerified())->toBeTrue()
        ->and($history[0]['request']->getMethod())->toBe('GET')
        ->and((string) $history[0]['request']->getUri()->getPath())
        ->toBe('/api/v2/callbacks/subscriptions/fbb6c74a-cc06-4ab7-100e-08daa861c517');
});

// ---------------------------------------------------------------------------
// add()  — POST /api/v2/callbacks/subscriptions
// ---------------------------------------------------------------------------

test('add sends correct POST payload and returns Subscription', function () {
    $history = [];
    $connector = subscriptionServiceConnector(
        responses: [new Response(200, [], json_encode(['SubscriptionId' => 'new-guid']))],
        history: $history,
    );

    $subscription = (new SubscriptionService($connector))->add(
        callbackUrl: 'https://myapp.com/webhooks/svea',
        eventTypes: [EventType::CheckoutOrderCreated, EventType::CheckoutOrderDelivered],
    );

    expect($subscription)->toBeInstanceOf(Subscription::class)
        ->and($subscription->id())->toBe('new-guid')
        ->and($history[0]['request']->getMethod())->toBe('POST')
        ->and((string) $history[0]['request']->getUri()->getPath())->toBe('/api/v2/callbacks/subscriptions')
        ->and(json_decode((string) $history[0]['request']->getBody(), true))->toBe([
            'CallbackUri' => 'https://myapp.com/webhooks/svea',
            'Events' => ['CheckoutOrder.Created', 'CheckoutOrder.Delivered'],
        ]);
});

// ---------------------------------------------------------------------------
// update()  — PUT /api/v2/callbacks/subscriptions/{subscriptionId}
// ---------------------------------------------------------------------------

test('update sends correct PUT payload and returns Subscription', function () {
    $history = [];
    $connector = subscriptionServiceConnector(
        responses: [new Response(200, [], json_encode([
            'SubscriptionId' => 'sub-to-update',
            'CallbackUri' => 'https://new.myapp.com/webhooks',
            'Events' => ['CheckoutOrder.CreditSucceeded'],
            'Verified' => false,
        ]))],
        history: $history,
    );

    $subscription = (new SubscriptionService($connector))->update(
        subscriptionId: 'sub-to-update',
        callbackUrl: 'https://new.myapp.com/webhooks',
        eventTypes: [EventType::CheckoutOrderCreditSucceeded],
    );

    expect($subscription)->toBeInstanceOf(Subscription::class)
        ->and($subscription->callbackUrl())->toBe('https://new.myapp.com/webhooks')
        ->and($subscription->events())->toBe([EventType::CheckoutOrderCreditSucceeded])
        ->and($history[0]['request']->getMethod())->toBe('PUT')
        ->and((string) $history[0]['request']->getUri()->getPath())
        ->toBe('/api/v2/callbacks/subscriptions/sub-to-update')
        ->and(json_decode((string) $history[0]['request']->getBody(), true))->toBe([
            'CallbackUri' => 'https://new.myapp.com/webhooks',
            'Events' => ['CheckoutOrder.CreditSucceeded'],
        ]);
});

// ---------------------------------------------------------------------------
// remove()  — DELETE /api/v2/callbacks/subscriptions/{subscriptionId}
// ---------------------------------------------------------------------------

test('remove sends DELETE to correct endpoint', function () {
    $history = [];
    $connector = subscriptionServiceConnector(
        responses: [new Response(200, [], '{}')],
        history: $history,
    );

    (new SubscriptionService($connector))->remove('sub-to-delete');

    expect($history[0]['request']->getMethod())->toBe('DELETE')
        ->and((string) $history[0]['request']->getUri()->getPath())
        ->toBe('/api/v2/callbacks/subscriptions/sub-to-delete');
});

// ---------------------------------------------------------------------------
// verify()  — POST /api/v2/callbacks/subscriptions/{subscriptionId}/verify
// ---------------------------------------------------------------------------

test('verify sends POST to correct verify endpoint', function () {
    $history = [];
    $connector = subscriptionServiceConnector(
        responses: [new Response(200, [], '{}')],
        history: $history,
    );

    (new SubscriptionService($connector))->verify('sub-to-verify');

    expect($history[0]['request']->getMethod())->toBe('POST')
        ->and((string) $history[0]['request']->getUri()->getPath())
        ->toBe('/api/v2/callbacks/subscriptions/sub-to-verify/verify');
});

// ---------------------------------------------------------------------------
// on()  — fluent builder delegation
// ---------------------------------------------------------------------------

test('on() returns a SubscriptionBuilder that registers correctly', function () {
    $history = [];
    $connector = subscriptionServiceConnector(
        responses: [new Response(200, [], json_encode(['SubscriptionId' => 'fluent-sub']))],
        history: $history,
    );

    $subscription = (new SubscriptionService($connector))
        ->on(EventType::CheckoutOrderCreated, EventType::CheckoutOrderClosed)
        ->notifyAt('https://myapp.com/webhooks/svea')
        ->register();

    expect($subscription->id())->toBe('fluent-sub')
        ->and($history[0]['request']->getMethod())->toBe('POST')
        ->and(json_decode((string) $history[0]['request']->getBody(), true)['Events'])
        ->toBe(['CheckoutOrder.Created', 'CheckoutOrder.Closed']);
});

// ---------------------------------------------------------------------------
// Error handling
// ---------------------------------------------------------------------------

test('list throws SveaAuthenticationException on 401', function () {
    $connector = subscriptionServiceConnector([
        new Response(401, [], json_encode(['Message' => 'Unauthorized'])),
    ]);

    expect(fn () => (new SubscriptionService($connector))->list())
        ->toThrow(SveaAuthenticationException::class);
});

test('verify throws SveaAuthenticationException on 401', function () {
    $connector = subscriptionServiceConnector([
        new Response(401, [], json_encode(['Message' => 'Unauthorized'])),
    ]);

    expect(fn () => (new SubscriptionService($connector))->verify('bad-sub'))
        ->toThrow(SveaAuthenticationException::class);
});
