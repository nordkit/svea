<?php

declare(strict_types=1);

namespace Svea\Webhooks;

use Svea\Exceptions\SignatureVerificationException;

/**
 * Pure HMAC-SHA256 signature verifier for inbound Svea webhook callbacks.
 *
 * Stateless and side-effect-free — takes the raw request body, the value from
 * the `Svea-Signature` header, and the webhook secret, then either returns
 * cleanly or throws {@see SignatureVerificationException}.
 *
 * Algorithm: `HMAC-SHA256(rawBody, webhookSecret)`
 *
 * Timing-safe comparison via `hash_equals()` prevents timing attacks.
 *
 * Called internally by {@see Webhook::constructEvent()} and
 * {@see WebhookService::fromRequest()}. Can also be used directly when
 * building a custom webhook handler:
 * ```php
 * (new SignatureVerifier)->verify(
 *     payload:   $request->getContent(),
 *     signature: $request->header('Svea-Signature'),
 *     secret:    config('svea.webhook_secret'),
 * );
 * ```
 */
final class SignatureVerifier
{
    /**
     * Verify the HMAC-SHA256 signature on an inbound Svea webhook payload.
     *
     * @param  string  $payload  Raw request body (before any decoding).
     * @param  string  $signature  Value of the `Svea-Signature` request header.
     * @param  string  $secret  The webhook secret registered with the subscription.
     *
     * @throws SignatureVerificationException When the computed HMAC does not match the provided signature.
     */
    public function verify(string $payload, string $signature, string $secret): void
    {
        $expected = hash_hmac('sha256', $payload, $secret);
        if (! hash_equals($expected, $signature)) {
            throw new SignatureVerificationException(
                'Svea webhook signature mismatch. Ensure SVEA_WEBHOOK_SECRET is correct.'
            );
        }
    }
}
