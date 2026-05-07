<?php

declare(strict_types=1);

namespace Svea\Exceptions;

use Svea\Transport\RetryMiddleware;

/**
 * Thrown when Svea returns HTTP 429 — the request rate limit has been exceeded.
 *
 * When `max_retries` is greater than 0, {@see RetryMiddleware}
 * will automatically retry the request with exponential backoff before this
 * exception is thrown to the caller.
 */
class SveaRateLimitException extends SveaApiException {}
