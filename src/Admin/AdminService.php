<?php

declare(strict_types=1);

namespace Svea\Admin;

use Svea\Contracts\AdminServiceInterface;
use Svea\SveaClient;
use Svea\Transport\SveaConnector;

/**
 * Svea Payment Admin API service.
 *
 * Entry point for all order management operations: deliver, cancel, credit,
 * modify rows, and poll async tasks.
 *
 * Obtained via {@see SveaClient::admin()} or the `Svea::admin()` facade.
 *
 * ```php
 * // Deliver an order
 * $deliver = Svea::admin()->order('12345678')->deliver();
 *
 * // Poll an async task
 * $task = Svea::admin()->task($deliver->taskReference());
 * $task->completed(); // bool
 * ```
 *
 * @see AdminOrderRequest
 * @see TaskResponse
 */
class AdminService implements AdminServiceInterface
{
    public function __construct(private readonly SveaConnector $connector) {}

    /**
     * Return a fluent order request builder scoped to the given order ID.
     *
     * @param  string  $orderId  The Svea order identifier.
     */
    public function order(string $orderId): AdminOrderRequest
    {
        return new AdminOrderRequest($this->connector, $orderId);
    }

    /**
     * Poll a pending async task by its reference URL.
     *
     * Svea signals completion with HTTP 303 See Other, redirecting to the resource
     * URL. A 200 response with `Status=InProgress` means the task is still pending.
     *
     * @param  string  $taskUrl  The full task URL returned by a previous deliver/credit call.
     */
    public function task(string $taskUrl): TaskResponse
    {
        $path = parse_url($taskUrl, PHP_URL_PATH) ?? $taskUrl;
        $response = $this->connector->get(ltrim((string) $path, '/'));

        if ($response->statusCode === 303) {
            $resourceUrl = $response->headers['Location'][0] ?? '';

            return TaskResponse::make(['Status' => 'Completed', 'Resource' => $resourceUrl]);
        }

        return TaskResponse::make($response->json)->withLastResponse($response);
    }
}
