<?php

declare(strict_types=1);

namespace Svea\Transport;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\HandlerStack;
use Svea\Exceptions\SveaApiException;
use Svea\Exceptions\SveaAuthenticationException;
use Svea\Exceptions\SveaConnectionException;
use Svea\Exceptions\SveaNotFoundException;
use Svea\Exceptions\SveaRateLimitException;

/**
 * Central HTTP transport layer for all Svea API surfaces.
 *
 * Each instance is scoped to a single API surface (checkout, admin, subscriptions)
 * and its corresponding base URL. Authentication is applied automatically on every
 * request using Svea's HMAC-SHA512 scheme.
 *
 * Auth header format:
 *   `Svea base64(merchantId:sha512(body + sharedSecret + timestamp))`
 *
 * Every public method returns a {@see SveaResponse} so that service classes can:
 * 1. Read `->json` for the decoded response data.
 * 2. Pass the response to `SveaResource::withLastResponse()` for raw HTTP debugging.
 *
 * Redirects (302/303) are intentionally not followed — Svea uses 303 to signal
 * async task completion, with the resource URL in the `Location` header.
 *
 * Supported config keys:
 * - `merchant_id`   (string) Svea merchant identifier
 * - `shared_secret` (string) HMAC-SHA512 signing secret
 * - `max_retries`   (int)    Number of automatic retries via {@see RetryMiddleware} (default 0)
 * - `timeout`       (int)    HTTP timeout in seconds (default 10)
 *
 * @see RetryMiddleware
 * @see SveaResponse
 */
final class SveaConnector
{
    private Client $client;

    /**
     * @param  array<string, mixed>  $config  SDK config array (merchant_id, shared_secret, max_retries, timeout).
     * @param  string  $baseUrl  Base URL for this API surface (e.g. https://checkoutapistage.svea.com).
     * @param  HandlerStack|null  $handlerStack  Optional Guzzle handler stack.
     */
    public function __construct(
        private readonly array $config,
        string $baseUrl,
        ?HandlerStack $handlerStack = null,
    ) {
        $stack = $handlerStack ?? HandlerStack::create();
        $stack->push(RetryMiddleware::make((int) ($config['max_retries'] ?? 0)));

        $this->client = new Client([
            'base_uri' => rtrim($baseUrl, '/').'/',
            'timeout' => $config['timeout'] ?? 10,
            'handler' => $stack,
            'http_errors' => false,
        ]);
    }

    /**
     * Send a GET request to the given API path.
     *
     * @param  string  $path  Relative path, e.g. `api/orders/12345678`.
     *
     * @throws SveaConnectionException On network failure or timeout.
     * @throws SveaAuthenticationException On HTTP 401.
     * @throws SveaNotFoundException On HTTP 404.
     * @throws SveaRateLimitException On HTTP 429.
     * @throws SveaApiException On any other non-2xx response.
     */
    public function get(string $path): SveaResponse
    {
        return $this->send('GET', $path);
    }

    /**
     * Send a POST request to the given API path with an optional JSON body.
     *
     * @param  string  $path  Relative path.
     * @param  array<string, mixed>  $data  Request body data (JSON-encoded automatically).
     * @param  string|null  $idempotencyKey  Optional idempotency key sent as `Idempotency-Key` header.
     *
     * @throws SveaConnectionException On network failure or timeout.
     * @throws SveaAuthenticationException On HTTP 401.
     * @throws SveaNotFoundException On HTTP 404.
     * @throws SveaRateLimitException On HTTP 429.
     * @throws SveaApiException On any other non-2xx response.
     */
    public function post(string $path, array $data = [], ?string $idempotencyKey = null): SveaResponse
    {
        return $this->send('POST', $path, $data, $idempotencyKey);
    }

    /**
     * Send a PUT request to the given API path with an optional JSON body.
     *
     * @param  string  $path  Relative path.
     * @param  array<string, mixed>  $data  Request body data (JSON-encoded automatically).
     *
     * @throws SveaConnectionException On network failure or timeout.
     * @throws SveaAuthenticationException On HTTP 401.
     * @throws SveaNotFoundException On HTTP 404.
     * @throws SveaRateLimitException On HTTP 429.
     * @throws SveaApiException On any other non-2xx response.
     */
    public function put(string $path, array $data = []): SveaResponse
    {
        return $this->send('PUT', $path, $data);
    }

    /**
     * Send a PATCH request to the given API path with an optional JSON body.
     *
     * @param  string  $path  Relative path.
     * @param  array<string, mixed>  $data  Request body data (JSON-encoded automatically).
     *
     * @throws SveaConnectionException On network failure or timeout.
     * @throws SveaAuthenticationException On HTTP 401.
     * @throws SveaNotFoundException On HTTP 404.
     * @throws SveaRateLimitException On HTTP 429.
     * @throws SveaApiException On any other non-2xx response.
     */
    public function patch(string $path, array $data = []): SveaResponse
    {
        return $this->send('PATCH', $path, $data);
    }

    /**
     * Send a DELETE request to the given API path.
     *
     * @param  string  $path  Relative path.
     *
     * @throws SveaConnectionException On network failure or timeout.
     * @throws SveaAuthenticationException On HTTP 401.
     * @throws SveaNotFoundException On HTTP 404.
     * @throws SveaRateLimitException On HTTP 429.
     * @throws SveaApiException On any other non-2xx response.
     */
    public function delete(string $path): SveaResponse
    {
        return $this->send('DELETE', $path);
    }

    /**
     * Execute an HTTP request and return a wrapped {@see SveaResponse}.
     *
     * Adds the Authorization, Timestamp, Content-Type, and optional Idempotency-Key
     * headers before dispatching. Redirects are disabled so that 303 responses from
     * async task endpoints can be inspected directly.
     *
     * @param  string  $method  HTTP method (GET, POST, PUT, DELETE).
     * @param  string  $path  Relative path.
     * @param  array<string, mixed>  $data  Optional request body data.
     * @param  string|null  $idempotencyKey  Optional idempotency key header value.
     *
     * @throws SveaConnectionException On {@see ConnectException}.
     * @throws SveaApiException (or subtype) On non-2xx responses.
     */
    private function send(string $method, string $path, array $data = [], ?string $idempotencyKey = null): SveaResponse
    {
        $body = $data !== [] ? (string) json_encode($data) : '';
        $timestamp = gmdate('Y-m-d H:i');
        $headers = array_filter([
            'Authorization' => $this->buildAuthHeader($body, $timestamp),
            'Timestamp' => $timestamp,
            'Content-Type' => 'application/json',
            'Idempotency-Key' => $idempotencyKey,
        ]);

        try {
            $psrResponse = $this->client->request($method, $path, [
                'headers' => $headers,
                'body' => $body ?: null,
                'allow_redirects' => false,
            ]);
        } catch (ConnectException $e) {
            throw new SveaConnectionException($e->getMessage(), previous: $e);
        }

        $response = new SveaResponse($psrResponse);
        $this->throwForStatus($response);

        return $response;
    }

    /**
     * Build the Svea HMAC-SHA512 Authorization header.
     *
     * Format: `Svea base64(merchantId:sha512(body + sharedSecret + timestamp))`
     *
     * The timestamp is included in the hash to prevent replay attacks.
     *
     * @param  string  $body  JSON-encoded request body (empty string for bodyless requests).
     * @param  string  $timestamp  UTC timestamp string in `Y-m-d H:i` format.
     */
    private function buildAuthHeader(string $body, string $timestamp): string
    {
        $hash = hash('sha512', $body.$this->config['shared_secret'].$timestamp);
        $token = base64_encode($this->config['merchant_id'].':'.$hash);

        return "Svea {$token}";
    }

    /**
     * Throw a typed exception for any non-successful HTTP response.
     *
     * @throws SveaAuthenticationException On HTTP 401.
     * @throws SveaNotFoundException On HTTP 404.
     * @throws SveaRateLimitException On HTTP 429.
     * @throws SveaApiException On any other non-2xx response.
     */
    private function throwForStatus(SveaResponse $response): void
    {
        if ($response->successful()) {
            return;
        }

        throw match (true) {
            $response->statusCode === 401 => new SveaAuthenticationException($response),
            $response->statusCode === 404 => new SveaNotFoundException($response),
            $response->statusCode === 429 => new SveaRateLimitException($response),
            default => new SveaApiException($response),
        };
    }
}
