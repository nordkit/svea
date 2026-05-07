<?php

declare(strict_types=1);

namespace Svea\Testing;

use Svea\Admin\AdminDeliveryRequest;
use Svea\Admin\TaskResponse;

/**
 * Fake delivery-scoped request for use in tests, returned by
 * {@see FakeAdminOrderRequest::delivery()}.
 *
 * Mirrors the {@see AdminDeliveryRequest} API:
 * - `credit()` — returns a {@see FakeCreditRequest} fluent builder.
 * - `creditAmount(int $amount)` — records an `admin.creditAmount` call and
 *   returns a pending {@see TaskResponse}.
 *
 * @see AdminDeliveryRequest  Real implementation
 */
class FakeAdminDeliveryRequest
{
    /**
     * @param  SveaFakeAssertions  $assertions  Shared call recorder and response store.
     * @param  string  $orderId  The parent Svea order identifier.
     * @param  int  $deliveryId  The Svea delivery ID to operate on.
     */
    public function __construct(
        private readonly SveaFakeAssertions $assertions,
        private readonly string $orderId,
        private readonly int $deliveryId,
    ) {}

    /**
     * Return a fake credit request builder scoped to this delivery.
     *
     * Chain `->rows()`/`->newRow()` and call `->send()` to record the credit.
     */
    public function credit(): FakeCreditRequest
    {
        return new FakeCreditRequest($this->assertions, $this->orderId, $this->deliveryId);
    }

    /**
     * Record an `admin.creditAmount` call and return a seeded or default pending task.
     *
     * @param  int  $amount  The fixed amount to credit in minor currency units (e.g. öre).
     */
    public function creditAmount(int $amount): TaskResponse
    {
        $this->assertions->recordCall('admin.creditAmount', [$this->orderId, $this->deliveryId, $amount]);
        if ($this->assertions->hasFakeFor('admin.creditAmount')) {
            return $this->assertions->fakeFor('admin.creditAmount');
        }

        return TaskResponse::pending('https://paymentadminapi.svea.com/api/v1/tasks/fake');
    }
}
