<?php

declare(strict_types=1);

use Svea\Subscriptions\EventType;

// ---------------------------------------------------------------------------
// Exhaustive dataset covering all 10 documented EventType cases.
// Each entry is [enum case, expected string value from Svea API docs].
// ---------------------------------------------------------------------------
dataset('event types', [
    'CheckoutOrder.Created' => [EventType::CheckoutOrderCreated,              'CheckoutOrder.Created'],
    'CheckoutOrder.Updated' => [EventType::CheckoutOrderUpdated,              'CheckoutOrder.Updated'],
    'CheckoutOrder.Delivered' => [EventType::CheckoutOrderDelivered,            'CheckoutOrder.Delivered'],
    'CheckoutOrder.CreditSucceeded' => [EventType::CheckoutOrderCreditSucceeded,      'CheckoutOrder.CreditSucceeded'],
    'CheckoutOrder.CreditFailed' => [EventType::CheckoutOrderCreditFailed,         'CheckoutOrder.CreditFailed'],
    'CheckoutOrder.Closed' => [EventType::CheckoutOrderClosed,               'CheckoutOrder.Closed'],
    'CheckoutOrder.PendingStatusReleased' => [EventType::CheckoutOrderPendingStatusReleased, 'CheckoutOrder.PendingStatusReleased'],
    'StandaloneOrder.PendingStatusReleased' => [EventType::StandaloneOrderPendingStatusReleased, 'StandaloneOrder.PendingStatusReleased'],
    'StandaloneOrder.Closed' => [EventType::StandaloneOrderClosed,             'StandaloneOrder.Closed'],
    'Ping' => [EventType::Ping,                              'Ping'],
]);

test('EventType enum value matches Svea API string', function (EventType $case, string $expected) {
    expect($case->value)->toBe($expected);
})->with('event types');

test('EventType::from resolves from Svea API string', function (EventType $case, string $value) {
    expect(EventType::from($value))->toBe($case);
})->with('event types');

test('EventType::tryFrom returns null for unknown string', function () {
    expect(EventType::tryFrom('Unknown.Event'))->toBeNull();
});

test('all 10 EventType cases are present', function () {
    expect(EventType::cases())->toHaveCount(10);
});
