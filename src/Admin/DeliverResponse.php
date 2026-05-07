<?php

declare(strict_types=1);

namespace Svea\Admin;

use Svea\SveaResource;

/**
 * Typed response for POST /api/v1/orders/{orderId}/deliveries (HTTP 202).
 *
 * Svea responds immediately with:
 * - `DeliveryId` in the response body — the created delivery's identifier
 * - Task reference URL — an async task to poll for delivery completion
 *
 * Both values may be null if Svea processed the delivery synchronously without
 * returning a task or delivery ID.
 *
 * @see AdminOrderRequest::deliver()
 * @see AdminService::task()
 */
class DeliverResponse extends SveaResource
{
    /**
     * Return the ID of the created delivery.
     *
     * Available immediately in the 202 response body.
     * Store this to reference the delivery in subsequent credit operations.
     */
    public function deliveryId(): ?int
    {
        $id = $this->data['DeliveryId'] ?? null;

        return $id !== null ? (int) $id : null;
    }

    /**
     * Return the async task reference URL to poll for delivery completion.
     *
     * Null if Svea completed the delivery synchronously (no task was created).
     * Pass to {@see AdminService::task()} to check the completion status.
     */
    public function taskReference(): ?string
    {
        $ref = $this->data['TaskReference'] ?? null;

        return ($ref !== null && $ref !== '') ? (string) $ref : null;
    }
}
