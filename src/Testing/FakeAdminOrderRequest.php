<?php

declare(strict_types=1);

namespace Svea\Testing;

use Svea\Admin\AdminOrderRequest;
use Svea\Admin\AdminOrderResponse;
use Svea\Admin\AdminOrderRow;
use Svea\Admin\DeliverResponse;
use Svea\Support\Conditionable;

/**
 * Fake fluent builder for admin order operations, used by {@see FakeAdminService}.
 *
 * Mirrors the full {@see AdminOrderRequest} API and records every
 * operation call so that {@see SveaFakeAssertions} domain methods work correctly:
 * `assertDelivered()`, `assertCancelledOrder()`, `assertCredited()`, etc.
 *
 * **Supported call keys (for seeding / generic assertions):**
 * - `admin.get` — `get()` — returns {@see AdminOrderResponse}
 * - `admin.deliver` — `deliver()` — returns {@see DeliverResponse}
 * - `admin.cancel` — `cancel()`
 * - `admin.cancelAmount` — `cancelAmount(int $amount)`
 * - `admin.cancelRow` — `cancelRow(int $rowId)`
 * - `admin.addOrderRow` — `addOrderRow(callable)`
 * - `admin.updateOrderRow` — `updateOrderRow(int, callable)`
 * - `admin.replaceOrderRows` — `replaceOrderRows(callable ...)`
 * - `delivery(int $deliveryId)` — returns {@see FakeAdminDeliveryRequest}
 *
 * Supports `Conditionable` via `when()` / `unless()` for conditional chaining.
 *
 * @see AdminOrderRequest  Real implementation
 */
class FakeAdminOrderRequest
{
    use Conditionable;

    /** @phpstan-ignore property.onlyWritten (stored by withIdempotencyKey but not forwarded — no real HTTP in the fake) */
    private ?string $idempotencyKey = null;

    /**
     * @param  SveaFakeAssertions  $assertions  Shared call recorder and response store.
     * @param  string  $orderId  The Svea order identifier.
     */
    public function __construct(
        private readonly SveaFakeAssertions $assertions,
        private readonly string $orderId,
    ) {}

    /**
     * Store an idempotency key to be passed on the next mutating request.
     *
     * Mirrors {@see AdminOrderRequest::withIdempotencyKey()} — the key is held
     * in state but not sent anywhere in the fake (no real HTTP). Useful to verify
     * the key is set in call arguments if needed via `assertCalled`.
     *
     * @param  string  $key  The idempotency key (e.g. `'capture-' . $paymentEvent->id`).
     */
    public function withIdempotencyKey(string $key): static
    {
        $this->idempotencyKey = $key;

        return $this;
    }

    /**
     * Record an `admin.get` call and return a seeded or default order response.
     *
     * @return AdminOrderResponse Default stub has `Open` status with `CanDeliverOrder` and `CanCancelOrder` actions.
     */
    public function get(): AdminOrderResponse
    {
        $this->assertions->recordCall('admin.get', [$this->orderId]);
        if ($this->assertions->hasFakeFor('admin.get')) {
            return $this->assertions->fakeFor('admin.get');
        }

        return AdminOrderResponse::make(['OrderStatus' => 'Open', 'Actions' => ['CanDeliverOrder', 'CanCancelOrder']]);
    }

    /**
     * Record an `admin.deliver` call and return a seeded or default deliver response.
     *
     * @param  int[]  $rows  Row IDs to deliver. Empty means deliver all rows.
     * @param  array<int, array{orderRowId: int, quantity: int}>|null  $rowDeliveryOptions  Optional partial-quantity delivery options per row.
     *
     * @throws \RuntimeException When `preventStrayRequests()` is active and no fake is seeded.
     */
    public function deliver(array $rows = [], ?array $rowDeliveryOptions = null): DeliverResponse
    {
        $this->assertions->recordCall('admin.deliver', [$this->orderId, $rows, $rowDeliveryOptions]);
        if ($this->assertions->hasFakeFor('admin.deliver')) {
            return $this->assertions->fakeFor('admin.deliver');
        }
        if ($this->assertions->isPreventingStrayRequests()) {
            throw new \RuntimeException('Svea fake has no response seeded for [admin.deliver] and stray requests are prevented.');
        }

        return DeliverResponse::make([
            'DeliveryId' => 999999,
            'TaskReference' => 'https://paymentadminapi.svea.com/api/v1/queue/fake',
        ]);
    }

    /**
     * Record an `admin.cancel` call (cancel the entire order).
     */
    public function cancel(): void
    {
        $this->assertions->recordCall('admin.cancel', [$this->orderId]);
    }

    /**
     * Record an `admin.cancelAmount` call (partial order amount cancellation).
     *
     * @param  int  $amount  Amount to cancel in minor currency units (e.g. öre).
     */
    public function cancelAmount(int $amount): void
    {
        $this->assertions->recordCall('admin.cancelAmount', [$this->orderId, $amount]);
    }

    /**
     * Record an `admin.cancelRow` call (cancel a specific order row).
     *
     * @param  int  $rowId  The order row ID to cancel.
     */
    public function cancelRow(int $rowId): void
    {
        $this->assertions->recordCall('admin.cancelRow', [$this->orderId, $rowId]);
    }

    /**
     * Return a fake delivery request scoped to the given delivery ID.
     *
     * @param  int  $deliveryId  The Svea delivery ID (from a prior deliver response).
     */
    public function delivery(int $deliveryId): FakeAdminDeliveryRequest
    {
        return new FakeAdminDeliveryRequest($this->assertions, $this->orderId, $deliveryId);
    }

    /**
     * Record an `admin.addOrderRow` call and return a seeded or default row-add result.
     *
     * @param  callable(AdminOrderRow): void  $callback  Populates the new order row payload.
     * @return array{order_row_id: int, task_reference: string}
     */
    public function addOrderRow(callable $callback): array
    {
        $row = new AdminOrderRow;
        $callback($row);

        $this->assertions->recordCall('admin.addOrderRow', [$this->orderId, $row->toArray()]);

        if ($this->assertions->hasFakeFor('admin.addOrderRow')) {
            return $this->assertions->fakeFor('admin.addOrderRow');
        }

        return [
            'order_row_id' => 0,
            'task_reference' => 'https://paymentadminapi.svea.com/api/v1/tasks/fake',
        ];
    }

    /**
     * Record an `admin.updateOrderRow` call.
     *
     * @param  int  $rowId  The order row ID to update.
     * @param  callable(AdminOrderRow): void  $callback  Populates the updated row payload.
     */
    public function updateOrderRow(int $rowId, callable $callback): void
    {
        $row = new AdminOrderRow;
        $callback($row);

        $this->assertions->recordCall('admin.updateOrderRow', [$this->orderId, $rowId, $row->toArray()]);
    }

    /**
     * Record an `admin.replaceOrderRows` call.
     *
     * @param  callable(AdminOrderRow): void  ...$callbacks  One callable per replacement row.
     */
    public function replaceOrderRows(callable ...$callbacks): void
    {
        $rows = [];

        foreach ($callbacks as $callback) {
            $row = new AdminOrderRow;
            $callback($row);
            $rows[] = $row->toArray();
        }

        $this->assertions->recordCall('admin.replaceOrderRows', [$this->orderId, $rows]);
    }
}
