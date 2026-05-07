<?php

declare(strict_types=1);

namespace Svea\Transport;

use Psr\Http\Message\ResponseInterface;
use Svea\SveaResource;

/**
 * Immutable value object wrapping a PSR-7 HTTP response from the Svea API.
 *
 * Eagerly parses the response body on construction so downstream code can
 * read `->json`, `->body`, `->statusCode`, and `->headers` without any
 * further I/O or decoding.
 *
 * Attached to every {@see SveaResource} via `withLastResponse()` so
 * callers can inspect the raw HTTP response after any API call:
 * ```php
 * $response = $svea->checkout()->get('12345678');
 * $response->getLastResponse()->statusCode; // 200
 * $response->getLastResponse()->headers;    // array<string, string[]>
 * ```
 *
 * The `successful()` method treats 302 and 303 as success because Svea uses
 * HTTP 303 to signal async task completion (Location header = resource URL).
 */
final readonly class SveaResponse
{
    /** HTTP status code of the response. */
    public int $statusCode;

    /**
     * Response headers, keyed by header name with an array of values.
     *
     * @var array<string, string[]>
     */
    public array $headers;

    /** Raw response body as a string. */
    public string $body;

    /**
     * JSON-decoded response body. Empty array if the body is not valid JSON
     * or the response has no body.
     *
     * @var array<string, mixed>
     */
    public array $json;

    /**
     * @param  ResponseInterface  $response  The PSR-7 response to wrap.
     */
    public function __construct(ResponseInterface $response)
    {
        $this->statusCode = $response->getStatusCode();
        $this->headers = $response->getHeaders();
        $this->body = (string) $response->getBody();
        $this->json = json_decode($this->body, true) ?? [];
    }

    /**
     * Returns true for HTTP status codes considered successful by this SDK.
     *
     * Includes 302 and 303 per Svea's async task pattern, where a 303 with a
     * `Location` header signals that the task has completed and the resource
     * is available at the redirect target.
     */
    public function successful(): bool
    {
        return in_array($this->statusCode, [200, 201, 202, 204, 302, 303], true);
    }
}
