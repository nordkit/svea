<?php

declare(strict_types=1);

namespace Svea\Checkout;

/**
 * Shipping configuration for a Svea Checkout order.
 *
 * Only applicable when the merchant has shipping checkout enabled.
 * Pass an instance to {@see CheckoutOrder::shippingInformation()}.
 *
 * Example:
 * ```php
 * $shipping = new ShippingInformation(
 *     enableShipping: true,
 *     weight: 1000,
 *     tags: ['IsBulky' => 'true'],
 *     fallbackOptions: [
 *         new FallbackOption(
 *             id: '145af7a2-e432-48b8-a6ce-2acf0519882a',
 *             carrier: 'DHL Home Delivery',
 *             name: 'dhl',
 *             shippingFee: 2900,
 *         ),
 *     ],
 * );
 * ```
 *
 * @see https://docs.payments.svea.com/docs/checkout/data-types
 */
final class ShippingInformation
{
    /** @var array<int, array<string, mixed>> */
    private array $fallbackOptions = [];

    /**
     * @param  bool  $enableShipping  Required. Set to true to enable shipping checkout.
     * @param  bool  $enforceFallback  Optional. Force the fallback options to be used.
     * @param  float|null  $weight  Optional. Shipment weight in grams.
     * @param  array<string, string>  $tags  Optional. Tags for shipping calculation (e.g. `['bulky' => 'true']`).
     * @param  array<int, FallbackOption>  $fallbackOptions  Optional. Fallback shipping options.
     * @param  bool  $shouldRejectShippingSession  Optional. Reject the shipping session.
     */
    public function __construct(
        public readonly bool $enableShipping = true,
        public readonly bool $enforceFallback = false,
        public readonly ?float $weight = null,
        public readonly array $tags = [],
        array $fallbackOptions = [],
        public readonly bool $shouldRejectShippingSession = false,
    ) {
        foreach ($fallbackOptions as $option) {
            $this->fallbackOptions[] = $option->toArray();
        }
    }

    /**
     * Add a fallback shipping option.
     */
    public function addFallbackOption(FallbackOption $option): static
    {
        $this->fallbackOptions[] = $option->toArray();

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'EnableShipping' => $this->enableShipping,
            'EnforceFallback' => $this->enforceFallback ?: null,
            'Weight' => $this->weight,
            'Tags' => $this->tags ?: null,
            'FallbackOptions' => $this->fallbackOptions ?: null,
            'ShouldRejectShippingSession' => $this->shouldRejectShippingSession ?: null,
        ], fn (mixed $v) => $v !== null);
    }
}
