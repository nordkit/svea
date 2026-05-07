<?php

declare(strict_types=1);

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Svea\Admin\AdminOrderRow;
use Svea\Admin\CreditRequest;
use Svea\Transport\SveaConnector;

/**
 * Create a SveaConnector backed by a MockHandler for the Admin API.
 *
 * @param  Response[]  $responses  Queued HTTP responses.
 * @param  array<int, mixed>  $history  Reference array populated with request/response pairs.
 */
function creditConnector(array $responses, array &$history = []): SveaConnector
{
    $mock = new MockHandler($responses);
    $stack = HandlerStack::create($mock);
    $stack->push(Middleware::history($history));

    return new SveaConnector(['merchant_id' => 'merchant', 'shared_secret' => 'secret'], 'https://paymentadminapi.svea.com', $stack);
}

test('rows includes row crediting options in payload', function () {
    $history = [];
    $connector = creditConnector(
        responses: [new Response(202, ['Location' => ['https://paymentadminapi.svea.com/api/v1/tasks/credit-2']], '{}')],
        history: $history,
    );
    $credit = new CreditRequest($connector, '12345678', 456);

    $response = $credit->rows([101], [['orderRowId' => 101, 'quantity' => 50]])->send();

    expect($response->reference())->toBe('https://paymentadminapi.svea.com/api/v1/tasks/credit-2')
        ->and((string) $history[0]['request']->getBody())->toBe(
            '{"OrderRowIds":[101],"RowCreditingOptions":[{"OrderRowId":101,"Quantity":50}]}'
        );
});

test('new row uses admin order row payload shape', function () {
    $history = [];
    $connector = creditConnector(
        responses: [new Response(202, ['Location' => ['https://paymentadminapi.svea.com/api/v1/tasks/credit-3']], '{}')],
        history: $history,
    );
    $credit = new CreditRequest($connector, '12345678', 456);

    $credit->newRow(function (AdminOrderRow $row) {
        $row->name('Restocking fee')
            ->quantity(100)
            ->unitPrice(5000)
            ->vatPercent(2500)
            ->sku('FEE-001');
    })->send();

    expect((string) $history[0]['request']->getBody())->toBe(
        '{"NewCreditOrderRows":[{"RowType":"Row","Name":"Restocking fee","Quantity":100,"UnitPrice":5000,"VatPercent":2500,"ArticleNumber":"FEE-001"}]}'
    );
});
