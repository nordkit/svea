<?php

declare(strict_types=1);

namespace Svea\Transport;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Guzzle retry middleware for transient Svea API failures.
 *
 * Retries requests on:
 * - {@see ConnectException} — network failure or timeout
 * - HTTP 429 — rate limit exceeded
 * - HTTP 500 — internal server error
 * - HTTP 503 — service unavailable
 *
 * Delay formula: `min(1000 * 2^attempt, 32_000) ms + random(0–1000) ms jitter`
 *
 * Example with `max_retries = 2`:
 * - Attempt 1 → ~2 000 ms
 * - Attempt 2 → ~4 000 ms
 *
 * Opt in via the `max_retries` config key (default 0 — no retries):
 * ```php
 * $svea = new SveaClient(['max_retries' => 2, ...]);
 * ```
 */
final class RetryMiddleware
{
    /**
     * Create a Guzzle retry middleware callable configured for the given maximum retries.
     *
     * @param  int  $maxRetries  Maximum number of retry attempts (0 disables retrying).
     * @return callable Guzzle middleware callable.
     */
    public static function make(int $maxRetries): callable
    {
        return Middleware::retry(
            decider: self::decider($maxRetries),
            delay: self::delay(),
        );
    }

    /**
     * Build the retry decider — returns true when the request should be retried.
     *
     * @param  int  $maxRetries  Maximum retry ceiling.
     * @return callable Guzzle decider callable.
     */
    private static function decider(int $maxRetries): callable
    {
        return function (
            int $retries,
            RequestInterface $request,
            ?ResponseInterface $response,
            ?\Throwable $exception
        ) use ($maxRetries): bool {
            if ($retries >= $maxRetries) {
                return false;
            }

            if ($exception instanceof ConnectException) {
                return true;
            }

            if ($response !== null && in_array($response->getStatusCode(), [429, 500, 503], true)) {
                return true;
            }

            return false;
        };
    }

    /**
     * Build the retry delay calculator using exponential backoff with jitter.
     *
     * @return callable Guzzle delay callable — receives attempt number, returns delay in ms.
     */
    private static function delay(): callable
    {
        return function (int $attempt): int {
            $base = min(1000 * (2 ** $attempt), 32_000);
            $jitter = random_int(0, 1000);

            return $base + $jitter;
        };
    }
}
