<?php

declare(strict_types=1);

namespace Svea\Webhooks;

use Svea\Subscriptions\EventType;
use Svea\SveaResource;

/**
 * Typed representation of an inbound Svea subscription webhook event.
 *
 * Constructed by {@see Webhook::constructEvent()} or {@see WebhookService::fromRequest()}
 * after a successful signature verification.
 *
 * Svea webhook payload shape:
 * ```json
 * {
 *   "EventType": "CheckoutOrder.Delivered",
 *   "OrderId":   "3fa85f64-5717-4562-b3fc-2c963f66afa6",
 *   "DeliveryId": "d1e2f3a4-...",
 *   "Timestamp":  "2026-04-23T12:00:00Z"
 * }
 * ```
 *
 * The full raw payload is always accessible via `payload()` or through
 * the {@see SveaResource} magic property / array accessors.
 *
 * @see Webhook::constructEvent()
 * @see WebhookService::fromRequest()
 */
class WebhookEvent extends SveaResource
{
    /**
     * Return the event type as a typed enum case.
     *
     * Returns null if the event type string is not a known {@see EventType} value
     * (e.g. a future event type added by Svea — handle gracefully).
     */
    public function type(): ?EventType
    {
        return EventType::tryFrom((string) ($this->data['Event'] ?? $this->data['EventType'] ?? ''));
    }

    /**
     * Return the Svea checkout order ID associated with this event.
     */
    public function orderId(): string
    {
        return (string) ($this->data['OrderId'] ?? '');
    }

    /**
     * Return the delivery ID for events that involve a delivery, or null if absent.
     *
     * Present on `CheckoutOrder.Delivered` and credit events.
     */
    public function deliveryId(): ?string
    {
        $value = $this->data['DeliveryId'] ?? null;

        return $value !== null ? (string) $value : null;
    }

    /**
     * Return the UTC timestamp when this event was raised by Svea.
     *
     * Maps to the `Timestamp` field. Returns null if the field is not present.
     */
    public function occurredAt(): ?\DateTimeImmutable
    {
        $value = $this->data['Timestamp'] ?? null;

        return $value !== null ? new \DateTimeImmutable((string) $value) : null;
    }

    /**
     * Return the full raw event payload as an array.
     *
     * Useful when accessing fields not covered by the named getters above,
     * or when logging the complete event for debugging.
     *
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return $this->data;
    }
}
