<?php

declare(strict_types=1);

namespace Svea\Checkout;

/**
 * A fallback shipping option used when the primary shipping service (nShift) is unavailable.
 *
 * Pass one or more instances to {@see ShippingInformation::__construct()} or
 * {@see ShippingInformation::addFallbackOption()}.
 *
 * @see https://docs.payments.svea.com/docs/checkout/data-types
 */
final readonly class FallbackOption
{
    /**
     * @param  string  $id  Required. nShift carrier ID (typically a UUID).
     * @param  string  $carrier  Required. Name of the carrier (e.g. `DHL Home Delivery`).
     * @param  string  $name  Required. Delivery option name (e.g. `dhl`).
     * @param  int  $price  Required. Price of the option in whole currency units (e.g. 29 = 29 SEK).
     * @param  int  $shippingFee  Required. Price of the parcel in minor currency units (e.g. 2600 = 26.00 SEK).
     * @param  array<int, mixed>  $addons  Optional. nShift format add-ons.
     * @param  array<int, mixed>  $fields  Optional. nShift format fields.
     */
    public function __construct(
        public string $id,
        public string $carrier,
        public string $name,
        public int $price,
        public int $shippingFee,
        public array $addons = [],
        public array $fields = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'Id' => $this->id,
            'Carrier' => $this->carrier,
            'Name' => $this->name,
            'Price' => $this->price,
            'ShippingFee' => $this->shippingFee,
            'Addons' => $this->addons ?: null,
            'Fields' => $this->fields ?: null,
        ], fn (mixed $v) => $v !== null);
    }
}
