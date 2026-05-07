<?php

declare(strict_types=1);

use Svea\Subscriptions\EventType;
use Svea\Subscriptions\Subscription;

// ---------------------------------------------------------------------------
// Fixture representing a full Svea API response for a single subscription.
// ---------------------------------------------------------------------------
/**
 * Return a fixture representing a full Svea API response for a single subscription.
 *
 * @return array<string, mixed>
 */
function subscriptionFixture(): array
{
    return [
        'SubscriptionId' => 'fbb6c74a-cc06-4ab7-100e-08daa861c517',
        'CallbackUri' => 'https://myapp.com/webhooks/svea',
        'Events' => [
            'CheckoutOrder.Created',
            'CheckoutOrder.Delivered',
            'CheckoutOrder.CreditSucceeded',
            'CheckoutOrder.CreditFailed',
        ],
        'Verified' => true,
        'Created' => '2024-06-04T09:49:22.0005285Z',
    ];
}

// ---------------------------------------------------------------------------
// id()
// ---------------------------------------------------------------------------

test('id returns the SubscriptionId from the API response', function () {
    $subscription = Subscription::make(subscriptionFixture());

    expect($subscription->id())->toBe('fbb6c74a-cc06-4ab7-100e-08daa861c517');
});

test('id returns empty string when SubscriptionId is absent', function () {
    expect(Subscription::make([])->id())->toBe('');
});

// ---------------------------------------------------------------------------
// callbackUrl()
// ---------------------------------------------------------------------------

test('callbackUrl returns the CallbackUri from the API response', function () {
    $subscription = Subscription::make(subscriptionFixture());

    expect($subscription->callbackUrl())->toBe('https://myapp.com/webhooks/svea');
});

test('callbackUrl returns empty string when CallbackUri is absent', function () {
    expect(Subscription::make([])->callbackUrl())->toBe('');
});

// ---------------------------------------------------------------------------
// events()
// ---------------------------------------------------------------------------

test('events returns typed EventType array from Events strings', function () {
    $subscription = Subscription::make(subscriptionFixture());

    expect($subscription->events())->toBe([
        EventType::CheckoutOrderCreated,
        EventType::CheckoutOrderDelivered,
        EventType::CheckoutOrderCreditSucceeded,
        EventType::CheckoutOrderCreditFailed,
    ]);
});

test('events returns empty array when Events is absent', function () {
    expect(Subscription::make([])->events())->toBe([]);
});

test('events returns empty array when Events is an empty array', function () {
    expect(Subscription::make(['Events' => []])->events())->toBe([]);
});

// ---------------------------------------------------------------------------
// isVerified()
// ---------------------------------------------------------------------------

test('isVerified returns true when Verified is true', function () {
    expect(Subscription::make(['Verified' => true])->isVerified())->toBeTrue();
});

test('isVerified returns false when Verified is false', function () {
    expect(Subscription::make(['Verified' => false])->isVerified())->toBeFalse();
});

test('isVerified returns false when Verified is absent', function () {
    expect(Subscription::make([])->isVerified())->toBeFalse();
});

// ---------------------------------------------------------------------------
// createdAt()
// ---------------------------------------------------------------------------

test('createdAt returns a DateTimeImmutable when Created is present', function () {
    $subscription = Subscription::make(subscriptionFixture());
    $createdAt = $subscription->createdAt();

    expect($createdAt)->toBeInstanceOf(DateTimeImmutable::class)
        ->and($createdAt->format('Y-m-d'))->toBe('2024-06-04');
});

test('createdAt returns null when Created is absent', function () {
    expect(Subscription::make([])->createdAt())->toBeNull();
});

// ---------------------------------------------------------------------------
// SveaResource base — array access and magic property access
// ---------------------------------------------------------------------------

test('subscription data is accessible via array access', function () {
    $subscription = Subscription::make(subscriptionFixture());

    expect($subscription['SubscriptionId'])->toBe('fbb6c74a-cc06-4ab7-100e-08daa861c517')
        ->and($subscription['Verified'])->toBeTrue();
});

test('subscription data is accessible via magic property access', function () {
    $subscription = Subscription::make(subscriptionFixture());

    expect($subscription->SubscriptionId)->toBe('fbb6c74a-cc06-4ab7-100e-08daa861c517');
});

test('toArray returns the raw API data', function () {
    $data = subscriptionFixture();
    $subscription = Subscription::make($data);

    expect($subscription->toArray())->toBe($data);
});
