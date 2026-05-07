<?php

declare(strict_types=1);

namespace Svea\Testing;

use Svea\Contracts\SubscriptionServiceInterface;
use Svea\Subscriptions\EventType;
use Svea\Subscriptions\Subscription;

/**
 * In-memory fake for SubscriptionService, used in tests via Svea::fake().
 *
 * Mirrors the full SubscriptionService API — including the fluent builder
 * chain (on → notifyAt → register) and all direct methods (add, get, list,
 * update, remove, verify). All calls are recorded for assertion with
 * SveaFakeAssertions.
 *
 * Pre-seed responses using the fake key map passed to FakeSveaClient:
 *   Svea::fake(['subscriptions.register' => Subscription::make([...])]);
 *
 * Then assert after running code under test:
 *   Svea::assertSubscriptionRegistered('https://myapp.com/webhooks/svea');
 */
class FakeSubscriptionService implements SubscriptionServiceInterface
{
    /** @var EventType[] */
    private array $pendingEventTypes = [];

    private string $pendingCallbackUrl = '';

    public function __construct(private readonly SveaFakeAssertions $assertions) {}

    // -------------------------------------------------------------------------
    // Fluent builder chain: on() → notifyAt() → register()
    // -------------------------------------------------------------------------

    /**
     * Begin a fluent subscription builder by specifying the event types to
     * subscribe to. Must be followed by notifyAt() and register().
     *
     * @param  EventType  ...$types  One or more event types to listen for.
     */
    public function on(EventType ...$types): static
    {
        $this->pendingEventTypes = $types;

        return $this;
    }

    /**
     * Set the callback URL for the pending subscription.
     *
     * @param  string  $url  The HTTPS URL Svea will POST events to.
     */
    public function notifyAt(string $url): static
    {
        $this->pendingCallbackUrl = $url;

        return $this;
    }

    /**
     * Complete the fluent builder and register the subscription.
     *
     * Records a 'subscriptions.register' call for assertSubscriptionRegistered().
     * Returns a pre-seeded fake if one is configured, otherwise returns a
     * default stub Subscription.
     */
    public function register(): Subscription
    {
        $this->assertions->recordCall('subscriptions.register', [$this->pendingCallbackUrl, $this->pendingEventTypes]);

        if ($this->assertions->hasFakeFor('subscriptions.register')) {
            return $this->assertions->fakeFor('subscriptions.register');
        }

        return Subscription::make([
            'SubscriptionId' => 'fake-sub-id',
            'CallbackUri' => $this->pendingCallbackUrl,
            'Events' => array_map(fn (EventType $t) => $t->value, $this->pendingEventTypes),
            'Verified' => false,
        ]);
    }

    // -------------------------------------------------------------------------
    // Direct methods
    // -------------------------------------------------------------------------

    /**
     * Add a new webhook subscription directly (non-fluent).
     *
     * Records a 'subscriptions.add' call.
     *
     * @param  string  $callbackUrl  The HTTPS URL Svea will POST events to.
     * @param  EventType[]  $eventTypes  The events to subscribe to.
     */
    public function add(string $callbackUrl, array $eventTypes): Subscription
    {
        $this->assertions->recordCall('subscriptions.add', [$callbackUrl, $eventTypes]);

        if ($this->assertions->hasFakeFor('subscriptions.add')) {
            return $this->assertions->fakeFor('subscriptions.add');
        }

        return Subscription::make([
            'SubscriptionId' => 'fake-sub-id',
            'CallbackUri' => $callbackUrl,
            'Events' => array_map(fn (EventType $t) => $t->value, $eventTypes),
            'Verified' => false,
        ]);
    }

    /**
     * Retrieve a single subscription by its ID.
     *
     * Records a 'subscriptions.get' call.
     *
     * @param  string  $subscriptionId  The GUID of the subscription.
     */
    public function get(string $subscriptionId): Subscription
    {
        $this->assertions->recordCall('subscriptions.get', [$subscriptionId]);

        if ($this->assertions->hasFakeFor('subscriptions.get')) {
            return $this->assertions->fakeFor('subscriptions.get');
        }

        return Subscription::make([
            'SubscriptionId' => $subscriptionId,
            'CallbackUri' => 'https://example.com/webhooks',
            'Events' => [],
            'Verified' => false,
        ]);
    }

    /**
     * List all registered webhook subscriptions.
     *
     * Records a 'subscriptions.list' call.
     *
     * @return array<int, Subscription>
     */
    public function list(): array
    {
        $this->assertions->recordCall('subscriptions.list');

        if ($this->assertions->hasFakeFor('subscriptions.list')) {
            return $this->assertions->fakeFor('subscriptions.list');
        }

        return [];
    }

    /**
     * Update an existing subscription by replacing its URL and event types.
     *
     * Records a 'subscriptions.update' call.
     *
     * @param  string  $subscriptionId  The GUID of the subscription to update.
     * @param  string  $callbackUrl  The new callback URL.
     * @param  EventType[]  $eventTypes  The new set of event types.
     */
    public function update(string $subscriptionId, string $callbackUrl, array $eventTypes): Subscription
    {
        $this->assertions->recordCall('subscriptions.update', [$subscriptionId, $callbackUrl, $eventTypes]);

        if ($this->assertions->hasFakeFor('subscriptions.update')) {
            return $this->assertions->fakeFor('subscriptions.update');
        }

        return Subscription::make([
            'SubscriptionId' => $subscriptionId,
            'CallbackUri' => $callbackUrl,
            'Events' => array_map(fn (EventType $t) => $t->value, $eventTypes),
            'Verified' => false,
        ]);
    }

    /**
     * Remove a registered subscription by ID.
     *
     * Records a 'subscriptions.remove' call.
     *
     * @param  string  $subscriptionId  The GUID of the subscription to delete.
     */
    public function remove(string $subscriptionId): void
    {
        $this->assertions->recordCall('subscriptions.remove', [$subscriptionId]);
    }

    /**
     * Trigger Svea to send a Ping event to the subscription's callback URL,
     * verifying that the endpoint is reachable and handling events correctly.
     *
     * Records a 'subscriptions.verify' call.
     *
     * @param  string  $subscriptionId  The GUID of the subscription to verify.
     */
    public function verify(string $subscriptionId): void
    {
        $this->assertions->recordCall('subscriptions.verify', [$subscriptionId]);
    }
}
