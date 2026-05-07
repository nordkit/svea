<?php

declare(strict_types=1);

namespace Svea\Admin;

use Svea\SveaResource;

/**
 * Typed response for GET /api/v1/orders/{orderId} from the Svea Admin API.
 *
 * Exposes the order's current status, available actions, and delivery details.
 * Use the `can*()` helpers to gate operations before attempting them:
 *
 * ```php
 * $order = Svea::admin()->order('12345678')->get();
 *
 * if ($order->canDeliver()) {
 *     Svea::admin()->order('12345678')->deliver();
 * }
 * ```
 *
 * All raw fields are also accessible via magic properties or array notation
 * through the {@see SveaResource} base class.
 */
class AdminOrderResponse extends SveaResource
{
    /**
     * Return the current order status as a typed enum.
     */
    public function status(): SveaOrderStatus
    {
        $value = $this->data['OrderStatus'] ?? 'Open';

        return SveaOrderStatus::from((string) $value);
    }

    /**
     * Return the list of available actions for this order.
     *
     * Common values: `CanDeliverOrder`, `CanCreditOrder`, `CanCancelOrder`.
     *
     * @return string[]
     */
    public function actions(): array
    {
        return (array) ($this->data['Actions'] ?? []);
    }

    /**
     * Return true if the order can be delivered (captured).
     */
    public function canDeliver(): bool
    {
        return in_array('CanDeliverOrder', $this->actions(), true);
    }

    /**
     * Return true if the order can be credited (refunded).
     */
    public function canCredit(): bool
    {
        return in_array('CanCreditOrder', $this->actions(), true);
    }

    /**
     * Return true if the order can be cancelled.
     */
    public function canCancel(): bool
    {
        return in_array('CanCancelOrder', $this->actions(), true);
    }

    /**
     * Return all deliveries on this order.
     *
     * @return array<int, array<string, mixed>>
     */
    public function deliveries(): array
    {
        return (array) ($this->data['Deliveries'] ?? []);
    }

    /**
     * Find a specific delivery by its ID.
     *
     * @param  int  $deliveryId  The delivery identifier.
     * @return array<string, mixed>|null The delivery data, or null if not found.
     */
    public function delivery(int $deliveryId): ?array
    {
        foreach ($this->deliveries() as $delivery) {
            if ((int) ($delivery['DeliveryId'] ?? $delivery['Id'] ?? 0) === $deliveryId) {
                return $delivery;
            }
        }

        return null;
    }

    /**
     * Return the order row IDs belonging to a specific delivery.
     *
     * Useful when crediting individual rows on a delivered order.
     *
     * @param  int  $deliveryId  The delivery identifier.
     * @return int[]
     */
    public function deliveryRowIds(int $deliveryId): array
    {
        $delivery = $this->delivery($deliveryId);

        if ($delivery === null) {
            return [];
        }

        return array_map(
            fn (array $row): int => (int) ($row['OrderRowId'] ?? 0),
            (array) ($delivery['OrderRows'] ?? [])
        );
    }

    /**
     * Return true if the given action string is available on this order.
     *
     * @param  string  $action  Action name, e.g. `CanDeliverOrder`.
     */
    public function hasAction(string $action): bool
    {
        return in_array($action, $this->actions(), true);
    }

    /**
     * Return true if the order has the given status string.
     *
     * @param  string  $status  Status string, e.g. `Open`, `Delivered`.
     */
    public function hasStatus(string $status): bool
    {
        return $this->status()->value === $status;
    }
}
