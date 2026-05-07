<?php

declare(strict_types=1);

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Svea\Admin\AdminDeliveryRequest;
use Svea\Transport\SveaConnector;

test('credit amount patches with CreditedAmount payload and returns task reference from CreditId', function () {
    $history = [];
    $mock = new MockHandler([
        new Response(200, [], '{"CreditId":"crd_123abc456def","ResultCodeName":"Success"}'),
    ]);
    $stack = HandlerStack::create($mock);
    $stack->push(Middleware::history($history));
    $connector = new SveaConnector(['merchant_id' => 'merchant', 'shared_secret' => 'secret'], 'https://paymentadminapi.svea.com', $stack);

    $response = (new AdminDeliveryRequest($connector, '12345678', 456))->creditAmount(9900);

    expect($response->reference())->toBe('crd_123abc456def')
        ->and($history[0]['request']->getMethod())->toBe('PATCH')
        ->and((string) $history[0]['request']->getBody())->toBe('{"CreditedAmount":9900}');
});
