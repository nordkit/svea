<?php

declare(strict_types=1);

namespace Svea\Exceptions;

/**
 * Thrown when an inbound webhook's HMAC-SHA256 signature does not match.
 *
 * This indicates that the request did not originate from Svea, or that
 * the `webhook_secret` config value does not match the secret registered
 * with the subscription.
 *
 * When caught, respond with HTTP 400 to signal rejection to the caller.
 */
class SignatureVerificationException extends SveaException {}
