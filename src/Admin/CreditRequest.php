<?php

declare(strict_types=1);

namespace Svea\Admin;

use Svea\Exceptions\SveaApiException;
use Svea\Transport\SveaConnector;

/**
 * Fluent builder for credit (refund) requests on a specific delivery.
 *
 * Obtained via {@see AdminDeliveryRequest::credit()} — never constructed directly.
 *
 * Three crediting strategies are supported:
 * - `rows()` — credit specific order rows by ID, optionally with partial quantities
 * - `newRow()` — credit a new row not present in the original order (e.g. return fee)
 * - Both can be combined in a single `send()` call
 *
 * Example:
 * ```php
 * // Credit specific rows
 * $task = Svea::admin()->order('12345678')->delivery(456)
 *     ->credit()->rows([101, 102])->send();
 *
 * // Credit a new row (e.g. restocking fee)
 * $task = Svea::admin()->order('12345678')->delivery(456)->credit()
 *     ->newRow(fn (AdminOrderRow $r) => $r->name('Return fee')->unitPrice(5000)->quantity(1)->vatPercent(25))
 *     ->send();
 * ```
 */
class CreditRequest
{
    /** @var int[] */
    private array $rowIds = [];

    /** @var array<int, array{orderRowId: int, quantity: int}>|null */
    private ?array $rowCreditingOptions = null;

    /** @var array<int, array<string, mixed>> */
    private array $newRows = [];

    /**
     * @param  SveaConnector  $connector  Transport layer for this API surface.
     * @param  string  $orderId  The Svea order identifier.
     * @param  int  $deliveryId  The delivery identifier.
     */
    public function __construct(
        private readonly SveaConnector $connector,
        private readonly string $orderId,
        private readonly int $deliveryId,
    ) {}

    /**
     * Specify the order row IDs to credit.
     *
     * Optionally pass per-row crediting options to credit partial quantities.
     *
     * @param  int[]  $rowIds  Row IDs to credit.
     * @param  array<int, array{orderRowId: int, quantity: int}>|null  $rowCreditingOptions  Per-row partial credit options.
     */
    public function rows(array $rowIds, ?array $rowCreditingOptions = null): static
    {
        $this->rowIds = $rowIds;
        $this->rowCreditingOptions = $rowCreditingOptions;

        return $this;
    }

    /**
     * Add a new credit row (not present in the original order).
     *
     * Can be called multiple times to add several new rows. The callback
     * receives an {@see AdminOrderRow} builder to populate.
     *
     * @param  callable(AdminOrderRow): void  $callback
     */
    public function newRow(callable $callback): static
    {
        $row = new AdminOrderRow;
        $callback($row);
        $this->newRows[] = $row->toArray();

        return $this;
    }

    /**
     * Submit the credit request to the Svea Admin API.
     *
     * Returns a {@see TaskResponse} to poll for completion status.
     *
     * @throws SveaApiException On any non-2xx response.
     */
    public function send(): TaskResponse
    {
        $payload = [];
        if (! empty($this->rowIds)) {
            $payload['OrderRowIds'] = $this->rowIds;
        }
        if ($this->rowCreditingOptions !== null && $this->rowCreditingOptions !== []) {
            $payload['RowCreditingOptions'] = array_map(
                fn (array $option): array => [
                    'OrderRowId' => (int) $option['orderRowId'],
                    'Quantity' => (int) $option['quantity'],
                ],
                $this->rowCreditingOptions
            );
        }
        if (! empty($this->newRows)) {
            $payload['NewCreditOrderRows'] = $this->newRows;
        }
        $response = $this->connector->post(
            "api/v1/orders/{$this->orderId}/deliveries/{$this->deliveryId}/credits",
            $payload
        );
        $reference = $response->json['Reference']
            ?? ($response->headers['Location'][0] ?? '');

        return TaskResponse::pending((string) $reference)->withLastResponse($response);
    }
}
