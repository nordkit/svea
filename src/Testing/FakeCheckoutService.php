<?php

declare(strict_types=1);

namespace Svea\Testing;

use Svea\Checkout\CheckoutOrder;
use Svea\Checkout\CheckoutResponse;
use Svea\Contracts\CheckoutServiceInterface;

/**
 * In-memory fake for CheckoutService, used in tests via FakeSveaClient::fake().
 *
 * Mirrors the full CheckoutService API — create, get, update, and cancel.
 * All calls are recorded for assertion via {@see SveaFakeAssertions}.
 *
 * Pre-seed responses using the fake key map:
 * ```php
 * Svea::fake(['checkout.create' => CheckoutResponse::make([...])]);
 * ```
 *
 * Then assert after running code under test:
 * ```php
 * Svea::assertCheckoutCreated();
 * ```
 */
class FakeCheckoutService implements CheckoutServiceInterface
{
    public function __construct(private readonly SveaFakeAssertions $assertions) {}

    /**
     * Record a checkout.create call and return a seeded or default response.
     *
     * @param  callable(CheckoutOrder): void|CheckoutOrder  $checkout
     */
    public function create(callable|CheckoutOrder $checkout): CheckoutResponse
    {
        $order = $checkout instanceof CheckoutOrder ? $checkout : $this->buildOrder($checkout);
        $this->assertions->recordCall('checkout.create', [$order->toArray()]);
        if ($this->assertions->hasFakeFor('checkout.create')) {
            return $this->assertions->fakeFor('checkout.create');
        }
        if ($this->assertions->isPreventingStrayRequests()) {
            throw new \RuntimeException('Svea fake has no response seeded for [checkout.create] and stray requests are prevented.');
        }

        return CheckoutResponse::make(['OrderId' => 'fake-order-id', 'Status' => 'Created', 'Gui' => ['Snippet' => '<div>fake</div>']]);
    }

    /**
     * Record a checkout.get call and return a seeded or default response.
     *
     * @param  string  $orderId  The Svea order identifier.
     */
    public function get(string $orderId): CheckoutResponse
    {
        $this->assertions->recordCall('checkout.get', [$orderId]);
        if ($this->assertions->hasFakeFor('checkout.get')) {
            return $this->assertions->fakeFor('checkout.get');
        }

        return CheckoutResponse::make(['OrderId' => $orderId, 'Status' => 'Created']);
    }

    /**
     * Record a checkout.update call and return a seeded or default response.
     *
     * @param  string  $orderId  The Svea order identifier.
     * @param  callable(CheckoutOrder): void|CheckoutOrder  $checkout
     */
    public function update(string $orderId, callable|CheckoutOrder $checkout): CheckoutResponse
    {
        $order = $checkout instanceof CheckoutOrder ? $checkout : $this->buildOrder($checkout);
        $this->assertions->recordCall('checkout.update', [$orderId, $order->toArray()]);
        if ($this->assertions->hasFakeFor('checkout.update')) {
            return $this->assertions->fakeFor('checkout.update');
        }

        return CheckoutResponse::make(['OrderId' => $orderId, 'Status' => 'Created']);
    }

    /**
     * Record a checkout.cancel call.
     *
     * @param  string  $orderId  The Svea order identifier.
     */
    public function cancel(string $orderId): void
    {
        $this->assertions->recordCall('checkout.cancel', [$orderId]);
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
