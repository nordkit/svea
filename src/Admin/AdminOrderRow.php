<?php

declare(strict_types=1);

namespace Svea\Admin;

use Svea\Checkout\OrderRow;
use Svea\Support\Conditionable;

/**
 * Fluent builder for Svea Payment Admin order rows.
 *
 * Used in {@see AdminOrderRequest::addOrderRow()}, {@see AdminOrderRequest::updateOrderRow()},
 * {@see AdminOrderRequest::replaceOrderRows()}, and {@see CreditRequest::newRow()}.
 *
 * Unlike the Checkout {@see OrderRow}, values must be passed in **Svea's native
 * minor-unit format** — no SDK-level conversion is applied:
 * - `quantity(100)` → 1 item (× 100)
 * - `vatPercent(2500)` → 25% (× 100, basis points)
 * - `discountPercent(1000)` → 10% (× 100, basis points)
 *
 * The default `RowType` is `Row`. Override with `rowType()` when adding fee or
 * shipping rows.
 *
 * Supports inline conditional branching via the {@see Conditionable} trait.
 */
class AdminOrderRow
{
    use Conditionable;

    /** @var array<string, mixed> */
    private array $attributes = ['RowType' => 'Row'];

    /**
     * All parameters are optional when using the fluent callback API.
     * When constructing directly, `name`, `quantity`, and `unitPrice` are required by the Svea Admin API.
     *
     * @param  string|null  $name  Human-readable product or service name.
     * @param  int|null  $quantity  Quantity in minor units (100 = 1 item).
     * @param  int|null  $unitPrice  Unit price in minor currency units, inclusive of VAT.
     * @param  string|null  $sku  Article/SKU number.
     * @param  int|null  $discountPercent  Discount in basis points (1000 = 10%).
     * @param  int|null  $vatPercent  VAT in basis points (2500 = 25%).
     * @param  string|null  $unit  Unit of measurement label.
     * @param  string|null  $temporaryReference  Arbitrary reference string.
     * @param  string|null  $merchantData  JSON string or other opaque merchant data.
     * @param  string  $rowType  Row type — `Row`, `ShippingFee`, `InvoiceFee`, etc. Defaults to `Row`.
     */
    public function __construct(
        ?string $name = null,
        ?int $quantity = null,
        ?int $unitPrice = null,
        ?string $sku = null,
        ?int $discountPercent = null,
        ?int $vatPercent = null,
        ?string $unit = null,
        ?string $temporaryReference = null,
        ?string $merchantData = null,
        string $rowType = 'Row',
    ) {
        $this->rowType($rowType);
        if ($name !== null) {
            $this->name($name);
        }
        if ($quantity !== null) {
            $this->quantity($quantity);
        }
        if ($unitPrice !== null) {
            $this->unitPrice($unitPrice);
        }
        if ($sku !== null) {
            $this->sku($sku);
        }
        if ($discountPercent !== null) {
            $this->discountPercent($discountPercent);
        }
        if ($vatPercent !== null) {
            $this->vatPercent($vatPercent);
        }
        if ($unit !== null) {
            $this->unit($unit);
        }
        if ($temporaryReference !== null) {
            $this->temporaryReference($temporaryReference);
        }
        if ($merchantData !== null) {
            $this->merchantData($merchantData);
        }
    }

    /**
     * Create a new instance for the fluent callback API.
     *
     * Equivalent to `new static()` — provided as a named constructor for
     * clarity at call sites inside {@see AdminOrderRequest::addOrderRow()} and similar.
     */
    public static function make(): static
    {
        return new static; // @phpstan-ignore new.static
    }

    /**
     * Set the display name of the row.
     *
     * @param  string  $name  Human-readable product or service name.
     */
    public function name(string $name): static
    {
        $this->attributes['Name'] = $name;

        return $this;
    }

    /**
     * Set the row quantity in Svea's minor unit format (whole units × 100).
     *
     * Pass the already-converted value: `100` = 1 item, `200` = 2 items.
     * Unlike {@see OrderRow::quantity()}, no SDK conversion is applied here.
     *
     * @param  int  $quantity  Quantity in minor units (e.g. 100 for 1 item).
     */
    public function quantity(int $quantity): static
    {
        $this->attributes['Quantity'] = $quantity;

        return $this;
    }

    /**
     * Set the unit price in minor currency units (e.g. öre for SEK), inclusive of VAT.
     *
     * @param  int  $unitPrice  Price in minor units, e.g. 9900 for 99.00 SEK.
     */
    public function unitPrice(int $unitPrice): static
    {
        $this->attributes['UnitPrice'] = $unitPrice;

        return $this;
    }

    /**
     * Set the VAT percentage in Svea's basis-point format (whole percent × 100).
     *
     * Pass the already-converted value: `2500` = 25%, `1900` = 19%.
     * Unlike {@see OrderRow::vatPercent()}, no SDK conversion is applied here.
     *
     * @param  int  $vatPercent  VAT in basis points (e.g. 2500 for 25%).
     */
    public function vatPercent(int $vatPercent): static
    {
        $this->attributes['VatPercent'] = $vatPercent;

        return $this;
    }

    /**
     * Set the article/SKU number.
     *
     * @param  string  $sku  The merchant's article identifier.
     */
    public function sku(string $sku): static
    {
        $this->attributes['ArticleNumber'] = $sku;

        return $this;
    }

    /**
     * Set the row-level discount percentage in Svea's basis-point format (whole percent × 100).
     *
     * Pass the already-converted value: `1000` = 10%, `5000` = 50%.
     * Unlike {@see OrderRow::discountPercent()}, no SDK conversion is applied here.
     *
     * @param  int  $discountPercent  Discount in basis points (e.g. 1000 for 10%).
     */
    public function discountPercent(int $discountPercent): static
    {
        $this->attributes['DiscountPercent'] = $discountPercent;

        return $this;
    }

    /**
     * Set the unit of measurement label (e.g. `st`, `kg`, `h`).
     *
     * @param  string  $unit  Unit label.
     */
    public function unit(string $unit): static
    {
        $this->attributes['Unit'] = $unit;

        return $this;
    }

    /**
     * Set the row type.
     *
     * Defaults to `Row`. Use `ShippingFee`, `InvoiceFee`, or other Svea-defined
     * types for non-product rows.
     *
     * @param  string  $rowType  Svea row type string.
     */
    public function rowType(string $rowType): static
    {
        $this->attributes['RowType'] = $rowType;

        return $this;
    }

    /**
     * Set a merchant-assigned temporary reference for this row.
     *
     * @param  string  $temporaryReference  Arbitrary reference string.
     */
    public function temporaryReference(string $temporaryReference): static
    {
        $this->attributes['TemporaryReference'] = $temporaryReference;

        return $this;
    }

    /**
     * Attach arbitrary merchant data to this row.
     *
     * @param  string  $merchantData  JSON string or other opaque merchant data.
     */
    public function merchantData(string $merchantData): static
    {
        $this->attributes['MerchantData'] = $merchantData;

        return $this;
    }

    /**
     * Compile the row into the array payload expected by the Svea Admin API.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->attributes;
    }
}
