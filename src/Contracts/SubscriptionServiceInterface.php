<?php

declare(strict_types=1);

namespace Svea\Contracts;

use Svea\Subscriptions\EventType;
use Svea\Subscriptions\Subscription;
use Svea\Subscriptions\SubscriptionService;
use Svea\Testing\FakeSubscriptionService;

/**
 * Contract for the Svea Webhook Subscription API service.
 *
 * Implemented by {@see SubscriptionService} for real API calls
 * and by {@see FakeSubscriptionService} for in-memory test doubles.
 *
 * Type-hint against this interface in application code to allow seamless
 * swapping between the real service and the fake in tests.
 */
interface SubscriptionServiceInterface
{
    /**
     * Start a fluent subscription builder by specifying the event types to listen for.
     *
     * The return type is intentionally untyped so that both
     * {@see SubscriptionService} (returns {@see SubscriptionBuilder})
     * and {@see FakeSubscriptionService} (returns itself for chaining)
     * can satisfy this contract without a shared base class.
     *
     * Chain with notifyAt() and register() to persist the subscription:
     * ```php
     * $svea->subscriptions()
     *     ->on(EventType::CheckoutOrderCreated)
     *     ->notifyAt('https://myapp.com/webhooks/svea')
     *     ->register();
     * ```
     *
     * @param  EventType  ...$types  One or more event types to subscribe to.
     */
    public function on(EventType ...$types): mixed;

    /**
     * List all registered webhook subscriptions for the authenticated merchant.
     *
     * @return array<int, Subscription>
     */
    public function list(): array;

    /**
     * Retrieve a single registered subscription by its GUID.
     *
     * @param  string  $subscriptionId  The GUID of the subscription to retrieve.
     */
    public function get(string $subscriptionId): Subscription;

    /**
     * Add a new webhook subscription directly (non-fluent alternative to the builder).
     *
     * @param  string  $callbackUrl  The HTTPS URL Svea will POST webhook events to.
     * @param  EventType[]  $eventTypes  The events that should trigger the webhook.
     */
    public function add(string $callbackUrl, array $eventTypes): Subscription;

    /**
     * Update an existing subscription by replacing its callback URL and event types.
     *
     * Note: changing the callback URL requires re-verification via verify().
     *
     * @param  string  $subscriptionId  The GUID of the subscription to update.
     * @param  string  $callbackUrl  The new callback URL.
     * @param  EventType[]  $eventTypes  The new set of event types.
     */
    public function update(string $subscriptionId, string $callbackUrl, array $eventTypes): Subscription;

    /**
     * Remove a registered subscription by its GUID.
     *
     * After removal Svea will no longer deliver events for this subscription.
     *
     * @param  string  $subscriptionId  The GUID of the subscription to delete.
     */
    public function remove(string $subscriptionId): void;

    /**
     * Trigger Svea to verify a subscription's callback URL via a Ping event.
     *
     * A new or updated subscription must be verified before events are delivered.
     *
     * @param  string  $subscriptionId  The GUID of the subscription to verify.
     */
    public function verify(string $subscriptionId): void;
}
