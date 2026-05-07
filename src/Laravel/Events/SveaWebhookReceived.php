<?php

declare(strict_types=1);

namespace Svea\Laravel\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Svea\Webhooks\WebhookEvent;

/**
 * Laravel event dispatched when an inbound Svea webhook is successfully verified.
 *
 * Dispatch this event from your webhook controller after calling
 * `WebhookService::fromRequest()`:
 *
 * ```php
 * $event = $webhookService->fromRequest($request);
 * SveaWebhookReceived::dispatch($event);
 * ```
 *
 * Register a listener in your `EventServiceProvider` (or via `#[AsListener]`):
 *
 * ```php
 * protected $listen = [
 *     SveaWebhookReceived::class => [
 *         HandleSveaWebhook::class,
 *     ],
 * ];
 * ```
 *
 * In the listener, access the typed {@see WebhookEvent}:
 *
 * ```php
 * public function handle(SveaWebhookReceived $event): void
 * {
 *     match ($event->payload->type()) {
 *         EventType::CheckoutOrderDelivered      => $this->handleDelivered($event->payload),
 *         EventType::CheckoutOrderCreditSucceeded => $this->handleCredited($event->payload),
 *         default                                => null,
 *     };
 * }
 * ```
 *
 * @see WebhookEvent  The wrapped Svea webhook payload
 */
class SveaWebhookReceived
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  WebhookEvent  $payload  The verified and parsed Svea webhook event.
     */
    public function __construct(
        public readonly WebhookEvent $payload
    ) {}
}
