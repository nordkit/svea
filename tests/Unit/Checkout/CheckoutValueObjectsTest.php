<?php

declare(strict_types=1);

use Svea\Checkout\Cart;
use Svea\Checkout\CheckoutOrder;
use Svea\Checkout\FallbackOption;
use Svea\Checkout\IdentityFlags;
use Svea\Checkout\MerchantSettings;
use Svea\Checkout\OrderRow;
use Svea\Checkout\PresetValue;
use Svea\Checkout\ShippingInformation;
use Svea\Checkout\Validation;

// ── PresetValue ────────────────────────────────────────────────────────────

test('PresetValue serialises correctly', function (): void {
    $preset = new PresetValue(typeName: 'EmailAddress', value: 'customer@example.com', isReadonly: true);

    expect($preset->toArray())->toBe([
        'TypeName' => 'EmailAddress',
        'Value' => 'customer@example.com',
        'IsReadonly' => true,
    ]);
});

test('PresetValue defaults isReadonly to false', function (): void {
    $preset = new PresetValue(typeName: 'PhoneNumber', value: '+46701234567');

    expect($preset->toArray()['IsReadonly'])->toBeFalse();
});

// ── IdentityFlags ──────────────────────────────────────────────────────────

test('IdentityFlags defaults all flags to false', function (): void {
    $flags = new IdentityFlags;

    expect($flags->toArray())->toBe([
        'HideNotYou' => false,
        'HideChangeAddress' => false,
        'HideAnonymous' => false,
    ]);
});

test('IdentityFlags serialises individual flags', function (): void {
    $flags = new IdentityFlags(hideNotYou: true, hideAnonymous: true);

    expect($flags->toArray()['HideNotYou'])->toBeTrue()
        ->and($flags->toArray()['HideAnonymous'])->toBeTrue()
        ->and($flags->toArray()['HideChangeAddress'])->toBeFalse();
});

// ── Validation ─────────────────────────────────────────────────────────────

test('Validation serialises minAge', function (): void {
    expect((new Validation(minAge: 18))->toArray())->toBe(['MinAge' => 18]);
});

test('Validation toArray is empty when minAge is null', function (): void {
    expect((new Validation)->toArray())->toBeEmpty();
});

// ── FallbackOption ─────────────────────────────────────────────────────────

test('FallbackOption serialises required fields', function (): void {
    $option = new FallbackOption(
        id: '145af7a2-e432-48b8-a6ce-2acf0519882a',
        carrier: 'Bring',
        name: 'Home Delivery',
        price: 29,
        shippingFee: 2900,
    );

    $array = $option->toArray();

    expect($array['Id'])->toBe('145af7a2-e432-48b8-a6ce-2acf0519882a')
        ->and($array['Carrier'])->toBe('Bring')
        ->and($array['Name'])->toBe('Home Delivery')
        ->and($array['Price'])->toBe(29)
        ->and($array['ShippingFee'])->toBe(2900)
        ->and($array)->not->toHaveKey('Addons')
        ->and($array)->not->toHaveKey('Fields');
});

// ── ShippingInformation ────────────────────────────────────────────────────

test('ShippingInformation serialises enableShipping', function (): void {
    $shipping = new ShippingInformation(enableShipping: true);

    expect($shipping->toArray()['EnableShipping'])->toBeTrue();
});

test('ShippingInformation serialises weight and tags', function (): void {
    $shipping = new ShippingInformation(
        enableShipping: true,
        weight: 1500.0,
        tags: ['bulky' => 'true'],
    );

    $array = $shipping->toArray();

    expect($array['Weight'])->toBe(1500.0)
        ->and($array['Tags'])->toBe(['bulky' => 'true']);
});

test('ShippingInformation serialises constructor fallback options', function (): void {
    $shipping = new ShippingInformation(
        enableShipping: true,
        fallbackOptions: [
            new FallbackOption(id: 'uuid-1', carrier: 'PostNord', name: 'Parcel', price: 49, shippingFee: 4900),
        ],
    );

    expect($shipping->toArray()['FallbackOptions'])->toHaveCount(1)
        ->and($shipping->toArray()['FallbackOptions'][0]['Carrier'])->toBe('PostNord');
});

test('ShippingInformation addFallbackOption appends options fluently', function (): void {
    $shipping = new ShippingInformation(enableShipping: true);
    $shipping->addFallbackOption(new FallbackOption(id: 'uuid-1', carrier: 'Bring', name: 'Express', price: 99, shippingFee: 9900));
    $shipping->addFallbackOption(new FallbackOption(id: 'uuid-2', carrier: 'DHL', name: 'Economy', price: 49, shippingFee: 4900));

    expect($shipping->toArray()['FallbackOptions'])->toHaveCount(2);
});

// ── MerchantSettings fluent builder ───────────────────────────────────────

test('MerchantSettings callable pattern sets all required URIs', function (): void {
    $order = new CheckoutOrder(
        currency: 'SEK',
        countryCode: 'SE',
        locale: 'sv-SE',
        clientOrderNumber: 'ORD-1',
        merchantSettings: new MerchantSettings(
            pushUri: 'https://example.com/push',
            termsUri: 'https://example.com/terms',
            confirmationUri: 'https://example.com/confirmed',
            checkoutUri: 'https://example.com/checkout',
        ),
        cart: new Cart,
    );

    $order->merchantSettings(fn (MerchantSettings $s) => $s
        ->pushUri('https://new.com/push')
        ->termsUri('https://new.com/terms')
        ->confirmationUri('https://new.com/confirmed')
        ->checkoutUri('https://new.com/checkout'));

    $ms = $order->toArray()['MerchantSettings'];

    expect($ms['PushUri'])->toBe('https://new.com/push')
        ->and($ms['TermsUri'])->toBe('https://new.com/terms')
        ->and($ms['ConfirmationUri'])->toBe('https://new.com/confirmed')
        ->and($ms['CheckoutUri'])->toBe('https://new.com/checkout');
});

test('MerchantSettings callable pattern supports optional fields', function (): void {
    $order = new CheckoutOrder(
        currency: 'SEK',
        countryCode: 'SE',
        locale: 'sv-SE',
        clientOrderNumber: 'ORD-1',
        merchantSettings: new MerchantSettings(
            pushUri: 'https://example.com/push',
            termsUri: 'https://example.com/terms',
            confirmationUri: 'https://example.com/confirmed',
            checkoutUri: 'https://example.com/checkout',
        ),
        cart: new Cart,
    );

    $order->merchantSettings(fn (MerchantSettings $s) => $s
        ->pushUri('https://example.com/push')
        ->termsUri('https://example.com/terms')
        ->confirmationUri('https://example.com/confirmed')
        ->checkoutUri('https://example.com/checkout')
        ->webhookUri('https://example.com/webhook')
        ->activePartPaymentCampaigns([101, 102])
        ->promotedPartPaymentCampaign(101)
        ->useClientSideValidation());

    $ms = $order->toArray()['MerchantSettings'];

    expect($ms['WebhookUri'])->toBe('https://example.com/webhook')
        ->and($ms['ActivePartPaymentCampaigns'])->toBe([101, 102])
        ->and($ms['PromotedPartPaymentCampaign'])->toBe(101)
        ->and($ms['UseClientSideValidation'])->toBeTrue();
});

test('MerchantSettings constructor serialises correctly', function (): void {
    $settings = new MerchantSettings(
        pushUri: 'https://example.com/push',
        termsUri: 'https://example.com/terms',
        confirmationUri: 'https://example.com/confirmed',
        checkoutUri: 'https://example.com/checkout',
    );

    expect($settings->toArray())->toBe([
        'PushUri' => 'https://example.com/push',
        'TermsUri' => 'https://example.com/terms',
        'ConfirmationUri' => 'https://example.com/confirmed',
        'CheckoutUri' => 'https://example.com/checkout',
    ]);
});

// ── MerchantSettings campaign fields ──────────────────────────────────────

test('MerchantSettings includes campaign fields when set', function (): void {
    $settings = new MerchantSettings(
        pushUri: 'https://example.com/push',
        termsUri: 'https://example.com/terms',
        confirmationUri: 'https://example.com/confirmed',
        checkoutUri: 'https://example.com/checkout',
        activePartPaymentCampaigns: [101, 102],
        promotedPartPaymentCampaign: 101,
    );

    expect($settings->toArray()['ActivePartPaymentCampaigns'])->toBe([101, 102])
        ->and($settings->toArray()['PromotedPartPaymentCampaign'])->toBe(101);
});

test('MerchantSettings omits campaign fields when not set', function (): void {
    $settings = new MerchantSettings(
        pushUri: 'https://example.com/push',
        termsUri: 'https://example.com/terms',
        confirmationUri: 'https://example.com/confirmed',
        checkoutUri: 'https://example.com/checkout',
    );

    expect($settings->toArray())
        ->not->toHaveKey('ActivePartPaymentCampaigns')
        ->not->toHaveKey('PromotedPartPaymentCampaign');
});

// ── OrderRow new fields ────────────────────────────────────────────────────

test('OrderRow discountAmount is set directly in minor units', function (): void {
    $row = new OrderRow(quantity: 1, unitPrice: 9900, vatPercent: 25, discountAmount: 500);

    expect($row->toArray()['DiscountAmount'])->toBe(500);
});

test('OrderRow rowType defaults to Row', function (): void {
    $row = new OrderRow(quantity: 1, unitPrice: 9900, vatPercent: 25);

    expect($row->toArray()['RowType'])->toBe('Row');
});

test('OrderRow rowType can be set to ShippingFee', function (): void {
    $row = new OrderRow(quantity: 1, unitPrice: 4900, vatPercent: 25, rowType: 'ShippingFee');

    expect($row->toArray()['RowType'])->toBe('ShippingFee');
});

test('OrderRow rowNumber and merchantData are set correctly', function (): void {
    $row = new OrderRow(quantity: 1, unitPrice: 9900, vatPercent: 25, rowNumber: 3, merchantData: '{"size":"M"}');

    expect($row->toArray()['RowNumber'])->toBe(3)
        ->and($row->toArray()['MerchantData'])->toBe('{"size":"M"}');
});

// ── CheckoutOrder new optional fields ─────────────────────────────────────

test('CheckoutOrder presetValues appear in payload', function (): void {
    $order = new CheckoutOrder;
    $order->addPresetValue(new PresetValue(typeName: 'EmailAddress', value: 'test@example.com', isReadonly: true));

    expect($order->toArray()['PresetValues'])->toHaveCount(1)
        ->and($order->toArray()['PresetValues'][0]['TypeName'])->toBe('EmailAddress');
});

test('CheckoutOrder identityFlags appear in payload', function (): void {
    $order = new CheckoutOrder;
    $order->identityFlags(new IdentityFlags(hideAnonymous: true));

    expect($order->toArray()['IdentityFlags']['HideAnonymous'])->toBeTrue();
});

test('CheckoutOrder validation appears in payload', function (): void {
    $order = new CheckoutOrder;
    $order->validation(new Validation(minAge: 21));

    expect($order->toArray()['Validation']['MinAge'])->toBe(21);
});

test('CheckoutOrder shippingInformation appears in payload', function (): void {
    $order = new CheckoutOrder;
    $order->shippingInformation(new ShippingInformation(enableShipping: true, weight: 800.0));

    expect($order->toArray()['ShippingInformation']['EnableShipping'])->toBeTrue()
        ->and($order->toArray()['ShippingInformation']['Weight'])->toBe(800.0);
});

test('CheckoutOrder recurring and merchantData are set via constructor', function (): void {
    $order = new CheckoutOrder(merchantData: 'ref:abc123', recurring: true);
    $payload = $order->toArray();

    expect($payload['MerchantData'])->toBe('ref:abc123')
        ->and($payload['Recurring'])->toBeTrue();
});

test('CheckoutOrder fluent recurring and merchantData setters work', function (): void {
    $order = new CheckoutOrder;
    $order->merchantData('ref:abc123')->recurring(true);
    $payload = $order->toArray();

    expect($payload['MerchantData'])->toBe('ref:abc123')
        ->and($payload['Recurring'])->toBeTrue();
});

test('CheckoutOrder constructor accepts presetValues and identityFlags', function (): void {
    $order = new CheckoutOrder(
        presetValues: [new PresetValue(typeName: 'EmailAddress', value: 'test@example.com')],
        identityFlags: new IdentityFlags(hideNotYou: true),
        validation: new Validation(minAge: 18),
    );

    $payload = $order->toArray();

    expect($payload['PresetValues'])->toHaveCount(1)
        ->and($payload['IdentityFlags']['HideNotYou'])->toBeTrue()
        ->and($payload['Validation']['MinAge'])->toBe(18);
});
