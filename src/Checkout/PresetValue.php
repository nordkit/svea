<?php

declare(strict_types=1);

namespace Svea\Checkout;

/**
 * Pre-fills a checkout field with a value that may optionally be locked.
 *
 * Pass one or more instances to {@see CheckoutOrder::presetValues()}.
 *
 * Example:
 * ```php
 * new PresetValue(typeName: 'EmailAddress', value: 'customer@example.com', isReadonly: true)
 * ```
 *
 * ## Supported type names
 *
 * | TypeName       | Type    | Additional Information                                                        |
 * |----------------|---------|-------------------------------------------------------------------------------|
 * | NationalId     | String  | Country-specific validation.                                                  |
 * | EmailAddress   | String  | Max 50 characters. Must be a valid email address.                             |
 * | PhoneNumber    | String  | 1–18 digits, can include "+", "-" and space.                                  |
 * | PostalCode     | String  | Country-specific validation.                                                  |
 * | IsCompany      | Boolean | Required if NationalId is set to read-only.                                   |
 * | PeppolId       | String  | Starts with four digits, followed by a colon, and alphanumeric characters.    |
 *
 * @see https://docs.payments.svea.com/docs/checkout/data-types
 */
final readonly class PresetValue
{
    /**
     * @param  string  $typeName  The field type to pre-fill (e.g. `EmailAddress`, `PhoneNumber`).
     * @param  string  $value  The value to pre-fill.
     * @param  bool  $isReadonly  Whether the customer can change the value. Default false.
     */
    public function __construct(
        public string $typeName,
        public string $value,
        public bool $isReadonly = false,
    ) {}

    /**
     * @return array{TypeName: string, Value: string, IsReadonly: bool}
     */
    public function toArray(): array
    {
        return [
            'TypeName' => $this->typeName,
            'Value' => $this->value,
            'IsReadonly' => $this->isReadonly,
        ];
    }
}
