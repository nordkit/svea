<?php

declare(strict_types=1);

namespace Svea\Subscriptions;

use Svea\Contracts\SubscriptionServiceInterface;
use Svea\Exceptions\SveaApiException;
use Svea\Exceptions\SveaAuthenticationException;
use Svea\Transport\SveaConnector;

/**
 * Svea Subscription API service.
 *
 * Manages webhook subscriptions that notify your application when Svea
 * checkout order events occur (created, delivered, credited, closed, etc.).
 *
 * Base URL (test):       http://paymentadminapistage.svea.com
 * Base URL (production): https://paymentadminapi.svea.com
 *
 * All endpoints require HMAC-SHA512 authentication handled automatically
 * by SveaConnector. Subscriptions are scoped per merchant.
 *
 * Usage — fluent builder:
 *   $subscription = $svea->subscriptions()
 *       ->on(EventType::CheckoutOrderCreated, EventType::CheckoutOrderDelivered)
 *       ->notifyAt('https://myapp.com/webhooks/svea')
 *       ->register();
 *
 * Usage — direct methods:
 *   $subscription = $svea->subscriptions()->add('https://myapp.com/webhooks/svea', [
 *       EventType::CheckoutOrderCreated,
 *   ]);
 *
 *   $subscriptions = $svea->subscriptions()->list();
 *   $svea->subscriptions()->verify($subscription->id());
 *   $svea->subscriptions()->remove($subscription->id());
 *
 * @see https://docs.payments.svea.com/docs/manage-order/callbacks
 */
class SubscriptionService implements SubscriptionServiceInterface
{
    public const SUBSCRIPTIONS = 'api/v2/callbacks/subscriptions';

    /**
     * @param  SveaConnector  $connector  Transport layer for the subscriptions API surface.
     * @param  string  $defaultCallbackUrl  Optional default callback URL pre-filled in the builder.
     */
    public function __construct(
        private readonly SveaConnector $connector,
        private readonly string $defaultCallbackUrl = '',
    ) {}

    /**
     * Start building a new webhook subscription fluently.
     *
     * Returns a SubscriptionBuilder that lets you chain event types and
     * a callback URL before calling register() to persist the subscription.
     *
     * Example:
     *   $svea->subscriptions()
     *       ->on(EventType::CheckoutOrderCreated)
     *       ->notifyAt('https://myapp.com/webhooks/svea')
     *       ->register();
     *
     * @param  EventType  ...$types  One or more event types to subscribe to.
     */
    public function on(EventType ...$types): SubscriptionBuilder
    {
        return (new SubscriptionBuilder($this->connector, $this->defaultCallbackUrl))->on(...$types);
    }

    /**
     * List all registered webhook subscriptions for the authenticated merchant.
     *
     * Endpoint: GET /api/v2/callbacks/subscriptions/
     *
     * Response fields per subscription:
     *   - SubscriptionId (GUID)   — unique identifier
     *   - CallbackUri   (string)  — the registered webhook URL
     *   - Events        (string[])— list of subscribed event type strings
     *   - Verified      (bool)    — whether the callback URL has been verified
     *
     * @return array<int, Subscription>
     *
     * @throws SveaAuthenticationException if credentials are invalid (401/403).
     * @throws SveaApiException on any other non-2xx response.
     *
     * @see https://docs.payments.svea.com/docs/manage-order/callbacks/get_subscriptions
     */
    public function list(): array
    {
        $response = $this->connector->get(self::SUBSCRIPTIONS.'/');
        $items = (array) ($response->json['Subscriptions'] ?? $response->json);

        return array_values(array_map(fn (array $item) => Subscription::make($item), $items));
    }

    /**
     * Retrieve a single registered subscription by its GUID.
     *
     * Endpoint: GET /api/v2/callbacks/subscriptions/{subscriptionId}
     *
     * @param  string  $subscriptionId  The GUID of the subscription to retrieve.
     *
     * @throws SveaAuthenticationException if credentials are invalid (401/403).
     * @throws SveaApiException on any other non-2xx response.
     */
    public function get(string $subscriptionId): Subscription
    {
        $response = $this->connector->get(self::SUBSCRIPTIONS."/{$subscriptionId}");

        return Subscription::make($response->json ?? $response)->withLastResponse($response);
    }

    /**
     * Add a new webhook subscription directly (non-fluent alternative to the builder).
     *
     * Endpoint: POST /api/v2/callbacks/subscriptions
     *
     * Request body:
     *   - CallbackUri (string)   — required, the HTTPS URL Svea will POST events to
     *   - Events      (string[]) — required, list of event type strings
     *
     * Response: { "SubscriptionId": "<guid>" }
     *
     * @param  string  $callbackUrl  The HTTPS URL Svea will POST webhook events to.
     * @param  EventType[]  $eventTypes  The events that should trigger the webhook.
     *
     * @throws SveaAuthenticationException if credentials are invalid (401/403).
     * @throws SveaApiException on any other non-2xx response.
     *
     * @see https://docs.payments.svea.com/docs/manage-order/callbacks/add_subscription
     */
    public function add(string $callbackUrl, array $eventTypes): Subscription
    {
        $response = $this->connector->post(self::SUBSCRIPTIONS, [
            'CallbackUri' => $callbackUrl ?: $this->defaultCallbackUrl,
            'Events' => array_map(fn (EventType $t) => $t->value, $eventTypes),
        ]);

        return Subscription::make($response->json ?? $response)->withLastResponse($response);
    }

    /**
     * Update an existing subscription by replacing its callback URL and event types.
     *
     * Endpoint: PUT /api/v2/callbacks/subscriptions/{subscriptionId}
     *
     * Note: If the callback URL is changed the subscription will need to be
     * re-verified via verify() before Svea will deliver events to it.
     *
     * Request body:
     *   - CallbackUri (string)   — required, the new HTTPS URL
     *   - Events      (string[]) — required, the new list of event type strings
     *
     * @param  string  $subscriptionId  The GUID of the subscription to update.
     * @param  string  $callbackUrl  The new callback URL to receive events.
     * @param  EventType[]  $eventTypes  The new set of event types to subscribe to.
     *
     * @throws SveaAuthenticationException if credentials are invalid (401/403).
     * @throws SveaApiException on any other non-2xx response.
     *
     * @see https://docs.payments.svea.com/docs/manage-order/callbacks/update_subscription
     */
    public function update(string $subscriptionId, string $callbackUrl, array $eventTypes): Subscription
    {
        $response = $this->connector->put(self::SUBSCRIPTIONS."/{$subscriptionId}", [
            'CallbackUri' => $callbackUrl,
            'Events' => array_map(fn (EventType $t) => $t->value, $eventTypes),
        ]);

        return Subscription::make($response->json ?? $response)->withLastResponse($response);
    }

    /**
     * Remove a registered subscription by its GUID.
     *
     * Endpoint: DELETE /api/v2/callbacks/subscriptions/{subscriptionId}
     *
     * After removal Svea will no longer deliver webhook events for this
     * subscription. This action is irreversible.
     *
     * @param  string  $subscriptionId  The GUID of the subscription to delete.
     *
     * @throws SveaAuthenticationException if credentials are invalid (401/403).
     * @throws SveaApiException on any other non-2xx response.
     *
     * @see https://docs.payments.svea.com/docs/manage-order/callbacks/remove_subscription
     */
    public function remove(string $subscriptionId): void
    {
        $this->connector->delete(self::SUBSCRIPTIONS."/{$subscriptionId}");
    }

    /**
     * Verify a registered subscription's callback URL.
     *
     * Endpoint: POST /api/v2/callbacks/subscriptions/{subscriptionId}/verify
     *
     * Svea sends a Ping event to the registered CallbackUri. If the endpoint
     * responds successfully, the subscription's Verified flag is set to true
     * and Svea will begin (or continue) delivering events.
     *
     * A new or updated subscription must be verified before events are delivered.
     *
     * @param  string  $subscriptionId  The GUID of the subscription to verify.
     *
     * @throws SveaAuthenticationException if credentials are invalid (401/403).
     * @throws SveaApiException if the webhook URL is invalid (400) or
     *                          any other non-2xx response.
     *
     * @see https://docs.payments.svea.com/docs/manage-order/callbacks/verify_subscription
     */
    public function verify(string $subscriptionId): void
    {
        $this->connector->post(self::SUBSCRIPTIONS."/{$subscriptionId}/verify");
    }
}
