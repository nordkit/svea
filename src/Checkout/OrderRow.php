<?php

declare(strict_types=1);

namespace Svea\Checkout;

use Svea\Admin\CreditRequest;

/**
 * Fluent builder for a single order row in a Svea Checkout order.
 *
 * Used inside the {@see CheckoutOrder::addRow()} callback and, for credit rows,
 * inside {@see CreditRequest::newRow()}.
 *
 * All numeric values follow Svea's minor-unit convention:
 * - Quantities: 100 = 1 unit, 300 = 3 units
 * - Prices/amounts: 9900 = 99.00 SEK
 * - Percentages: 2500 = 25%, 1000 = 10%
 *
 * Constructor-style example:
 * ```php
 * $row = new OrderRow(
 *     name: 'T-Shirt Black M',
 *     quantity: 100,      // 1 unit
 *     unitPrice: 29900,   // 299.00 SEK
 *     sku: 'TSHIRT-BLK-M',
 *     vatPercent: 2500,   // 25%
 *     unit: 'st',
 * );
 * ```
 *
 * Fluent-style example:
 * ```php
 * $order->addRow(function (OrderRow $row) {
 *     $row->name('T-Shirt Black M')
 *         ->quantity(100)     // 1 unit
 *         ->unitPrice(29900)  // 299.00 SEK
 *         ->vatPercent(2500)  // 25%
 *         ->sku('TSHIRT-BLK-M')
 *         ->unit('st');
 * });
 * ```
 */
class OrderRow
{
    /** @var array<string, mixed> */
    private array $data = [];

    /**
     * All parameters are optional when using the fluent callback API.
     * When constructing directly, `name`, `quantity`, and `unitPrice` are required by the Svea API.
     *
     * @param  string|null  $name  Article name (max 40 chars).
     * @param  int|null  $quantity  Quantity in minor units (100 = 1 unit).
     * @param  int|null  $unitPrice  Unit price in minor currency units. Can be negative except for ShippingFee rows.
     * @param  string|null  $sku  Article/SKU number (max 256 chars).
     * @param  int|null  $discountPercent  Discount in minor units (1000 = 10%). Cannot be used with discountAmount.
     * @param  int|null  $discountAmount  Fixed discount amount in minor currency units. Cannot be used with discountPercent.
     * @param  int|null  $vatPercent  VAT in minor units (2500 = 25%).
     * @param  string|null  $unit  Unit of measurement label (e.g. `st`, `kg`). Max 4 chars.
     * @param  string|null  $temporaryReference  Temporary reference string for this row.
     * @param  int|null  $rowNumber  Row number in the Webpay system.
     * @param  string|null  $merchantData  Merchant-specific data for this row (max 255 chars).
     * @param  string  $rowType  Row type — `Row` or `ShippingFee`. Defaults to `Row`.
     */
    public function __construct(
        ?string $name = null,
        ?int $quantity = null,
        ?int $unitPrice = null,
        ?string $sku = null,
        ?int $discountPercent = null,
        ?int $discountAmount = null,
        ?int $vatPercent = null,
        ?string $unit = null,
        ?string $temporaryReference = null,
        ?int $rowNumber = null,
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
        if ($discountAmount !== null) {
            $this->discountAmount($discountAmount);
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
        if ($rowNumber !== null) {
            $this->rowNumber($rowNumber);
        }
        if ($merchantData !== null) {
            $this->merchantData($merchantData);
        }
    }

    /**
     * Create a new instance for the fluent callback API.
     *
     * Equivalent to `new static()` — provided as a named constructor for
     * clarity at call sites inside {@see Cart::addRow()} and similar.
     */
    public static function make(): static
    {
        return new static; // @phpstan-ignore new.static
    }

    /**
     * Set the article/SKU number.
     *
     * @param  string  $sku  The merchant's article identifier.
     */
    public function sku(string $sku): static
    {
        $this->data['ArticleNumber'] = $sku;

        return $this;
    }

    /**
     * Set the display name of the row.
     *
     * @param  string  $name  Human-readable product or service name.
     */
    public function name(string $name): static
    {
        $this->data['Name'] = $name;

        return $this;
    }

    /**
     * Set the row quantity in minor units (100 = 1 unit).
     *
     * @param  int  $quantity  Quantity in minor units, e.g. 300 for 3 units.
     */
    public function quantity(int $quantity): static
    {
        $this->data['Quantity'] = $quantity;

        return $this;
    }

    /**
     * Set the unit price in minor currency units (e.g. öre for SEK).
     *
     * Must be inclusive of VAT. Use negative values for discount rows.
     *
     * @param  int  $unitPrice  Price in minor units, e.g. 9900 for 99.00 SEK.
     */
    public function unitPrice(int $unitPrice): static
    {
        $this->data['UnitPrice'] = $unitPrice;

        return $this;
    }

    /**
     * Set the VAT percentage in minor units (2500 = 25%).
     *
     * @param  int  $vatPercent  VAT in minor units, e.g. 2500 for 25%.
     */
    public function vatPercent(int $vatPercent): static
    {
        $this->data['VatPercent'] = $vatPercent;

        return $this;
    }

    /**
     * Set the row-level discount in minor units (1000 = 10%).
     *
     * @param  int  $discountPercent  Discount in minor units, e.g. 1000 for 10%.
     */
    public function discountPercent(int $discountPercent): static
    {
        $this->data['DiscountPercent'] = $discountPercent;

        return $this;
    }

    /**
     * Set the unit of measurement label (e.g. `st`, `kg`, `h`).
     *
     * @param  string  $unit  Unit label displayed in the checkout.
     */
    public function unit(string $unit): static
    {
        $this->data['Unit'] = $unit;

        return $this;
    }

    /**
     * Set the temporary reference for this row.
     *
     * Returned in API responses as-is but not stored and not returned in GetOrder.
     *
     * @param  string  $temporaryReference  Merchant-assigned reference string.
     */
    public function temporaryReference(string $temporaryReference): static
    {
        $this->data['TemporaryReference'] = $temporaryReference;

        return $this;
    }

    /**
     * Set a fixed discount amount in minor currency units.
     *
     * Use as an alternative to `discountPercent()` when you have a fixed monetary discount.
     *
     * @param  int  $discountAmount  Discount amount in minor units (e.g. 1000 for 10.00 SEK).
     */
    public function discountAmount(int $discountAmount): static
    {
        $this->data['DiscountAmount'] = $discountAmount;

        return $this;
    }

    /**
     * Set the row number within the order.
     *
     * @param  int  $rowNumber  1-based row number.
     */
    public function rowNumber(int $rowNumber): static
    {
        $this->data['RowNumber'] = $rowNumber;

        return $this;
    }

    /**
     * Attach merchant-specific data to this row.
     *
     * @param  string  $merchantData  Arbitrary merchant data (max 255 chars).
     */
    public function merchantData(string $merchantData): static
    {
        $this->data['MerchantData'] = $merchantData;

        return $this;
    }

    /**
     * Set the row type.
     *
     * Use `Row` for standard product rows and `ShippingFee` for shipping cost rows.
     * Defaults to `Row`.
     *
     * @param  string  $rowType  `Row` or `ShippingFee`.
     */
    public function rowType(string $rowType): static
    {
        $this->data['RowType'] = $rowType;

        return $this;
    }

    /**
     * Compile the row into the array payload expected by the Svea Checkout API.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }
}
