<?php

declare(strict_types=1);

namespace Svea\Subscriptions;

use Svea\SveaResource;

/**
 * A registered Svea webhook subscription.
 *
 * Returned by SubscriptionService::add(), get(), list(), update(), and the
 * SubscriptionBuilder::register() fluent chain.
 *
 * API response shape (from Svea docs):
 *
 * @see https://docs.payments.svea.com/docs/manage-order/callbacks/get_subscriptions
 *
 * Fields:
 *   - SubscriptionId (GUID, string) — unique identifier assigned by Svea
 *   - CallbackUri    (string)       — the registered HTTPS webhook URL
 *   - Events         (string[])     — list of event type strings (see EventType enum)
 *   - Verified       (bool)         — true once the URL has been confirmed via verify()
 *   - Created        (string|null)  — ISO-8601 creation timestamp (not present in all responses)
 *
 * Example:
 *   $subscription->id();           // 'fbb6c74a-cc06-4ab7-100e-08daa861c517'
 *   $subscription->callbackUrl();  // 'https://myapp.com/webhooks/svea'
 *   $subscription->events();       // [EventType::CheckoutOrderCreated, ...]
 *   $subscription->isVerified();   // true
 *   $subscription->createdAt();    // \DateTimeImmutable|null
 */
class Subscription extends SveaResource
{
    /**
     * The unique GUID identifier assigned to this subscription by Svea.
     *
     * Maps to the 'SubscriptionId' field in the API response.
     */
    public function id(): string
    {
        return (string) ($this->data['SubscriptionId'] ?? '');
    }

    /**
     * The HTTPS URL that Svea POSTs webhook event payloads to.
     *
     * Maps to the 'CallbackUri' field in the API response.
     */
    public function callbackUrl(): string
    {
        return (string) ($this->data['CallbackUri'] ?? '');
    }

    /**
     * The list of event types this subscription is listening for.
     *
     * Maps to the 'Events' field in the API response (array of event name strings).
     * Each string is resolved to its corresponding EventType enum case.
     *
     * @return EventType[]
     */
    public function events(): array
    {
        return array_map(
            fn (string $type) => EventType::from($type),
            (array) ($this->data['Events'] ?? [])
        );
    }

    /**
     * Whether the callback URL has been successfully verified by Svea.
     *
     * A subscription must be verified before Svea will deliver events to it.
     * Verification is triggered via SubscriptionService::verify(), which sends
     * a Ping event to the registered CallbackUri.
     *
     * Maps to the 'Verified' field in the API response.
     */
    public function isVerified(): bool
    {
        return (bool) ($this->data['Verified'] ?? false);
    }

    /**
     * The UTC timestamp when this subscription was created.
     *
     * Maps to the 'Created' field in the API response. Note: this field is
     * not present in all API responses (e.g. the add endpoint returns only
     * SubscriptionId). Returns null when the field is absent.
     */
    public function createdAt(): ?\DateTimeImmutable
    {
        $value = $this->data['Created'] ?? null;

        return $value !== null ? new \DateTimeImmutable((string) $value) : null;
    }
}
