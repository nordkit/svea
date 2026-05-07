<?php

declare(strict_types=1);

namespace Svea\Checkout;

/**
 * Represents the cart (items list) in a Svea Checkout order.
 *
 * Can be constructed with an array of {@see OrderRow} instances, or rows can
 * be added fluently via {@see addRow()}. Pass an instance to
 * {@see CheckoutOrder::__construct()} or {@see CheckoutOrder::cart()}.
 *
 * The Svea API requires the cart to be non-empty.
 *
 * Constructor-style example:
 * ```php
 * $cart = new Cart([
 *     new OrderRow(quantity: 1, unitPrice: 9900, vatPercent: 25, sku: 'SKU-1', name: 'Widget'),
 *     new OrderRow(quantity: 2, unitPrice: 4900, vatPercent: 25, sku: 'SKU-2', name: 'Gadget'),
 * ]);
 * ```
 *
 * Fluent-style example:
 * ```php
 * $cart = new Cart;
 * $cart->addRow(fn (OrderRow $row) => $row->name('Widget')->quantity(1)->unitPrice(9900)->vatPercent(25)->sku('SKU-1'));
 * ```
 */
final class Cart
{
    /** @var array<int, array<string, mixed>> */
    private array $items = [];

    /**
     * @param  array<int, OrderRow>  $rows  Pre-built order row instances.
     */
    public function __construct(array $rows = [])
    {
        foreach ($rows as $row) {
            $this->items[] = $row->toArray();
        }
    }

    /**
     * Add a row via a fluent callback.
     *
     * The callback receives a blank {@see OrderRow} builder instance.
     * Set at minimum `name()`, `quantity()`, and `unitPrice()` — they are
     * required by the Svea API.
     *
     * @param  callable(OrderRow): void  $callback
     */
    public function addRow(callable $callback): static
    {
        $row = OrderRow::make();
        $callback($row);
        $this->items[] = $row->toArray();

        return $this;
    }

    /**
     * Compile into the array payload expected by the Svea Checkout API.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return ['Items' => $this->items];
    }
}
