<?php

declare(strict_types=1);

use Svea\Admin\AdminOrderResponse;
use Svea\Admin\TaskResponse;
use Svea\Checkout\CheckoutResponse;
use Svea\Laravel\Svea;
use Svea\Laravel\SveaServiceProvider;
use Svea\Laravel\WebhookService;
use Svea\SveaClient;
use Svea\Testing\FakeSveaClient;
use Svea\Testing\SveaFakeAssertions;

// ---------------------------------------------------------------------------
// Test helpers
// ---------------------------------------------------------------------------

/**
 * Boots the Svea service provider in the Orchestra Testbench container.
 */
function bootSveaProvider(): void
{
    app()->register(SveaServiceProvider::class);
    app()['config']->set('svea', [
        'merchant_id' => 'test-merchant',
        'shared_secret' => 'test-secret',
        'environment' => 'test',
        'webhook_secret' => 'whk-secret',
        'max_retries' => 0,
        'timeout' => 5,
    ]);
}

// ---------------------------------------------------------------------------
// SveaServiceProvider — container bindings
// ---------------------------------------------------------------------------

test('service provider binds SveaClient as singleton', function (): void {
    bootSveaProvider();

    $a = app(SveaClient::class);
    $b = app(SveaClient::class);

    expect($a)->toBeInstanceOf(SveaClient::class)
        ->and($a)->toBe($b);
});

test('service provider binds WebhookService', function (): void {
    bootSveaProvider();

    expect(app(WebhookService::class))->toBeInstanceOf(WebhookService::class);
});

test('service provider registers the svea alias', function (): void {
    bootSveaProvider();

    expect(app('svea'))->toBeInstanceOf(SveaClient::class);
});

// ---------------------------------------------------------------------------
// Svea facade — fake() swaps the singleton
// ---------------------------------------------------------------------------

test('Svea::fake returns SveaFakeAssertions', function (): void {
    bootSveaProvider();

    $assertions = Svea::fake();

    expect($assertions)->toBeInstanceOf(SveaFakeAssertions::class);
    expect(Svea::getFacadeRoot())->toBeInstanceOf(FakeSveaClient::class);
});

test('Svea::fake seeds responses correctly', function (): void {
    bootSveaProvider();

    Svea::fake([
        'checkout.create' => CheckoutResponse::make(['OrderId' => 'from-fake', 'Status' => 'Created']),
    ]);

    $result = Svea::checkout()->create(fn ($o) => $o->currency('SEK'));

    expect($result->id())->toBe('from-fake');
});

// ---------------------------------------------------------------------------
// Svea facade — static assertion proxies
// ---------------------------------------------------------------------------

test('Svea::assertCheckoutCreated proxies to fake assertions', function (): void {
    bootSveaProvider();
    Svea::fake();

    Svea::checkout()->create(fn ($o) => $o->currency('SEK'));

    Svea::assertCheckoutCreated();
});

test('Svea::assertDelivered proxies to fake assertions', function (): void {
    bootSveaProvider();
    Svea::fake(['admin.get' => AdminOrderResponse::make(['OrderStatus' => 'Open', 'Actions' => ['CanDeliverOrder']])]);

    Svea::admin()->order('12345678')->deliver();

    Svea::assertDelivered('12345678');
});

test('Svea::assertCancelledOrder proxies to fake assertions', function (): void {
    bootSveaProvider();
    Svea::fake();

    Svea::admin()->order('12345678')->cancel();

    Svea::assertCancelledOrder('12345678');
});

test('Svea::assertTaskPolled proxies to fake assertions', function (): void {
    bootSveaProvider();
    Svea::fake();

    $url = 'https://paymentadminapi.svea.com/api/v1/tasks/fake-99';
    Svea::admin()->task($url);

    Svea::assertTaskPolled($url);
});

test('Svea::assertNothingSent proxies to fake assertions', function (): void {
    bootSveaProvider();
    Svea::fake();

    Svea::assertNothingSent();
});

test('Svea::assertCredited proxies to fake assertions', function (): void {
    bootSveaProvider();
    Svea::fake([
        'admin.credit' => TaskResponse::pending('https://paymentadminapi.svea.com/api/v1/tasks/fake'),
    ]);

    Svea::admin()->order('12345678')->delivery(456)->credit()->rows([101, 102])->send();

    Svea::assertCredited('12345678');
});

test('Svea assertion proxy throws RuntimeException outside of fake context', function (): void {
    bootSveaProvider();

    // No fake() called — facade resolves to real SveaClient
    expect(fn () => Svea::assertNothingSent())
        ->toThrow(RuntimeException::class, 'Svea::fake() to be called first');
});
