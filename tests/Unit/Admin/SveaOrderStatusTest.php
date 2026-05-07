<?php

declare(strict_types=1);

use Svea\Admin\SveaOrderStatus;

// ---------------------------------------------------------------------------
// Enum value dataset
// ---------------------------------------------------------------------------

dataset('order_statuses', [
    ['Open',      SveaOrderStatus::Open],
    ['Delivered', SveaOrderStatus::Delivered],
    ['Cancelled', SveaOrderStatus::Cancelled],
    ['Final',     SveaOrderStatus::Final],
]);

test('SveaOrderStatus::from resolves from Svea API string', function (string $value, SveaOrderStatus $expected): void {
    expect(SveaOrderStatus::from($value))->toBe($expected);
})->with('order_statuses');

test('SveaOrderStatus value matches Svea API string', function (string $value, SveaOrderStatus $status): void {
    expect($status->value)->toBe($value);
})->with('order_statuses');

test('SveaOrderStatus::tryFrom returns null for unknown string', function (): void {
    expect(SveaOrderStatus::tryFrom('Unknown'))->toBeNull();
});

test('all 4 SveaOrderStatus cases are present', function (): void {
    expect(SveaOrderStatus::cases())->toHaveCount(4);
});
