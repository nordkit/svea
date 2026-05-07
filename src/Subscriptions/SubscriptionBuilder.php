<?php

declare(strict_types=1);

namespace Svea\Subscriptions;

use Svea\Exceptions\SveaApiException;
use Svea\Exceptions\SveaAuthenticationException;
use Svea\Support\Conditionable;
use Svea\Transport\SveaConnector;

/**
 * Fluent builder for registering a new Svea webhook subscription.
 *
 * Obtained via SubscriptionService::on() and completed with register().
 * Supports conditional chaining via the Conditionable trait (when/unless).
 *
 * Full example:
 *   $subscription = $svea->subscriptions()
 *       ->on(EventType::CheckoutOrderCreated, EventType::CheckoutOrderDelivered)
 *       ->when($isProd, fn ($b) => $b->notifyAt('https://myapp.com/webhooks/svea'))
 *       ->unless($isProd, fn ($b) => $b->notifyAt('https://staging.myapp.com/webhooks/svea'))
 *       ->register();
 *
 * Endpoint: POST /api/v2/callbacks/subscriptions
 *
 * @see SubscriptionService::on()
 * @see https://docs.payments.svea.com/docs/manage-order/callbacks/add_subscription
 */
class SubscriptionBuilder
{
    use Conditionable;

    /** @var EventType[] */
    private array $eventTypes = [];

    private string $callbackUrl = '';

    /**
     * @param  SveaConnector  $connector  Transport layer for the subscriptions API surface.
     * @param  string  $defaultCallbackUrl  Default callback URL — used if notifyAt() is not called.
     */
    public function __construct(
        private readonly SveaConnector $connector,
        string $defaultCallbackUrl = '',
    ) {
        $this->callbackUrl = $defaultCallbackUrl;
    }

    /**
     * Set the event types this subscription should listen for.
     *
     * Replaces any previously set event types. Can be called multiple times
     * in a chain but only the last call takes effect.
     *
     * @param  EventType  ...$types  One or more event types to subscribe to.
     */
    public function on(EventType ...$types): static
    {
        $this->eventTypes = $types;

        return $this;
    }

    /**
     * Set the callback URL that Svea will POST webhook events to.
     *
     * The URL must be publicly reachable over HTTPS. After registering,
     * call SubscriptionService::verify() to send a Ping and confirm the
     * endpoint is live.
     *
     * @param  string  $url  A publicly reachable HTTPS URL.
     */
    public function notifyAt(string $url): static
    {
        $this->callbackUrl = $url;

        return $this;
    }

    /**
     * Persist the subscription by calling the Svea API.
     *
     * Sends a POST request with the configured CallbackUri and Events array.
     * Returns the created Subscription with its assigned SubscriptionId.
     *
     * @throws SveaAuthenticationException if credentials are invalid (401/403).
     * @throws SveaApiException on any other non-2xx response.
     *
     * @see https://docs.payments.svea.com/docs/manage-order/callbacks/add_subscription
     */
    public function register(): Subscription
    {
        $response = $this->connector->post(SubscriptionService::SUBSCRIPTIONS, [
            'CallbackUri' => $this->callbackUrl,
            'Events' => array_map(fn (EventType $t) => $t->value, $this->eventTypes),
        ]);

        return Subscription::make($response->json ?? $response)->withLastResponse($response);
    }
}
