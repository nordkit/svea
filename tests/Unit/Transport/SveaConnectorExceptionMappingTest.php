<?php

declare(strict_types=1);

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Svea\Exceptions\SveaApiException;
use Svea\Exceptions\SveaAuthenticationException;
use Svea\Exceptions\SveaNotFoundException;
use Svea\Exceptions\SveaRateLimitException;
use Svea\Transport\SveaConnector;

// ---------------------------------------------------------------------------
// Helper
// ---------------------------------------------------------------------------

/**
 * Create a SveaConnector backed by a single queued mock response.
 *
 * @param  Response  $response  The single response to return.
 */
function connectorWithResponse(Response $response): SveaConnector
{
    $stack = HandlerStack::create(new MockHandler([$response]));

    return new SveaConnector(
        config: ['merchant_id' => 'test', 'shared_secret' => 'secret'],
        baseUrl: 'https://checkoutapistage.svea.com',
        handlerStack: $stack,
    );
}

// ---------------------------------------------------------------------------
// HTTP status → exception class mapping
// ---------------------------------------------------------------------------

dataset('http_error_responses', [
    '401 maps to SveaAuthenticationException' => [
        401,
        SveaAuthenticationException::class,
    ],
    '404 maps to SveaNotFoundException' => [
        404,
        SveaNotFoundException::class,
    ],
    '429 maps to SveaRateLimitException' => [
        429,
        SveaRateLimitException::class,
    ],
    '400 maps to SveaApiException' => [
        400,
        SveaApiException::class,
    ],
    '500 maps to SveaApiException' => [
        500,
        SveaApiException::class,
    ],
    '503 maps to SveaApiException' => [
        503,
        SveaApiException::class,
    ],
]);

test('connector maps HTTP error status to typed exception', function (int $status, string $exceptionClass): void {
    $connector = connectorWithResponse(new Response($status, [], json_encode(['Message' => 'Error'])));

    expect(fn () => $connector->get('api/v2/orders/1'))
        ->toThrow($exceptionClass);
})->with('http_error_responses');

// ---------------------------------------------------------------------------
// SveaApiException carries status code and last response
// ---------------------------------------------------------------------------

test('SveaApiException exposes statusCode and last response body', function (): void {
    $connector = connectorWithResponse(
        new Response(400, [], json_encode(['Message' => 'Bad request', 'ErrorCode' => 'INVALID']))
    );

    try {
        $connector->get('api/v2/orders/1');
        expect(false)->toBeTrue('Expected SveaApiException to be thrown');
    } catch (SveaApiException $e) {
        expect($e->statusCode)->toBe(400)
            ->and($e->getLastResponse())->not->toBeNull()
            ->and($e->getLastResponse()->statusCode)->toBe(400);
    }
});

// ---------------------------------------------------------------------------
// 2xx response does NOT throw
// ---------------------------------------------------------------------------

dataset('successful_statuses', [200, 201, 202, 204]);

test('connector does not throw on successful status', function (int $status): void {
    $connector = connectorWithResponse(new Response($status, [], '{}'));

    expect(fn () => $connector->get('api/v2/orders/1'))
        ->not->toThrow(SveaApiException::class);
})->with('successful_statuses');
