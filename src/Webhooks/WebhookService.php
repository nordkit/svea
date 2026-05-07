<?php

declare(strict_types=1);

namespace Svea\Webhooks;

use Psr\Http\Message\RequestInterface;
use Svea\Exceptions\SignatureVerificationException;

/**
 * PSR-7-aware entry point for parsing and verifying inbound Svea webhook requests.
 *
 * A thin wrapper around {@see Webhook::constructEvent()} that extracts the raw
 * body and `Svea-Signature` header from any PSR-7 `RequestInterface` automatically.
 *
 * Bind this in your DI container with the webhook secret:
 * ```php
 * $container->bind(WebhookService::class, fn () => new WebhookService(
 *     secret: config('svea.webhook_secret'),
 * ));
 * ```
 *
 * Then use it in a controller or middleware:
 * ```php
 * $event = $webhookService->fromRequest($psrRequest);
 * ```
 *
 * Laravel users can inject the service via the `Svea::webhook()` facade or
 * the `SveaServiceProvider` binding.
 *
 * @see Webhook::constructEvent()
 * @see WebhookEvent
 */
class WebhookService
{
    /**
     * @param  string  $secret  The webhook secret registered with the Svea subscription.
     */
    public function __construct(private readonly string $secret) {}

    /**
     * Parse and verify an inbound Svea webhook from a PSR-7 request.
     *
     * Reads the raw body from `$request->getBody()` and the signature from the
     * `Svea-Signature` header, then delegates to {@see Webhook::constructEvent()}.
     *
     * @param  RequestInterface  $request  The incoming PSR-7 webhook request.
     *
     * @throws SignatureVerificationException When the signature is invalid.
     */
    public function fromRequest(RequestInterface $request): WebhookEvent
    {
        return Webhook::constructEvent(
            payload: (string) $request->getBody(),
            signature: $request->getHeaderLine('Svea-Signature'),
            secret: $this->secret,
        );
    }
}
