<?php

declare(strict_types=1);

namespace Svea\Exceptions;

/**
 * Thrown when Svea returns HTTP 404 — the requested order or resource was not found.
 *
 * Verify that the order ID is correct and belongs to the authenticated merchant.
 */
class SveaNotFoundException extends SveaApiException {}
