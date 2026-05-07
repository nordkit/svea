<?php

declare(strict_types=1);

namespace Svea\Exceptions;

use Svea\Transport\SveaResponse;

/**
 * Thrown for any non-2xx HTTP response from the Svea API.
 *
 * Carries the raw {@see SveaResponse} for inspection, as well as the parsed
 * HTTP status code and the full Svea error payload.
 *
 * Concrete subtypes cover the most common error statuses:
 * - {@see SveaAuthenticationException} — 401 invalid credentials
 * - {@see SveaInvalidRequestException} — 400 validation failure
 * - {@see SveaNotFoundException}       — 404 resource not found
 * - {@see SveaRateLimitException}      — 429 rate limit exceeded
 *
 * All other non-2xx responses are thrown as `SveaApiException` directly.
 */
class SveaApiException extends SveaException
{
    /** HTTP status code returned by the Svea API. */
    public readonly int $statusCode;

    /**
     * Raw error payload from the Svea API response body.
     *
     * @var array<string, mixed>
     */
    public readonly array $sveaError;

    /**
     * @param  SveaResponse  $response  The raw HTTP response that triggered this exception.
     */
    public function __construct(protected readonly SveaResponse $response)
    {
        $this->statusCode = $response->statusCode;
        $this->sveaError = $response->json;

        parent::__construct(
            message: $response->json['Message'] ?? "Svea API error [{$response->statusCode}]",
            code: $response->statusCode,
        );
    }

    /**
     * Return the raw HTTP response that triggered this exception.
     */
    public function getLastResponse(): SveaResponse
    {
        return $this->response;
    }
}
