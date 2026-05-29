<?php

declare(strict_types=1);

use Svea\Laravel\Commands\SveaSubscriptionAddCommand;
use Svea\Laravel\Commands\SveaSubscriptionGetCommand;
use Svea\Laravel\Commands\SveaSubscriptionListCommand;
use Svea\Laravel\Commands\SveaSubscriptionRemoveCommand;
use Svea\Laravel\Commands\SveaSubscriptionUpdateCommand;
use Svea\Laravel\Commands\SveaSubscriptionVerifyCommand;
use Svea\Laravel\Svea;
use Svea\Laravel\SveaServiceProvider;
use Svea\Subscriptions\EventType;
use Svea\Subscriptions\Subscription;

beforeEach(function (): void {
    app()->register(SveaServiceProvider::class);
    app()['config']->set('svea', [
        'merchant_id' => 'test-merchant',
        'shared_secret' => 'test-secret',
        'environment' => 'test',
        'webhook_secret' => 'whk-secret',
        'max_retries' => 0,
        'timeout' => 5,
    ]);
});

// ---------------------------------------------------------------------------
// svea:subscription:list
// ---------------------------------------------------------------------------

test('svea:subscription:list shows subscriptions table', function (): void {
    Svea::fake([
        'subscriptions.list' => [
            Subscription::make([
                'SubscriptionId' => 'sub-1',
                'CallbackUri' => 'https://myapp.com/webhooks/svea',
                'Events' => ['CheckoutOrder.Created'],
                'Verified' => true,
            ]),
        ],
    ]);

    $this->artisan(SveaSubscriptionListCommand::class)
        ->expectsOutputToContain('sub-1')
        ->assertExitCode(0);
});

test('svea:subscription:list shows message when no subscriptions exist', function (): void {
    Svea::fake(['subscriptions.list' => []]);

    $this->artisan(SveaSubscriptionListCommand::class)
        ->expectsOutputToContain('No subscriptions registered.')
        ->assertExitCode(0);
});

// ---------------------------------------------------------------------------
// svea:subscription:add
// ---------------------------------------------------------------------------

test('svea:subscription:add registers a subscription and verifies it', function (): void {
    $assertions = Svea::fake([
        'subscriptions.add' => Subscription::make([
            'SubscriptionId' => 'new-sub',
            'CallbackUri' => 'https://myapp.com/webhooks/svea',
            'Events' => ['CheckoutOrder.Created'],
            'Verified' => false,
        ]),
    ]);

    $this->artisan(SveaSubscriptionAddCommand::class, [
        '--url' => 'https://myapp.com/webhooks/svea',
        '--events' => 'CheckoutOrder.Created',
    ])
        ->expectsOutputToContain('new-sub')
        ->expectsOutputToContain('Verification Ping sent')
        ->assertExitCode(0);

    $assertions->assertSubscriptionAdded('https://myapp.com/webhooks/svea');
    $assertions->assertSubscriptionVerified('new-sub');
});

test('svea:subscription:add skips verification when --no-verify is passed', function (): void {
    $assertions = Svea::fake([
        'subscriptions.add' => Subscription::make([
            'SubscriptionId' => 'new-sub',
            'CallbackUri' => 'https://myapp.com/webhooks/svea',
            'Events' => [],
            'Verified' => false,
        ]),
    ]);

    $this->artisan(SveaSubscriptionAddCommand::class, [
        '--url' => 'https://myapp.com/webhooks/svea',
        '--no-verify' => true,
    ])
        ->doesntExpectOutputToContain('Verification Ping')
        ->assertExitCode(0);

    $assertions->assertSubscriptionAdded('https://myapp.com/webhooks/svea');
});

test('svea:subscription:add defaults to all event types when --events is omitted', function (): void {
    $assertions = Svea::fake([
        'subscriptions.add' => Subscription::make([
            'SubscriptionId' => 'all-events-sub',
            'CallbackUri' => 'https://myapp.com/webhooks/svea',
            'Events' => array_map(fn ($e) => $e->value, EventType::cases()),
            'Verified' => false,
        ]),
    ]);

    $this->artisan(SveaSubscriptionAddCommand::class, [
        '--url' => 'https://myapp.com/webhooks/svea',
        '--no-verify' => true,
    ])->assertExitCode(0);

    $assertions->assertSubscriptionAdded('https://myapp.com/webhooks/svea');
});

// ---------------------------------------------------------------------------
// svea:subscription:get
// ---------------------------------------------------------------------------

test('svea:subscription:get displays subscription details', function (): void {
    Svea::fake([
        'subscriptions.get' => Subscription::make([
            'SubscriptionId' => 'sub-abc',
            'CallbackUri' => 'https://myapp.com/webhooks/svea',
            'Events' => ['CheckoutOrder.Created', 'CheckoutOrder.Delivered'],
            'Verified' => true,
            'Created' => '2024-06-04T09:49:22Z',
        ]),
    ]);

    $this->artisan(SveaSubscriptionGetCommand::class, ['id' => 'sub-abc'])
        ->expectsOutputToContain('sub-abc')
        ->expectsOutputToContain('https://myapp.com/webhooks/svea')
        ->expectsOutputToContain('CheckoutOrder.Created')
        ->expectsOutputToContain('2024-06-04')
        ->assertExitCode(0);
});

// ---------------------------------------------------------------------------
// svea:subscription:update
// ---------------------------------------------------------------------------

test('svea:subscription:update updates url and events', function (): void {
    $assertions = Svea::fake([
        'subscriptions.get' => Subscription::make([
            'SubscriptionId' => 'sub-xyz',
            'CallbackUri' => 'https://old.myapp.com/webhooks',
            'Events' => ['CheckoutOrder.Created'],
            'Verified' => true,
        ]),
        'subscriptions.update' => Subscription::make([
            'SubscriptionId' => 'sub-xyz',
            'CallbackUri' => 'https://new.myapp.com/webhooks',
            'Events' => ['CheckoutOrder.Delivered'],
            'Verified' => false,
        ]),
    ]);

    $this->artisan(SveaSubscriptionUpdateCommand::class, [
        'id' => 'sub-xyz',
        '--url' => 'https://new.myapp.com/webhooks',
        '--events' => 'CheckoutOrder.Delivered',
    ])
        ->expectsOutputToContain('https://new.myapp.com/webhooks')
        ->expectsOutputToContain('re-verify')
        ->assertExitCode(0);

    $assertions->assertSubscriptionFetched('sub-xyz');
    $assertions->assertSubscriptionUpdated('sub-xyz');
});

test('svea:subscription:update sends verify ping when --verify is passed', function (): void {
    $assertions = Svea::fake([
        'subscriptions.get' => Subscription::make([
            'SubscriptionId' => 'sub-xyz',
            'CallbackUri' => 'https://myapp.com/webhooks',
            'Events' => ['CheckoutOrder.Created'],
            'Verified' => true,
        ]),
        'subscriptions.update' => Subscription::make([
            'SubscriptionId' => 'sub-xyz',
            'CallbackUri' => 'https://myapp.com/webhooks',
            'Events' => ['CheckoutOrder.Created'],
            'Verified' => true,
        ]),
    ]);

    $this->artisan(SveaSubscriptionUpdateCommand::class, [
        'id' => 'sub-xyz',
        '--verify' => true,
    ])
        ->expectsOutputToContain('Verification Ping sent')
        ->assertExitCode(0);

    $assertions->assertSubscriptionVerified('sub-xyz');
});

// ---------------------------------------------------------------------------
// svea:subscription:remove
// ---------------------------------------------------------------------------

test('svea:subscription:remove removes subscription with --force', function (): void {
    $assertions = Svea::fake();

    $this->artisan(SveaSubscriptionRemoveCommand::class, [
        'id' => 'sub-to-delete',
        '--force' => true,
    ])
        ->expectsOutputToContain('removed')
        ->assertExitCode(0);

    $assertions->assertSubscriptionRemoved('sub-to-delete');
});

test('svea:subscription:remove aborts when confirmation is declined', function (): void {
    $assertions = Svea::fake();

    $this->artisan(SveaSubscriptionRemoveCommand::class, ['id' => 'sub-to-delete'])
        ->expectsConfirmation('Remove subscription [sub-to-delete]? This cannot be undone.', 'no')
        ->expectsOutputToContain('Aborted')
        ->assertExitCode(0);

    $assertions->assertNothingSent();
});

// ---------------------------------------------------------------------------
// svea:subscription:verify
// ---------------------------------------------------------------------------

test('svea:subscription:verify sends ping to the subscription', function (): void {
    $assertions = Svea::fake();

    $this->artisan(SveaSubscriptionVerifyCommand::class, ['id' => 'sub-to-verify'])
        ->expectsOutputToContain('Ping sent')
        ->assertExitCode(0);

    $assertions->assertSubscriptionVerified('sub-to-verify');
});
