<?php

declare(strict_types=1);

namespace Svea\Checkout;

use Svea\SveaResource;

/**
 * Typed response object for Svea Checkout API calls (create, get, update).
 *
 * Extends {@see SveaResource} — all raw fields are accessible via
 * magic properties (`$response->OrderId`) or array notation (`$response['OrderId']`).
 *
 * Named getters are provided for the most commonly used fields:
 * - `id()` — the Svea order ID to store and reference later
 * - `snippet()` — the HTML/JS snippet to embed in the checkout page
 * - `status()` — current order status string
 * - `successful()` — true when the status is `Created` or `Final`
 *
 * Raw HTTP response is always accessible via `getLastResponse()`.
 */
class CheckoutResponse extends SveaResource
{
    /**
     * Return the Svea order ID.
     *
     * Store this value and use it for subsequent get(), update(), and admin() calls.
     */
    public function id(): string
    {
        return (string) ($this->data['OrderId'] ?? '');
    }

    /**
     * Return the Gui snippet — the HTML/JS to embed in the checkout page.
     *
     * Rendered in an iframe or directly in the page to display the Svea
     * payment form to the customer.
     */
    public function snippet(): string
    {
        return (string) ($this->data['Gui']['Snippet'] ?? '');
    }

    /**
     * Return the current order status string as returned by Svea.
     *
     * Common values: `Created`, `Cancelled`, `Final`.
     */
    public function status(): string
    {
        return (string) ($this->data['Status'] ?? '');
    }

    /**
     * Return true when the order status indicates a live, payable order.
     *
     * `Created` — order is active and awaiting payment.
     * `Final`   — order has been paid and delivered.
     */
    public function successful(): bool
    {
        return in_array($this->status(), ['Created', 'Final'], true);
    }
}
