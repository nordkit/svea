<?php

declare(strict_types=1);

namespace Svea\Checkout;

use Svea\Contracts\CheckoutServiceInterface;
use Svea\Transport\SveaConnector;

/**
 * Svea Checkout API service.
 *
 * Implements the full checkout order lifecycle: create, get, update, and cancel.
 * All methods communicate with the Svea Checkout API via {@see SveaConnector}
 * and return typed {@see CheckoutResponse} objects.
 *
 * @see CheckoutServiceInterface
 */
class CheckoutService implements CheckoutServiceInterface
{
    public function __construct(private readonly SveaConnector $connector) {}

    /**
     * Create a new Svea Checkout order.
     *
     * Accepts either a pre-built {@see CheckoutOrder} or a callable that receives
     * a blank order to populate fluently:
     * ```php
     * $svea->checkout()->create(fn (CheckoutOrder $o) =>
     *     $o->currency('SEK')->countryCode('SE')->locale('sv-SE')
     *       ->clientOrderNumber('ORD-1')->merchantSettings($settings)
     *       ->cart($cart)
     * );
     * ```
     *
     * @param  callable(CheckoutOrder): void|CheckoutOrder  $checkout
     */
    public function create(callable|CheckoutOrder $checkout): CheckoutResponse
    {
        $order = $checkout instanceof CheckoutOrder ? $checkout : $this->buildOrder($checkout);
        $response = $this->connector->post('api/orders', $order->toArray());

        return CheckoutResponse::make($response->json)->withLastResponse($response);
    }

    /**
     * Retrieve an existing Svea Checkout order by its ID.
     *
     * @param  string  $orderId  The Svea order identifier.
     */
    public function get(string $orderId): CheckoutResponse
    {
        $response = $this->connector->get("api/orders/{$orderId}");

        return CheckoutResponse::make($response->json)->withLastResponse($response);
    }

    /**
     * Update an existing Svea Checkout order.
     *
     * @param  string  $orderId  The Svea order identifier.
     * @param  callable(CheckoutOrder): void|CheckoutOrder  $checkout
     */
    public function update(string $orderId, callable|CheckoutOrder $checkout): CheckoutResponse
    {
        $order = $checkout instanceof CheckoutOrder ? $checkout : $this->buildOrder($checkout);
        $response = $this->connector->post("api/orders/{$orderId}", $order->toArray());

        return CheckoutResponse::make($response->json)->withLastResponse($response);
    }

    /**
     * Cancel an existing Svea Checkout order.
     *
     * @param  string  $orderId  The Svea order identifier.
     */
    public function cancel(string $orderId): void
    {
        $this->connector->post("api/orders/{$orderId}/cancel");
    }

    /**
     * Build a {@see CheckoutOrder} from a callable using a blank instance.
     *
     * @param  callable(CheckoutOrder): void  $callback
     */
    private function buildOrder(callable $callback): CheckoutOrder
    {
        $order = CheckoutOrder::make();
        $callback($order);

        return $order;
    }
}
