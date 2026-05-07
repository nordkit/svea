<?php

declare(strict_types=1);

namespace Svea\Testing;

use Svea\Admin\AdminService;
use Svea\Admin\TaskResponse;
use Svea\Contracts\AdminServiceInterface;

/**
 * In-memory fake for AdminService, used via FakeSveaClient in tests.
 *
 * Mirrors the full {@see AdminService} API:
 * - `order(string $orderId)` — returns a {@see FakeAdminOrderRequest} that
 *   records all fluent operations (get, deliver, cancel, cancelAmount,
 *   cancelRow, delivery, addOrderRow, updateOrderRow, replaceOrderRows).
 * - `task(string $taskUrl)` — records the poll call and returns a seeded or
 *   default completed {@see TaskResponse}.
 *
 * Pre-seed responses via the `FakeSveaClient` constructor or `Svea::fake()`:
 * ```php
 * Svea::fake(['admin.task' => TaskResponse::pending('https://paymentadminapi.svea.com/api/v1/tasks/123')]);
 * ```
 *
 * @see FakeAdminOrderRequest  Fluent builder used for all order operations
 * @see AdminServiceInterface  Real service contract
 */
class FakeAdminService implements AdminServiceInterface
{
    /**
     * @param  SveaFakeAssertions  $assertions  Shared call recorder and response store.
     */
    public function __construct(private readonly SveaFakeAssertions $assertions) {}

    /**
     * Return a fake order request builder for the given order ID.
     *
     * All fluent operations (`get`, `deliver`, `cancel`, `delivery`, etc.) are
     * recorded through the shared {@see SveaFakeAssertions} instance.
     *
     * @param  string  $orderId  The Svea order identifier.
     */
    public function order(string $orderId): FakeAdminOrderRequest
    {
        return new FakeAdminOrderRequest($this->assertions, $orderId);
    }

    /**
     * Record an `admin.task` call and return a seeded or default response.
     *
     * @param  string  $taskUrl  The full task URL returned by a deliver/credit operation.
     */
    public function task(string $taskUrl): TaskResponse
    {
        $this->assertions->recordCall('admin.task', [$taskUrl]);
        if ($this->assertions->hasFakeFor('admin.task')) {
            return $this->assertions->fakeFor('admin.task');
        }

        return TaskResponse::make(['Status' => 'Completed']);
    }
}
