<?php

declare(strict_types=1);

namespace Svea\Exceptions;

use Svea\Transport\SveaResponse;

/**
 * Thrown when Svea returns HTTP 400 — the request payload failed validation.
 *
 * The `$errors` property carries the field-level error details returned by Svea,
 * parsed from the `Errors` or `errors` key in the response body.
 */
class SveaInvalidRequestException extends SveaApiException
{
    /**
     * Field-level validation errors returned by the Svea API.
     *
     * @var array<string, mixed>
     */
    public readonly array $errors;

    /**
     * @param  SveaResponse  $response  The raw 400 HTTP response.
     */
    public function __construct(SveaResponse $response)
    {
        parent::__construct($response);
        $this->errors = $response->json['Errors'] ?? $response->json['errors'] ?? [];
    }
}
