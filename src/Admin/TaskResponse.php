<?php

declare(strict_types=1);

namespace Svea\Admin;

use Svea\SveaResource;

/**
 * Typed response object for async Svea Admin API operations.
 *
 * Svea returns HTTP 202 for deliver and credit operations, signalling that the
 * action has been accepted but not yet completed. The `TaskResponse` carries:
 * - `reference()` — the URL to poll until the task completes
 * - `resource()` — the URL of the finalised resource (available once completed)
 * - `completed()` / `failed()` — convenience status checks
 *
 * Typical polling pattern:
 * ```php
 * $task = Svea::admin()->order('12345678')->deliver();
 *
 * // Poll until done (in practice: schedule a delayed job)
 * $status = Svea::admin()->task($task->taskReference());
 * if ($status->completed()) {
 *     // delivery confirmed
 * }
 * ```
 *
 * @see AdminService::task()
 */
class TaskResponse extends SveaResource
{
    /**
     * Return the async task reference URL to poll for completion.
     *
     * Pass this to {@see AdminService::task()} to check the current status.
     */
    public function reference(): ?string
    {
        return $this->data['Reference'] ?? null;
    }

    /**
     * Return the URL of the finalised resource once the task has completed.
     *
     * Null while the task is still pending or if no resource URL was returned.
     */
    public function resource(): ?string
    {
        return $this->data['Resource'] ?? null;
    }

    /**
     * Return true if the async task has completed successfully.
     */
    public function completed(): bool
    {
        return isset($this->data['Status']) && $this->data['Status'] === 'Completed';
    }

    /**
     * Return true if the async task has failed.
     */
    public function failed(): bool
    {
        return isset($this->data['Status']) && $this->data['Status'] === 'Failed';
    }

    /**
     * Named constructor for a pending task from a 202 response.
     *
     * @param  string  $reference  The task reference URL from the `Location` header or response body.
     */
    public static function pending(string $reference): static
    {
        return static::make(['Reference' => $reference]);
    }
}
