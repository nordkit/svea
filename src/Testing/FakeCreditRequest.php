<?php

declare(strict_types=1);

namespace Svea\Testing;

use Svea\Admin\AdminOrderRow;
use Svea\Admin\CreditRequest;
use Svea\Admin\TaskResponse;

/**
 * Fake fluent credit (refund) builder for use in tests, returned by
 * {@see FakeAdminDeliveryRequest::credit()}.
 *
 * Mirrors the {@see CreditRequest} API:
 * - `rows(array $rowIds, ?array $rowCreditingOptions)` — specify row IDs to credit.
 * - `newRow(callable $callback)` — add a virtual credit row.
 * - `send()` — records an `admin.credit` call and returns a pending
 *   {@see TaskResponse}.
 *
 * All operations accumulate state and are recorded together when `send()` is called,
 * consistent with the real `CreditRequest` implementation.
 *
 * @see CreditRequest  Real implementation
 */
class FakeCreditRequest
{
    /** @var int[] */
    private array $rowIds = [];

    /** @var array<int, array{orderRowId: int, quantity: int}>|null */
    private ?array $rowCreditingOptions = null;

    /** @var array<int, array<string, mixed>> */
    private array $newRows = [];

    /**
     * @param  SveaFakeAssertions  $assertions  Shared call recorder and response store.
     * @param  string  $orderId  The parent Svea order identifier.
     * @param  int  $deliveryId  The delivery ID this credit is scoped to.
     */
    public function __construct(
        private readonly SveaFakeAssertions $assertions,
        private readonly string $orderId,
        private readonly int $deliveryId,
    ) {}

    /**
     * Set the order row IDs to credit, with optional per-row quantity options.
     *
     * @param  int[]  $rowIds  The order row IDs to credit.
     * @param  array<int, array{orderRowId: int, quantity: int}>|null  $rowCreditingOptions  Optional partial-quantity options per row.
     */
    public function rows(array $rowIds, ?array $rowCreditingOptions = null): static
    {
        $this->rowIds = $rowIds;
        $this->rowCreditingOptions = $rowCreditingOptions;

        return $this;
    }

    /**
     * Add a virtual new credit row (e.g. a return fee or shipping credit).
     *
     * @param  callable(AdminOrderRow): void  $callback  Populates the new credit row payload.
     */
    public function newRow(callable $callback): static
    {
        $row = new AdminOrderRow;
        $callback($row);
        $this->newRows[] = $row->toArray();

        return $this;
    }

    /**
     * Record an `admin.credit` call with the accumulated state and return a seeded
     * or default pending task response.
     *
     * All calls to `rows()` and `newRow()` are bundled into a single recorded call,
     * consistent with the real {@see CreditRequest::send()} behaviour.
     */
    public function send(): TaskResponse
    {
        $this->assertions->recordCall('admin.credit', [
            $this->orderId,
            $this->deliveryId,
            $this->rowIds,
            $this->rowCreditingOptions,
            $this->newRows,
        ]);
        if ($this->assertions->hasFakeFor('admin.credit')) {
            return $this->assertions->fakeFor('admin.credit');
        }

        return TaskResponse::pending('https://paymentadminapi.svea.com/api/v1/tasks/fake');
    }
}
