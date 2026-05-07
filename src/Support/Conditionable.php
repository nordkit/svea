<?php

declare(strict_types=1);

namespace Svea\Support;

use Svea\Admin\AdminOrderRequest;
use Svea\Admin\AdminOrderRow;
use Svea\Checkout\CheckoutOrder;
use Svea\Subscriptions\SubscriptionBuilder;

/**
 * Lightweight fluent conditional trait — zero framework dependencies.
 *
 * Provides `when()` and `unless()` for inline conditional branching inside
 * fluent builder chains without breaking the chain or reaching for if/else blocks.
 *
 * Used by {@see CheckoutOrder}, {@see AdminOrderRequest},
 * {@see AdminOrderRow}, and {@see SubscriptionBuilder}.
 *
 * This trait is intentionally self-contained — it has no dependency on Laravel,
 * Illuminate, or any other framework. It replicates the familiar `when()`/`unless()`
 * pattern found in Laravel's `Conditionable` trait as a standalone alternative
 * suitable for any PHP 8.2+ project.
 *
 * Example:
 * ```php
 * $svea->admin()->order('12345678')
 *     ->withIdempotencyKey('capture-' . $eventId)
 *     ->when($isPartialDelivery, fn ($o) => $o->deliver(rows: $rowIds))
 *     ->unless($isPartialDelivery, fn ($o) => $o->deliver());
 * ```
 */
trait Conditionable
{
    /**
     * Execute `$callback` on this instance if `$value` is truthy; otherwise
     * execute `$default` (if provided). Returns `$this` in both cases.
     *
     * @param  mixed  $value  The condition to evaluate.
     * @param  callable($this, mixed): mixed  $callback  Executed when truthy.
     * @param  callable($this, mixed): mixed|null  $default  Executed when falsy (optional).
     */
    public function when(mixed $value, callable $callback, ?callable $default = null): static
    {
        if ($value) {
            $callback($this, $value);
        } elseif ($default !== null) {
            $default($this, $value);
        }

        return $this;
    }

    /**
     * Execute `$callback` on this instance if `$value` is falsy; otherwise
     * execute `$default` (if provided). Returns `$this` in both cases.
     *
     * @param  mixed  $value  The condition to evaluate.
     * @param  callable($this, mixed): mixed  $callback  Executed when falsy.
     * @param  callable($this, mixed): mixed|null  $default  Executed when truthy (optional).
     */
    public function unless(mixed $value, callable $callback, ?callable $default = null): static
    {
        if (! $value) {
            $callback($this, $value);
        } elseif ($default !== null) {
            $default($this, $value);
        }

        return $this;
    }
}
