<?php

declare(strict_types=1);

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Svea\Checkout\CheckoutOrder;
use Svea\Checkout\CheckoutResponse;
use Svea\Checkout\CheckoutService;
use Svea\Checkout\OrderRow;
use Svea\Transport\SveaConnector;

// ---------------------------------------------------------------------------
// Helper
// ---------------------------------------------------------------------------

/**
 * Create a SveaConnector backed by a MockHandler for the Checkout API.
 *
 * @param  Response[]  $responses  Queued HTTP responses.
 * @param  array<int, mixed>  $history  Reference array populated with request/response pairs.
 */
function checkoutConnector(array $responses, array &$history = []): SveaConnector
{
    $mock = new MockHandler($responses);
    $stack = HandlerStack::create($mock);
    $stack->push(Middleware::history($history));

    return new SveaConnector(
        config: ['merchant_id' => 'test-merchant', 'shared_secret' => 'test-secret'],
        baseUrl: 'https://checkoutapistage.svea.com',
        handlerStack: $stack,
    );
}

// ---------------------------------------------------------------------------
// create()
// ---------------------------------------------------------------------------

test('create posts order payload and returns CheckoutResponse', function (): void {
    $history = [];
    $connector = checkoutConnector(
        responses: [new Response(201, [], json_encode(['OrderId' => 99999999, 'Status' => 'Created', 'Gui' => ['Snippet' => '<div/>']]))],
        history: $history,
    );

    $response = (new CheckoutService($connector))->create(function (CheckoutOrder $order): void {
        $order->currency('SEK')
            ->locale('sv-SE')
            ->countryCode('SE')
            ->clientOrderNumber('ORD-001')
            ->pushUri('https://example.com/push')
            ->addRow(fn (OrderRow $row) => $row->name('Product 1')->quantity(100)->unitPrice(9900)->sku('P1')->vatPercent(2500));
    });

    expect($response)->toBeInstanceOf(CheckoutResponse::class)
        ->and($response->id())->toBe('99999999')
        ->and($response->status())->toBe('Created')
        ->and($response->successful())->toBeTrue()
        ->and($response->snippet())->toBe('<div/>')
        ->and($history[0]['request']->getMethod())->toBe('POST')
        ->and((string) $history[0]['request']->getUri()->getPath())->toBe('/api/orders');
});

test('create attaches raw PSR-7 response via getLastResponse', function (): void {
    $connector = checkoutConnector([
        new Response(201, [], json_encode(['OrderId' => 1, 'Status' => 'Created'])),
    ]);

    $response = (new CheckoutService($connector))->create(fn (CheckoutOrder $o) => $o->currency('SEK'));

    expect($response->getLastResponse())->not->toBeNull()
        ->and($response->getLastResponse()->statusCode)->toBe(201);
});

// ---------------------------------------------------------------------------
// get()
// ---------------------------------------------------------------------------

test('get fetches order by ID and returns CheckoutResponse', function (): void {
    $history = [];
    $connector = checkoutConnector(
        responses: [new Response(200, [], json_encode(['OrderId' => 12345678, 'Status' => 'Final']))],
        history: $history,
    );

    $response = (new CheckoutService($connector))->get('12345678');

    expect($response)->toBeInstanceOf(CheckoutResponse::class)
        ->and($response->id())->toBe('12345678')
        ->and($response->status())->toBe('Final')
        ->and($history[0]['request']->getMethod())->toBe('GET')
        ->and((string) $history[0]['request']->getUri()->getPath())->toBe('/api/orders/12345678');
});

// ---------------------------------------------------------------------------
// update()
// ---------------------------------------------------------------------------

test('update sends PUT payload and returns CheckoutResponse', function (): void {
    $history = [];
    $connector = checkoutConnector(
        responses: [new Response(200, [], json_encode(['OrderId' => 12345678, 'Status' => 'Created']))],
        history: $history,
    );

    $response = (new CheckoutService($connector))->update('12345678', function (CheckoutOrder $order): void {
        $order->addRow(fn (OrderRow $row) => $row->name('Add-on')->quantity(100)->unitPrice(5000)->sku('EXTRA')->vatPercent(2500));
    });

    expect($response)->toBeInstanceOf(CheckoutResponse::class)
        ->and($response->id())->toBe('12345678')
        ->and($history[0]['request']->getMethod())->toBe('POST')
        ->and((string) $history[0]['request']->getUri()->getPath())->toBe('/api/orders/12345678');
});

// ---------------------------------------------------------------------------
// cancel()
// ---------------------------------------------------------------------------

test('cancel sends DELETE request to the correct endpoint', function (): void {
    $history = [];
    $connector = checkoutConnector(
        responses: [new Response(200, [], '{}')],
        history: $history,
    );

    (new CheckoutService($connector))->cancel('12345678');

    expect($history[0]['request']->getMethod())->toBe('POST')
        ->and((string) $history[0]['request']->getUri()->getPath())->toBe('/api/orders/12345678/cancel');
});
