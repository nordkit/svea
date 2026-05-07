<?php

declare(strict_types=1);

namespace Svea\Checkout;

/**
 * Order-level validation rules applied during checkout.
 *
 * Pass an instance to {@see CheckoutOrder::validation()}.
 *
 * Example:
 * ```php
 * new Validation(minAge: 18)
 * ```
 *
 * @see https://docs.payments.svea.com/docs/checkout/data-types
 */
final readonly class Validation
{
    /**
     * @param  int|null  $minAge  Minimum customer age required to complete the order. Null to disable.
     */
    public function __construct(
        public ?int $minAge = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter(['MinAge' => $this->minAge], fn (mixed $v) => $v !== null);
    }
}
