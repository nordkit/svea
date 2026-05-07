<?php

declare(strict_types=1);

namespace Svea\Checkout;

use Svea\Support\Conditionable;

/**
 * Fluent builder for a Svea Checkout order payload.
 *
 * Populate the order by chaining setter methods, then pass it to
 * {@see CheckoutService::create()} or {@see CheckoutService::update()}.
 *
 * All currency amounts are in minor units (e.g. öre for SEK).
 * VAT is expressed as a whole integer percent (e.g. 25 for 25%).
 *
 * Required: `currency`, `countryCode`, `locale`, `clientOrderNumber`, `merchantSettings`, `cart`.
 *
 * Usage example:
 * ```php
 * $order = new CheckoutOrder(
 *     currency: 'SEK',
 *     countryCode: 'SE',
 *     locale: 'sv-SE',
 *     clientOrderNumber: $cart->reference,
 *     merchantSettings: new MerchantSettings(
 *         pushUri: route('svea.push'),
 *         termsUri: route('terms'),
 *         confirmationUri: route('checkout.confirmed'),
 *         checkoutUri: route('checkout'),
 *     ),
 *     cart: new Cart([
 *         new OrderRow(name: 'Widget', quantity: 1, unitPrice: 9900, vatPercent: 25, sku: 'SKU-1'),
 *     ]),
 * );
 * ```
 *
 * Optional fields can be chained after construction:
 * ```php
 * $order = (new CheckoutOrder(
 *     currency: 'SEK',
 *     countryCode: 'SE',
 *     locale: 'sv-SE',
 *     clientOrderNumber: $cart->reference,
 *     merchantSettings: $merchantSettings,
 *     cart: $cart,
 * ))->merchantData('my-data')
 *   ->when($hasDiscount, fn ($o) => $o->addRow(fn ($r) => $r->name('Discount')->quantity(1)->unitPrice(-500)->sku('DISC')));
 * ```
 *
 * Supports inline conditional branching via the {@see Conditionable} trait:
 * ```php
 * $order->when($hasDiscount, fn ($o) => $o->addRow(fn ($r) => $r->name('Discount')->quantity(1)->unitPrice(-500)->sku('DISC')));
 * ```
 */
class CheckoutOrder
{
    use Conditionable;

    private ?Cart $cart = null;

    private ?MerchantSettings $merchantSettingsObject = null;

    /** @var array<string, mixed> */
    private array $legacyMerchantSettings = [];

    /** @var array<string, mixed> */
    private array $attributes = [];

    /** @var array<int, array<string, mixed>> */
    private array $presetValuesData = [];

    private ?IdentityFlags $identityFlagsObject = null;

    private ?ShippingInformation $shippingInformationObject = null;

    private ?Validation $validationObject = null;

    /** @var array<string, string> */
    private array $metadataItems = [];

    /**
     * All parameters are optional when using the fluent callback API.
     * When constructing directly, `currency`, `countryCode`, `locale`, `clientOrderNumber`,
     * `merchantSettings`, and `cart` are required by the Svea API.
     *
     * @param  string|null  $currency  ISO 4217 currency code (e.g. `SEK`, `EUR`).
     * @param  string|null  $countryCode  ISO 3166-1 alpha-2 country code (e.g. `SE`).
     * @param  string|null  $locale  BCP 47 locale. Supported: `sv-SE`, `da-DK`, `de-DE`, `en-US`, `fi-FI`, `nn-NO`.
     * @param  string|null  $clientOrderNumber  Merchant's own order reference (max 32 chars). Must be unique per order.
     * @param  MerchantSettings|null  $merchantSettings  Merchant callback URI settings.
     * @param  Cart|null  $cart  Cart with order rows (non-empty, total >= 0, max 1000 rows).
     * @param  array<int, PresetValue>  $presetValues  Optional. Pre-fill customer fields in the checkout.
     * @param  IdentityFlags|null  $identityFlags  Optional. Hide/show identity UI elements.
     * @param  ShippingInformation|null  $shippingInformation  Optional. Shipping checkout configuration (requires merchant shipping enabled).
     * @param  Validation|null  $validation  Optional. Order validation rules (e.g. min age).
     * @param  bool  $requireElectronicIdAuthentication  Optional. Require BankID or equivalent.
     * @param  string|null  $partnerKey  Optional. Svea partner key (UUID).
     * @param  string|null  $merchantData  Optional. Opaque merchant metadata (max 6000 chars).
     * @param  bool  $recurring  Optional. Create a recurring token on finalisation (requires merchant recurring enabled).
     * @param  array<string, string>  $metadata  Optional. Key-value metadata pairs visible in the Svea portal. Cleaned up after 45 days.
     */
    public function __construct(
        ?string $currency = null,
        ?string $countryCode = null,
        ?string $locale = null,
        ?string $clientOrderNumber = null,
        ?MerchantSettings $merchantSettings = null,
        ?Cart $cart = null,
        array $presetValues = [],
        ?IdentityFlags $identityFlags = null,
        ?ShippingInformation $shippingInformation = null,
        ?Validation $validation = null,
        bool $requireElectronicIdAuthentication = false,
        ?string $partnerKey = null,
        ?string $merchantData = null,
        bool $recurring = false,
        array $metadata = [],
    ) {
        if ($currency !== null) {
            $this->currency($currency);
        }
        if ($countryCode !== null) {
            $this->countryCode($countryCode);
        }
        if ($locale !== null) {
            $this->locale($locale);
        }
        if ($clientOrderNumber !== null) {
            $this->clientOrderNumber($clientOrderNumber);
        }
        if ($merchantSettings !== null) {
            $this->merchantSettings($merchantSettings);
        }
        if ($cart !== null) {
            $this->cart = $cart;
        }
        foreach ($presetValues as $presetValue) {
            $this->addPresetValue($presetValue);
        }
        if ($identityFlags !== null) {
            $this->identityFlags($identityFlags);
        }
        if ($shippingInformation !== null) {
            $this->shippingInformation($shippingInformation);
        }
        if ($validation !== null) {
            $this->validation($validation);
        }
        if ($requireElectronicIdAuthentication) {
            $this->attributes['RequireElectronicIdAuthentication'] = true;
        }
        if ($partnerKey !== null) {
            $this->attributes['PartnerKey'] = $partnerKey;
        }
        if ($merchantData !== null) {
            $this->attributes['MerchantData'] = $merchantData;
        }
        if ($recurring) {
            $this->attributes['Recurring'] = true;
        }
        if ($metadata !== []) {
            $this->metadata($metadata);
        }
    }

    /**
     * Create a new instance for the fluent callback API.
     *
     * Equivalent to `new static()` — provided as a named constructor for
     * clarity at call sites inside {@see CheckoutService::create()} and similar.
     *
     * @internal Only used by CheckoutService and FakeCheckoutService::buildOrder().
     */
    public static function make(): static
    {
        return new static; // @phpstan-ignore new.static
    }

    /**
     * Set the order currency code (ISO 4217, e.g. `SEK`, `EUR`).
     *
     * @param  string  $currency  ISO 4217 currency code — automatically upper-cased.
     */
    public function currency(string $currency): static
    {
        $this->attributes['Currency'] = strtoupper($currency);

        return $this;
    }

    /**
     * Set the checkout locale (e.g. `sv-SE`, `en-US`).
     *
     * Controls the language displayed in the Svea Checkout iframe.
     * Supported values: `sv-SE`, `da-DK`, `de-DE`, `en-US`, `fi-FI`, `nn-NO`.
     *
     * @param  string  $locale  BCP 47 locale string.
     */
    public function locale(string $locale): static
    {
        $this->attributes['Locale'] = $locale;

        return $this;
    }

    /**
     * Set the customer's country code (ISO 3166-1 alpha-2, e.g. `SE`, `NO`).
     *
     * @param  string  $countryCode  ISO 3166-1 alpha-2 code — automatically upper-cased.
     */
    public function countryCode(string $countryCode): static
    {
        $this->attributes['CountryCode'] = strtoupper($countryCode);

        return $this;
    }

    /**
     * Set the merchant's own order reference number.
     *
     * Stored alongside the Svea order and returned in webhooks, making it easy
     * to correlate Svea orders with internal orders.
     *
     * @param  string  $orderNumber  The merchant's order reference.
     */
    public function clientOrderNumber(string $orderNumber): static
    {
        $this->attributes['ClientOrderNumber'] = $orderNumber;

        return $this;
    }

    /**
     * Set merchant settings using a {@see MerchantSettings} value object or a fluent callback.
     *
     * The callable receives a blank {@see MerchantSettings} instance to populate via
     * the fluent setters:
     * ```php
     * $order->merchantSettings(fn (MerchantSettings $s) => $s
     *     ->pushUri(route('svea.push'))
     *     ->termsUri(route('terms'))
     *     ->confirmationUri(route('checkout.confirmed'))
     *     ->checkoutUri(route('checkout')));
     * ```
     *
     * Replaces any previously set individual URI values.
     *
     * @param  callable(MerchantSettings): void|MerchantSettings  $settings
     */
    public function merchantSettings(callable|MerchantSettings $settings): static
    {
        if (is_callable($settings)) {
            $blank = MerchantSettings::make();
            $settings($blank);
            $this->merchantSettingsObject = $blank;
        } else {
            $this->merchantSettingsObject = $settings;
        }

        return $this;
    }

    /**
     * Set the URL Svea will POST order status updates to (push webhook).
     *
     * @param  string  $uri  Absolute HTTPS URL.
     */
    public function pushUri(string $uri): static
    {
        $this->legacyMerchantSettings['PushUri'] = $uri;

        return $this;
    }

    /**
     * Set the URL to redirect the customer to after a successful payment.
     *
     * @param  string  $uri  Absolute HTTPS URL.
     */
    public function confirmationUri(string $uri): static
    {
        $this->legacyMerchantSettings['ConfirmationUri'] = $uri;

        return $this;
    }

    /**
     * Set the URL to your terms and conditions page.
     *
     * Required by Svea — displayed in the checkout iframe.
     *
     * @param  string  $uri  Absolute HTTPS URL.
     */
    public function termsUri(string $uri): static
    {
        $this->legacyMerchantSettings['TermsUri'] = $uri;

        return $this;
    }

    /**
     * Set the URL of the page hosting the Svea Checkout iframe.
     *
     * @param  string  $uri  Absolute HTTPS URL.
     */
    public function checkoutUri(string $uri): static
    {
        $this->legacyMerchantSettings['CheckoutUri'] = $uri;

        return $this;
    }

    /**
     * Set the cart using a {@see Cart} value object.
     *
     * Replaces any rows previously added via {@see addRow()}.
     */
    public function cart(Cart $cart): static
    {
        $this->cart = $cart;

        return $this;
    }

    /**
     * Add an order row via a fluent callback.
     *
     * The callback receives a blank {@see OrderRow} builder instance. Set at
     * minimum `name()`, `quantity()`, and `unitPrice()` — they are required by
     * the Svea API. The built row is appended to the cart automatically.
     *
     * @param  callable(OrderRow): void  $callback
     */
    public function addRow(callable $callback): static
    {
        if ($this->cart === null) {
            $this->cart = new Cart;
        }

        $this->cart->addRow($callback);

        return $this;
    }

    /**
     * Add a {@see PresetValue} to pre-fill a customer field in the checkout.
     */
    public function addPresetValue(PresetValue $presetValue): static
    {
        $this->presetValuesData[] = $presetValue->toArray();

        return $this;
    }

    /**
     * Set identity UI flags using an {@see IdentityFlags} value object.
     */
    public function identityFlags(IdentityFlags $flags): static
    {
        $this->identityFlagsObject = $flags;

        return $this;
    }

    /**
     * Set shipping information using a {@see ShippingInformation} value object.
     *
     * Only applicable when the merchant has shipping checkout enabled.
     */
    public function shippingInformation(ShippingInformation $shippingInformation): static
    {
        $this->shippingInformationObject = $shippingInformation;

        return $this;
    }

    /**
     * Set order validation rules using a {@see Validation} value object.
     */
    public function validation(Validation $validation): static
    {
        $this->validationObject = $validation;

        return $this;
    }

    /**
     * Require electronic ID authentication (e.g. BankID) for this order.
     */
    public function requireElectronicIdAuthentication(bool $require = true): static
    {
        $this->attributes['RequireElectronicIdAuthentication'] = $require;

        return $this;
    }

    /**
     * Set the Svea partner key (UUID provided by Svea).
     *
     * @param  string  $partnerKey  UUID string.
     */
    public function partnerKey(string $partnerKey): static
    {
        $this->attributes['PartnerKey'] = $partnerKey;

        return $this;
    }

    /**
     * Attach opaque merchant metadata to the order (max 6000 chars).
     *
     * @param  string  $merchantData  Arbitrary metadata visible in the Svea portal.
     */
    public function merchantData(string $merchantData): static
    {
        $this->attributes['MerchantData'] = $merchantData;

        return $this;
    }

    /**
     * Mark this order as recurring.
     *
     * A recurring token is created when the order is finalised, which can be
     * used to charge the customer again without re-entering payment details.
     */
    public function recurring(bool $recurring = true): static
    {
        $this->attributes['Recurring'] = $recurring;

        return $this;
    }

    /**
     * Attach key-value metadata pairs to the order.
     *
     * Metadata is visible in the Svea portal and is cleaned up after 45 days.
     *
     * @param  array<string, string>  $metadata
     */
    public function metadata(array $metadata): static
    {
        $this->metadataItems = $metadata;

        return $this;
    }

    /**
     * Compile the order into the array payload expected by the Svea Checkout API.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $merchantSettings = $this->merchantSettingsObject !== null
            ? $this->merchantSettingsObject->toArray()
            : $this->legacyMerchantSettings;

        $payload = array_merge($this->attributes, [
            'MerchantSettings' => $merchantSettings,
            'Cart' => $this->cart?->toArray() ?? ['Items' => []],
        ]);

        if ($this->presetValuesData !== []) {
            $payload['PresetValues'] = $this->presetValuesData;
        }
        if ($this->identityFlagsObject !== null) {
            $payload['IdentityFlags'] = $this->identityFlagsObject->toArray();
        }
        if ($this->shippingInformationObject !== null) {
            $payload['ShippingInformation'] = $this->shippingInformationObject->toArray();
        }
        if ($this->validationObject !== null) {
            $payload['Validation'] = $this->validationObject->toArray();
        }
        if ($this->metadataItems !== []) {
            $payload['Metadata'] = array_map(
                fn (string $key, string $value): array => ['Key' => $key, 'Value' => $value],
                array_keys($this->metadataItems),
                $this->metadataItems,
            );
        }

        return $payload;
    }
}
