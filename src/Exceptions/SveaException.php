<?php

declare(strict_types=1);

namespace Svea\Exceptions;

/**
 * Base exception for all Svea SDK errors.
 *
 * Every exception thrown by this SDK extends this class, making it easy to
 * catch all Svea-related errors with a single `catch (SveaException $e)` block.
 *
 * Hierarchy:
 * ```
 * SveaException
 * ├── SveaApiException              — any non-2xx HTTP response
 * │   ├── SveaAuthenticationException  — 401 invalid credentials
 * │   ├── SveaInvalidRequestException  — 400 validation failure (carries $errors)
 * │   ├── SveaNotFoundException        — 404 resource not found
 * │   └── SveaRateLimitException       — 429 rate limited (triggers auto-retry)
 * ├── SveaConnectionException        — network failure or timeout (triggers auto-retry)
 * └── SignatureVerificationException — inbound webhook HMAC mismatch
 * ```
 */
class SveaException extends \RuntimeException {}
