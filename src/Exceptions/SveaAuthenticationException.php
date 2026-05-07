<?php

declare(strict_types=1);

namespace Svea\Exceptions;

/**
 * Thrown when Svea returns HTTP 401 — invalid merchant credentials.
 *
 * Check that `merchant_id` and `shared_secret` in your config are correct
 * and match the environment ('test' vs 'production').
 */
class SveaAuthenticationException extends SveaApiException {}
