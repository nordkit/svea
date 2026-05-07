<?php

declare(strict_types=1);

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Svea\Admin\AdminOrderRequest;
use Svea\Admin\AdminOrderRow;
use Svea\Transport\SveaConnector;

/**
 * Create a SveaConnector backed by a MockHandler for the Admin API.
 *
 * @param  Response[]  $responses  Queued HTTP responses.
 * @param  array<int, mixed>  $history  Reference array populated with request/response pairs.
 */
function adminConnector(array $responses, array &$history = []): SveaConnector
{
    $mock = new MockHandler($responses);
    $stack = HandlerStack::create($mock);
    $stack->push(Middleware::history($history));

    return new SveaConnector(['merchant_id' => 'merchant', 'shared_secret' => 'secret'], 'https://paymentadminapi.svea.com', $stack);
}

test('deliver includes row delivery options payload', function () {
    $history = [];
    $connector = adminConnector(
        responses: [new Response(202, ['Location' => ['https://paymentadminapi.svea.com/api/v1/tasks/123']], '{}')],
        history: $history,
    );
    $request = new AdminOrderRequest($connector, '12345678');

    $task = $request->withIdempotencyKey('idem-1')->deliver(
        rows: [101],
        rowDeliveryOptions: [['orderRowId' => 101, 'quantity' => 50]],
    );

    expect($task->taskReference())->toBe('https://paymentadminapi.svea.com/api/v1/tasks/123')
        ->and($history)->toHaveCount(1)
        ->and((string) $history[0]['request']->getBody())->toBe(
            '{"OrderRowIds":[101],"RowDeliveryOptions":[{"OrderRowId":101,"Quantity":50}]}'
        )
        ->and($history[0]['request']->getHeaderLine('Idempotency-Key'))->toBe('idem-1');
});

test('add order row returns ids from response', function () {
    $history = [];
    $connector = adminConnector(
        responses: [new Response(201, ['Location' => ['https://paymentadminapi.svea.com/api/v1/tasks/999']], '{"OrderRowId":77}')],
        history: $history,
    );
    $request = new AdminOrderRequest($connector, '12345678');

    $response = $request->addOrderRow(function (AdminOrderRow $row) {
        $row->name('Warranty')
            ->quantity(100)
            ->unitPrice(19900)
            ->vatPercent(2500);
    });

    expect($response)->toBe([
        'order_row_id' => 77,
        'task_reference' => 'https://paymentadminapi.svea.com/api/v1/tasks/999',
    ])->and((string) $history[0]['request']->getBody())->toBe(
        '{"OrderRow":{"RowType":"Row","Name":"Warranty","Quantity":100,"UnitPrice":19900,"VatPercent":2500}}'
    );
});

test('update and replace order rows send expected payloads', function () {
    $history = [];
    $connector = adminConnector(
        responses: [new Response(200, [], '{}'), new Response(200, [], '{}')],
        history: $history,
    );
    $request = new AdminOrderRequest($connector, '12345678');

    $request->updateOrderRow(5, function (AdminOrderRow $row) {
        $row->name('Updated')->unitPrice(8900);
    });
    $request->replaceOrderRows(
        function (AdminOrderRow $row) {
            $row->name('Product A')->quantity(100)->unitPrice(9900)->vatPercent(2500);
        },
        function (AdminOrderRow $row) {
            $row->name('Shipping')->quantity(100)->unitPrice(4900)->vatPercent(2500)->rowType('ShippingFee');
        },
    );

    expect($history)->toHaveCount(2)
        ->and((string) $history[0]['request']->getBody())->toBe(
            '{"OrderRow":{"RowType":"Row","Name":"Updated","UnitPrice":8900}}'
        )
        ->and((string) $history[1]['request']->getBody())->toBe(
            '{"OrderRows":[{"RowType":"Row","Name":"Product A","Quantity":100,"UnitPrice":9900,"VatPercent":2500},{"RowType":"ShippingFee","Name":"Shipping","Quantity":100,"UnitPrice":4900,"VatPercent":2500}]}'
        );
});

// ── Cancel ──────────────────────────────────────────────────────────────────

test('cancel sends PATCH with IsCancelled true', function (): void {
    $history = [];
    $connector = adminConnector(
        responses: [new Response(202, [], '')],
        history: $history,
    );

    (new AdminOrderRequest($connector, '12345678'))->cancel();

    expect($history[0]['request']->getMethod())->toBe('PATCH')
        ->and((string) $history[0]['request']->getUri()->getPath())->toBe('/api/v1/orders/12345678')
        ->and((string) $history[0]['request']->getBody())->toBe('{"IsCancelled":true}');
});

test('cancelAmount sends PATCH with CancelledAmount', function (): void {
    $history = [];
    $connector = adminConnector(
        responses: [new Response(202, [], '')],
        history: $history,
    );

    (new AdminOrderRequest($connector, '12345678'))->cancelAmount(29900);

    expect($history[0]['request']->getMethod())->toBe('PATCH')
        ->and((string) $history[0]['request']->getUri()->getPath())->toBe('/api/v1/orders/12345678')
        ->and((string) $history[0]['request']->getBody())->toBe('{"CancelledAmount":29900}');
});

test('cancelRow sends PATCH to cancelOrderRows endpoint with OrderRowIds', function (): void {
    $history = [];
    $connector = adminConnector(
        responses: [new Response(204, [], '')],
        history: $history,
    );

    (new AdminOrderRequest($connector, '12345678'))->cancelRow(42);

    expect($history[0]['request']->getMethod())->toBe('PATCH')
        ->and((string) $history[0]['request']->getUri()->getPath())->toBe('/api/v1/orders/12345678/rows/cancelOrderRows/')
        ->and((string) $history[0]['request']->getBody())->toBe('{"OrderRowIds":[42]}');
});

// ── AdminOrderRow ───────────────────────────────────────────────────────────

test('AdminOrderRow constructor style produces identical output to fluent style', function (): void {
    $fluent = AdminOrderRow::make();
    $fluent->name('T-Shirt Black M')
        ->quantity(100)
        ->unitPrice(29900)
        ->sku('TSHIRT-BLK-M')
        ->discountPercent(1000)
        ->vatPercent(2500)
        ->unit('st')
        ->temporaryReference('ref-42')
        ->merchantData('{"color":"black"}');

    $constructor = new AdminOrderRow(
        name: 'T-Shirt Black M',
        quantity: 100,
        unitPrice: 29900,
        sku: 'TSHIRT-BLK-M',
        discountPercent: 1000,
        vatPercent: 2500,
        unit: 'st',
        temporaryReference: 'ref-42',
        merchantData: '{"color":"black"}',
    );

    expect($constructor->toArray())->toEqual($fluent->toArray());
});

test('AdminOrderRow constructor sets RowType to Row by default', function (): void {
    $row = new AdminOrderRow(name: 'Product', quantity: 100, unitPrice: 9900);

    expect($row->toArray()['RowType'])->toBe('Row');
});

test('AdminOrderRow constructor accepts custom rowType', function (): void {
    $row = new AdminOrderRow(name: 'Shipping', quantity: 100, unitPrice: 4900, rowType: 'ShippingFee');

    expect($row->toArray()['RowType'])->toBe('ShippingFee');
});

test('AdminOrderRow constructor omits optional fields when not provided', function (): void {
    $row = new AdminOrderRow(name: 'Widget', quantity: 100, unitPrice: 9900);

    expect($row->toArray())
        ->toBe(['RowType' => 'Row', 'Name' => 'Widget', 'Quantity' => 100, 'UnitPrice' => 9900]);
});
