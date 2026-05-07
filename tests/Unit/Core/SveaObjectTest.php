<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\Response;
use Svea\Checkout\CheckoutResponse;
use Svea\Transport\SveaResponse;

test('make creates instance with data', function () {
    $obj = CheckoutResponse::make(['OrderId' => '12345678', 'Status' => 'Created']);
    expect($obj->id())->toBe('12345678')
        ->and($obj->status())->toBe('Created')
        ->and($obj->successful())->toBeTrue();
});

test('supports array read access', function () {
    $obj = CheckoutResponse::make(['OrderId' => '99']);
    expect($obj['OrderId'])->toBe('99');
});

test('offsetExists returns true for present keys', function () {
    $obj = CheckoutResponse::make(['OrderId' => '1']);
    expect(isset($obj['OrderId']))->toBeTrue()
        ->and(isset($obj['Missing']))->toBeFalse();
});

test('supports magic property read access', function () {
    $obj = CheckoutResponse::make(['Status' => 'Final']);
    expect($obj->Status)->toBe('Final');
});

test('__isset returns true for present keys', function () {
    $obj = CheckoutResponse::make(['OrderId' => '1']);
    expect(isset($obj->OrderId))->toBeTrue()
        ->and(isset($obj->Missing))->toBeFalse();
});

test('toArray returns underlying data', function () {
    $data = ['OrderId' => '42', 'Status' => 'Created'];
    $obj = CheckoutResponse::make($data);
    expect($obj->toArray())->toBe($data);
});

test('withLastResponse attaches raw response', function () {
    $psr = new Response(200, [], '{"OrderId":"1"}');
    $sr = new SveaResponse($psr);
    $obj = CheckoutResponse::make(['OrderId' => '1'])->withLastResponse($sr);
    expect($obj->getLastResponse())->toBeInstanceOf(SveaResponse::class)
        ->and($obj->getLastResponse()->statusCode)->toBe(200);
});

test('offsetSet throws BadMethodCallException', function () {
    $obj = CheckoutResponse::make(['OrderId' => '1']);
    $obj['OrderId'] = 'modified';
})->throws(BadMethodCallException::class, 'read-only');

test('offsetUnset throws BadMethodCallException', function () {
    $obj = CheckoutResponse::make(['OrderId' => '1']);
    unset($obj['OrderId']);
})->throws(BadMethodCallException::class, 'read-only');
