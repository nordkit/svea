<?php

declare(strict_types=1);
use Svea\Admin\AdminOrderResponse;
use Svea\Admin\SveaOrderStatus;

test('status returns SveaOrderStatus enum', function () {
    $r = AdminOrderResponse::make(['OrderStatus' => 'Open', 'Actions' => []]);
    expect($r->status())->toBe(SveaOrderStatus::Open);
});

test('canDeliver returns true when action is present', function () {
    $r = AdminOrderResponse::make(['OrderStatus' => 'Open', 'Actions' => ['CanDeliverOrder', 'CanCancelOrder']]);
    expect($r->canDeliver())->toBeTrue()
        ->and($r->canCancel())->toBeTrue()
        ->and($r->canCredit())->toBeFalse()
        ->and($r->hasAction('CanDeliverOrder'))->toBeTrue()
        ->and($r->hasStatus('Open'))->toBeTrue();
});

test('delivery helpers resolve delivery rows', function () {
    $r = AdminOrderResponse::make([
        'OrderStatus' => 'Delivered',
        'Actions' => ['CanCreditOrder'],
        'Deliveries' => [
            [
                'Id' => 456,
                'OrderRows' => [
                    ['OrderRowId' => 101],
                    ['OrderRowId' => 102],
                ],
            ],
        ],
    ]);

    expect($r->deliveries())->toHaveCount(1)
        ->and($r->delivery(456))->toBeArray()
        ->and($r->delivery(999))->toBeNull()
        ->and($r->deliveryRowIds(456))->toBe([101, 102])
        ->and($r->deliveryRowIds(999))->toBe([]);
});
