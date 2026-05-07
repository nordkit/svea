<?php

declare(strict_types=1);

namespace Svea\Exceptions;

use GuzzleHttp\Exception\ConnectException;
use Svea\Transport\RetryMiddleware;

/**
 * Thrown when a network-level failure or timeout occurs while communicating with Svea.
 *
 * Wraps a Guzzle {@see ConnectException} at the transport layer.
 *
 * When `max_retries` is greater than 0, {@see RetryMiddleware}
 * will automatically retry the request with exponential backoff before this
 * exception is thrown to the caller.
 */
class SveaConnectionException extends SveaException {}
