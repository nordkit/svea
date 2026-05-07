<?php

declare(strict_types=1);

namespace Svea\Contracts;

use Svea\Admin\AdminService;
use Svea\Admin\TaskResponse;
use Svea\Testing\FakeAdminService;

/**
 * Contract for the Svea Payment Admin API service.
 *
 * Implemented by {@see AdminService} for real API calls and by
 * {@see FakeAdminService} for in-memory test doubles.
 *
 * Type-hint against this interface in application code to allow seamless
 * swapping between the real service and the fake in tests.
 */
interface AdminServiceInterface
{
    /**
     * Return a fluent order request builder for the given Svea order ID.
     *
     * The returned builder supports get(), deliver(), cancel(), cancelAmount(),
     * cancelRow(), delivery(), addOrderRow(), updateOrderRow(), replaceOrderRows(),
     * and withIdempotencyKey().
     *
     * @param  string  $orderId  The Svea order identifier.
     */
    public function order(string $orderId): mixed;

    /**
     * Poll a pending async task by its reference URL.
     *
     * Svea signals completion with HTTP 303 See Other, redirecting to the
     * resource URL. A 200 with Status=InProgress means the task is still pending.
     *
     * @param  string  $taskUrl  The full task URL returned by a previous deliver/credit call.
     * @return TaskResponse The current task status.
     */
    public function task(string $taskUrl): TaskResponse;
}
