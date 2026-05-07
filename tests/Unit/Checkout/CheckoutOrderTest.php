<?php

declare(strict_types=1);

use Svea\Checkout\Cart;
use Svea\Checkout\CheckoutOrder;
use Svea\Checkout\MerchantSettings;
use Svea\Checkout\OrderRow;

// ── helpers ────────────────────────────────────────────────────────────────

function merchantSettings(): MerchantSettings
{
    return new MerchantSettings(
        pushUri: 'https://example.com/push',
        termsUri: 'https://example.com/terms',
        confirmationUri: 'https://example.com/confirmed',
        checkoutUri: 'https://example.com/checkout',
    );
}

// ── CheckoutOrder ──────────────────────────────────────────────────────────

test('builds correct payload', function (): void {
    $order = new CheckoutOrder;
    $order->currency('SEK')
        ->locale('sv-SE')
        ->countryCode('SE')
        ->clientOrderNumber('ORD-123')
        ->merchantSettings(merchantSettings())
        ->addRow(fn (OrderRow $row) => $row->name('Product')->quantity(100)->unitPrice(9900)->sku('PROD-1')->vatPercent(2500));
    $payload = $order->toArray();
    expect($payload['Currency'])->toBe('SEK')
        ->and($payload['Locale'])->toBe('sv-SE')
        ->and($payload['ClientOrderNumber'])->toBe('ORD-123')
        ->and($payload['MerchantSettings']['PushUri'])->toBe('https://example.com/push')
        ->and($payload['Cart']['Items'])->toHaveCount(1)
        ->and($payload['Cart']['Items'][0]['ArticleNumber'])->toBe('PROD-1');
});

test('MerchantSettings object populates all four required URIs', function (): void {
    $order = new CheckoutOrder;
    $order->merchantSettings(merchantSettings());

    $settings = $order->toArray()['MerchantSettings'];

    expect($settings['PushUri'])->toBe('https://example.com/push')
        ->and($settings['TermsUri'])->toBe('https://example.com/terms')
        ->and($settings['CheckoutUri'])->toBe('https://example.com/checkout')
        ->and($settings['ConfirmationUri'])->toBe('https://example.com/confirmed');
});

test('legacy individual URI setters still work', function (): void {
    $order = new CheckoutOrder;
    $order->pushUri('https://example.com/push')
        ->confirmationUri('https://example.com/confirmed')
        ->termsUri('https://example.com/terms')
        ->checkoutUri('https://example.com/checkout');

    $settings = $order->toArray()['MerchantSettings'];

    expect($settings['PushUri'])->toBe('https://example.com/push')
        ->and($settings['TermsUri'])->toBe('https://example.com/terms')
        ->and($settings['CheckoutUri'])->toBe('https://example.com/checkout')
        ->and($settings['ConfirmationUri'])->toBe('https://example.com/confirmed');
});

test('multiple rows accumulate in Cart.Items', function (): void {
    $order = new CheckoutOrder;
    $order->addRow(fn (OrderRow $row) => $row->name('Item A')->quantity(100)->unitPrice(1000)->sku('A')->vatPercent(2500))
        ->addRow(fn (OrderRow $row) => $row->name('Item B')->quantity(200)->unitPrice(2000)->sku('B')->vatPercent(2500));

    expect($order->toArray()['Cart']['Items'])->toHaveCount(2);
});

test('OrderRow passes quantity and vatPercent as minor units', function (): void {
    $order = new CheckoutOrder;
    $order->addRow(fn (OrderRow $row) => $row->name('X')->quantity(300)->unitPrice(9900)->sku('X')->vatPercent(2500));

    $item = $order->toArray()['Cart']['Items'][0];

    expect($item['Quantity'])->toBe(300)
        ->and($item['VatPercent'])->toBe(2500);
});

test('OrderRow passes discountPercent as minor units', function (): void {
    $order = new CheckoutOrder;
    $order->addRow(fn (OrderRow $row) => $row->name('X')->quantity(100)->unitPrice(9900)->sku('X')->vatPercent(2500)->discountPercent(1000));

    $item = $order->toArray()['Cart']['Items'][0];

    expect($item['DiscountPercent'])->toBe(1000);
});

test('clientOrderNumber and countryCode are set in payload', function (): void {
    $order = new CheckoutOrder;
    $order->clientOrderNumber('ORDER-999')->countryCode('NO');

    $payload = $order->toArray();

    expect($payload['ClientOrderNumber'])->toBe('ORDER-999')
        ->and($payload['CountryCode'])->toBe('NO');
});

test('Cart object passed to constructor appears in toArray', function (): void {
    $cart = new Cart([
        new OrderRow(name: 'Product 1', quantity: 100, unitPrice: 9900, vatPercent: 2500, sku: 'SKU-1'),
        new OrderRow(name: 'Product 2', quantity: 200, unitPrice: 4900, vatPercent: 2500, sku: 'SKU-2'),
    ]);

    $order = new CheckoutOrder(cart: $cart);

    $items = $order->toArray()['Cart']['Items'];

    expect($items)->toHaveCount(2)
        ->and($items[0]['ArticleNumber'])->toBe('SKU-1')
        ->and($items[1]['ArticleNumber'])->toBe('SKU-2');
});

test('cart() fluent setter replaces previously added rows', function (): void {
    $order = new CheckoutOrder;
    $order->addRow(fn (OrderRow $row) => $row->name('Old Product')->quantity(100)->unitPrice(100)->sku('OLD')->vatPercent(2500));
    $order->cart(new Cart([new OrderRow(name: 'New Product', quantity: 100, unitPrice: 9900, vatPercent: 2500, sku: 'NEW')]));

    $items = $order->toArray()['Cart']['Items'];

    expect($items)->toHaveCount(1)
        ->and($items[0]['ArticleNumber'])->toBe('NEW');
});

test('constructor style produces identical payload to fluent style', function (): void {
    $settings = merchantSettings();

    $fluent = new CheckoutOrder;
    $fluent->currency('SEK')
        ->countryCode('SE')
        ->clientOrderNumber('ORD-456')
        ->locale('sv-SE')
        ->merchantSettings($settings)
        ->addRow(fn (OrderRow $row) => $row->name('Product 1')->quantity(100)->unitPrice(9900)->sku('SKU-1')->vatPercent(2500));

    $constructor = new CheckoutOrder(
        currency: 'SEK',
        countryCode: 'SE',
        clientOrderNumber: 'ORD-456',
        locale: 'sv-SE',
        merchantSettings: $settings,
        cart: new Cart([new OrderRow(name: 'Product 1', quantity: 100, unitPrice: 9900, vatPercent: 2500, sku: 'SKU-1')]),
    );

    expect($constructor->toArray())->toEqual($fluent->toArray());
});

test('constructor omits keys for null params', function (): void {
    $order = new CheckoutOrder(currency: 'EUR', countryCode: 'DE');
    $payload = $order->toArray();

    expect($payload['Currency'])->toBe('EUR')
        ->and($payload)->not->toHaveKey('Locale')
        ->and($payload)->not->toHaveKey('ClientOrderNumber')
        ->and($payload['MerchantSettings'])->toBeEmpty();
});

// ── MerchantSettings ───────────────────────────────────────────────────────

test('MerchantSettings includes optional validation callback URI when set', function (): void {
    $settings = new MerchantSettings(
        pushUri: 'https://example.com/push',
        termsUri: 'https://example.com/terms',
        confirmationUri: 'https://example.com/confirmed',
        checkoutUri: 'https://example.com/checkout',
        checkoutValidationCallbackUri: 'https://example.com/validate/{checkout.order.uri}',
    );

    expect($settings->toArray()['CheckoutValidationCallBackUri'])
        ->toBe('https://example.com/validate/{checkout.order.uri}');
});

test('MerchantSettings omits validation callback URI when not set', function (): void {
    expect(merchantSettings()->toArray())->not->toHaveKey('CheckoutValidationCallBackUri');
});

// ── Cart ───────────────────────────────────────────────────────────────────

test('Cart constructor accepts OrderRow instances', function (): void {
    $cart = new Cart([
        new OrderRow(name: 'Product A', quantity: 100, unitPrice: 9900, vatPercent: 2500, sku: 'A'),
    ]);

    expect($cart->toArray()['Items'])->toHaveCount(1)
        ->and($cart->toArray()['Items'][0]['ArticleNumber'])->toBe('A');
});

test('Cart addRow callback appends rows', function (): void {
    $cart = new Cart;
    $cart->addRow(fn (OrderRow $r) => $r->name('Product A')->quantity(100)->unitPrice(1000)->sku('A')->vatPercent(2500))
        ->addRow(fn (OrderRow $r) => $r->name('Product B')->quantity(100)->unitPrice(2000)->sku('B')->vatPercent(2500));

    expect($cart->toArray()['Items'])->toHaveCount(2);
});

// ── OrderRow ───────────────────────────────────────────────────────────────

test('OrderRow constructor style produces identical output to fluent style', function (): void {
    $fluent = OrderRow::make();
    $fluent->name('Product')
        ->quantity(200)
        ->unitPrice(9900)
        ->sku('PROD-1')
        ->vatPercent(2500)
        ->discountPercent(1000)
        ->unit('st')
        ->temporaryReference('42');

    $constructor = new OrderRow(
        name: 'Product',
        quantity: 200,
        unitPrice: 9900,
        sku: 'PROD-1',
        discountPercent: 1000,
        vatPercent: 2500,
        unit: 'st',
        temporaryReference: '42',
    );

    expect($constructor->toArray())->toEqual($fluent->toArray());
});
