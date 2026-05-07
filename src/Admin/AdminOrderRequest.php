<?php

declare(strict_types=1);

namespace Svea\Admin;

use Svea\Exceptions\SveaApiException;
use Svea\Exceptions\SveaAuthenticationException;
use Svea\Exceptions\SveaNotFoundException;
use Svea\Support\Conditionable;
use Svea\Transport\SveaConnector;

/**
 * Fluent request builder scoped to a specific Svea Admin order.
 *
 * Obtained via {@see AdminService::order()} — never constructed directly.
 *
 * Supports the full order lifecycle:
 * - `get()` — fetch current order details
 * - `deliver()` — capture the order (all rows or a subset)
 * - `cancel()` / `cancelAmount()` / `cancelRow()` — cancel the order or parts of it
 * - `delivery()` — access a specific delivery for credit operations
 * - `addOrderRow()` / `updateOrderRow()` / `replaceOrderRows()` — modify order rows
 * - `withIdempotencyKey()` — attach an idempotency key to prevent duplicate captures
 *
 * Supports inline conditional branching via the {@see Conditionable} trait:
 * ```php
 * Svea::admin()->order('12345678')
 *     ->withIdempotencyKey('capture-' . $eventId)
 *     ->when($isPartial, fn ($o) => $o->deliver(rows: $rowIds))
 *     ->unless($isPartial, fn ($o) => $o->deliver());
 * ```
 *
 * @see AdminService::order()
 */
class AdminOrderRequest
{
    use Conditionable;

    private ?string $idempotencyKey = null;

    /**
     * @param  SveaConnector  $connector  Transport layer for this API surface.
     * @param  string  $orderId  The Svea order identifier.
     */
    public function __construct(
        private readonly SveaConnector $connector,
        private readonly string $orderId,
    ) {}

    /**
     * Attach an idempotency key to the next mutating request.
     *
     * Prevents duplicate captures or credits when a job is retried.
     * Sent as the `Idempotency-Key` header on `deliver()` calls.
     *
     * @param  string  $key  A unique identifier for this operation (e.g. `capture-{paymentId}`).
     */
    public function withIdempotencyKey(string $key): static
    {
        $this->idempotencyKey = $key;

        return $this;
    }

    /**
     * Fetch the current order details from the Svea Admin API.
     *
     * @throws SveaNotFoundException If the order does not exist.
     * @throws SveaAuthenticationException On invalid credentials.
     */
    public function get(): AdminOrderResponse
    {
        $response = $this->connector->get("api/v1/orders/{$this->orderId}");

        return AdminOrderResponse::make($response->json)->withLastResponse($response);
    }

    /**
     * Deliver (capture) the order, optionally limiting to specific row IDs.
     *
     * Svea responds with HTTP 202. The response carries a `DeliveryId` in the
     * body and an async task reference for polling completion status.
     *
     * @param  int[]  $rows  Specific row IDs to deliver (empty = all rows).
     * @param  array<int, array{orderRowId: int, quantity: int}>|null  $rowDeliveryOptions  Per-row partial delivery options.
     *
     * @throws SveaApiException On any non-2xx response.
     */
    public function deliver(array $rows = [], ?array $rowDeliveryOptions = null): DeliverResponse
    {
        $payload = ['OrderRowIds' => $rows];
        if ($rowDeliveryOptions !== null && $rowDeliveryOptions !== []) {
            $payload['RowDeliveryOptions'] = array_map(
                fn (array $option): array => [
                    'OrderRowId' => (int) $option['orderRowId'],
                    'Quantity' => (int) $option['quantity'],
                ],
                $rowDeliveryOptions
            );
        }
        $response = $this->connector->post(
            "api/v1/orders/{$this->orderId}/deliveries",
            $payload,
            $this->idempotencyKey
        );

        $taskReference = $response->json['Reference']
            ?? ($response->headers['Location'][0] ?? '');

        $data = [];

        if (isset($response->json['DeliveryId'])) {
            $data['DeliveryId'] = $response->json['DeliveryId'];
        }

        if ($taskReference !== '') {
            $data['TaskReference'] = $taskReference;
        }

        return DeliverResponse::make($data)->withLastResponse($response);
    }

    /**
     * Cancel the entire order.
     *
     * @throws SveaApiException On any non-2xx response.
     */
    public function cancel(): void
    {
        $this->connector->patch("api/v1/orders/{$this->orderId}", ['IsCancelled' => true]);
    }

    /**
     * Cancel a specific amount on the order.
     *
     * @param  int  $amount  Amount to cancel in minor currency units (e.g. öre).
     *
     * @throws SveaApiException On any non-2xx response.
     */
    public function cancelAmount(int $amount): void
    {
        $this->connector->patch("api/v1/orders/{$this->orderId}", ['CancelledAmount' => $amount]);
    }

    /**
     * Cancel a specific order row by its row ID.
     *
     * @param  int  $rowId  The ID of the row to cancel.
     *
     * @throws SveaApiException On any non-2xx response.
     */
    public function cancelRow(int $rowId): void
    {
        $this->connector->patch(
            "api/v1/orders/{$this->orderId}/rows/cancelOrderRows/",
            ['OrderRowIds' => [$rowId]]
        );
    }

    /**
     * Return a delivery-scoped request builder for credit operations.
     *
     * @param  int  $deliveryId  The ID of the delivery to operate on.
     */
    public function delivery(int $deliveryId): AdminDeliveryRequest
    {
        return new AdminDeliveryRequest($this->connector, $this->orderId, $deliveryId);
    }

    /**
     * Add a new order row to the order.
     *
     * The callback receives an {@see AdminOrderRow} builder to populate.
     *
     * @param  callable(AdminOrderRow): void  $callback
     * @return array{order_row_id: int, task_reference: string}
     *
     * @throws SveaApiException On any non-2xx response.
     */
    public function addOrderRow(callable $callback): array
    {
        $orderRow = AdminOrderRow::make();
        $callback($orderRow);

        $response = $this->connector->post(
            "api/v1/orders/{$this->orderId}/rows",
            ['OrderRow' => $orderRow->toArray()]
        );

        return [
            'order_row_id' => (int) ($response->json['OrderRowId'] ?? 0),
            'task_reference' => (string) ($response->headers['Location'][0] ?? ''),
        ];
    }

    /**
     * Update an existing order row by its row ID.
     *
     * The callback receives an {@see AdminOrderRow} builder pre-configured
     * with the current row — set only the fields you want to change.
     *
     * @param  int  $rowId  The ID of the row to update.
     * @param  callable(AdminOrderRow): void  $callback
     *
     * @throws SveaApiException On any non-2xx response.
     */
    public function updateOrderRow(int $rowId, callable $callback): void
    {
        $orderRow = AdminOrderRow::make();
        $callback($orderRow);

        $this->connector->put(
            "api/v1/orders/{$this->orderId}/rows/{$rowId}",
            ['OrderRow' => $orderRow->toArray()]
        );
    }

    /**
     * Replace all order rows with the provided set.
     *
     * Each callback receives a fresh {@see AdminOrderRow} builder.
     * All existing rows are removed and replaced with the ones built here.
     *
     * @param  callable(AdminOrderRow): void  ...$callbacks  One callback per replacement row.
     *
     * @throws SveaApiException On any non-2xx response.
     */
    public function replaceOrderRows(callable ...$callbacks): void
    {
        $rows = [];

        foreach ($callbacks as $callback) {
            $orderRow = AdminOrderRow::make();
            $callback($orderRow);
            $rows[] = $orderRow->toArray();
        }

        $this->connector->put(
            "api/v1/orders/{$this->orderId}/rows",
            ['OrderRows' => $rows]
        );
    }
}
