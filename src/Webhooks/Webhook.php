<?php

declare(strict_types=1);

namespace Svea\Webhooks;

use Svea\Exceptions\SignatureVerificationException;

/**
 * Static entry point for parsing and verifying inbound Svea webhook payloads.
 *
 * Mirrors the Stripe `Webhook::constructEvent()` pattern — a single, familiar
 * call that validates the signature and returns a typed event object or throws.
 *
 * Typical usage in a Laravel controller:
 * ```php
 * public function __invoke(Request $request): Response
 * {
 *     try {
 *         $event = Webhook::constructEvent(
 *             payload:   $request->getContent(),
 *             signature: $request->header('Svea-Signature'),
 *             secret:    config('svea.webhook_secret'),
 *         );
 *     } catch (SignatureVerificationException $e) {
 *         return response('Invalid signature', 400);
 *     }
 *
 *     match ($event->type()) {
 *         EventType::CheckoutOrderDelivered => $this->handleDelivered($event),
 *         default => null,
 *     };
 *
 *     return response('OK');
 * }
 * ```
 *
 * For PSR-7 applications, prefer {@see WebhookService::fromRequest()} which
 * extracts the body and header automatically.
 *
 * @see WebhookService
 * @see SignatureVerifier
 */
final class Webhook
{
    /**
     * Parse and verify an inbound Svea webhook payload.
     *
     * Verifies the HMAC-SHA256 signature, decodes the JSON body, and returns
     * a typed {@see WebhookEvent}.
     *
     * @param  string  $payload  Raw request body (before any decoding).
     * @param  string  $signature  Value of the `Svea-Signature` request header.
     * @param  string  $secret  The webhook secret configured on the subscription.
     *
     * @throws SignatureVerificationException When the signature is invalid.
     */
    public static function constructEvent(
        string $payload,
        string $signature,
        string $secret,
    ): WebhookEvent {
        (new SignatureVerifier)->verify($payload, $signature, $secret);
        /** @var array<string, mixed> $data */
        $data = json_decode($payload, true) ?? [];

        return WebhookEvent::make($data);
    }
}
