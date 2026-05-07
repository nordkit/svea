<?php

declare(strict_types=1);

namespace Svea\Contracts;

use Svea\Checkout\CheckoutOrder;
use Svea\Checkout\CheckoutResponse;
use Svea\Checkout\CheckoutService;
use Svea\Testing\FakeCheckoutService;

/**
 * Contract for the Svea Checkout API service.
 *
 * Implemented by {@see CheckoutService} for real API calls and by
 * {@see FakeCheckoutService} for in-memory test doubles.
 *
 * Type-hint against this interface in application code to allow seamless
 * swapping between the real service and the fake in tests.
 */
interface CheckoutServiceInterface
{
    /**
     * Create a new Svea Checkout order.
     *
     * Accepts either a pre-built {@see CheckoutOrder} or a callable that receives
     * a blank order to populate fluently.
     *
     * @param  callable(CheckoutOrder): void|CheckoutOrder  $checkout
     * @return CheckoutResponse The created order, including the Gui snippet.
     */
    public function create(callable|CheckoutOrder $checkout): CheckoutResponse;

    /**
     * Retrieve an existing Svea Checkout order by its ID.
     *
     * @param  string  $orderId  The Svea order identifier.
     * @return CheckoutResponse The current order state.
     */
    public function get(string $orderId): CheckoutResponse;

    /**
     * Update an existing Svea Checkout order.
     *
     * @param  string  $orderId  The Svea order identifier.
     * @param  callable(CheckoutOrder): void|CheckoutOrder  $checkout
     * @return CheckoutResponse The updated order state.
     */
    public function update(string $orderId, callable|CheckoutOrder $checkout): CheckoutResponse;

    /**
     * Cancel an existing Svea Checkout order.
     *
     * @param  string  $orderId  The Svea order identifier.
     */
    public function cancel(string $orderId): void;
}
