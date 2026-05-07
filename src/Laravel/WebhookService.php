<?php

declare(strict_types=1);

namespace Svea\Laravel;

use Illuminate\Http\Request;
use Svea\Exceptions\SignatureVerificationException;
use Svea\Webhooks\Webhook;
use Svea\Webhooks\WebhookEvent;

/**
 * Laravel bridge for inbound Svea webhook verification.
 *
 * Extracts the raw request body and the `Svea-Signature` header from an
 * `Illuminate\Http\Request` and delegates to the framework-agnostic
 * {@see Webhook::constructEvent()} static method.
 *
 * This class exists because `Svea\Webhooks\WebhookService` accepts a PSR-7
 * `RequestInterface` — an interface not natively implemented by Laravel's
 * `Illuminate\Http\Request`. Rather than adding a PSR-7 bridge dependency to
 * the core SDK, this thin wrapper handles the translation in the Laravel layer.
 *
 * Bound in the container by {@see SveaServiceProvider} using the `webhook_secret`
 * from `config/svea.php`:
 *
 * ```php
 * // app/Http/Controllers/SveaWebhookController.php
 * public function __invoke(Request $request, WebhookService $webhookService): Response
 * {
 *     try {
 *         $event = $webhookService->fromRequest($request);
 *     } catch (SignatureVerificationException) {
 *         return response()->json(['error' => 'invalid signature'], 400);
 *     }
 *
 *     match ($event->type()) {
 *         EventType::CheckoutOrderDelivered => $this->handleDelivered($event),
 *         default                           => null,
 *     };
 *
 *     return response()->noContent();
 * }
 * ```
 *
 * @see Webhook::constructEvent()  Framework-agnostic entry point
 * @see \Svea\Webhooks\WebhookService  PSR-7 variant
 */
class WebhookService
{
    /**
     * @param  string  $secret  The webhook secret from `config('svea.webhook_secret')`,
     *                          used to verify the `Svea-Signature` HMAC-SHA256 header.
     */
    public function __construct(private readonly string $secret) {}

    /**
     * Parse and verify an inbound webhook from a Laravel Request.
     *
     * Extracts the raw body via `$request->getContent()` (bypasses JSON
     * parsing so the signature remains verifiable) and reads the
     * `Svea-Signature` header before calling `Webhook::constructEvent()`.
     *
     * @param  Request  $request  The incoming webhook request.
     *
     * @throws SignatureVerificationException When the HMAC-SHA256 signature does not match.
     */
    public function fromRequest(Request $request): WebhookEvent
    {
        return Webhook::constructEvent(
            payload: $request->getContent(),
            signature: (string) $request->header('Svea-Signature', ''),
            secret: $this->secret,
        );
    }
}
