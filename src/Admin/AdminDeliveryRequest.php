<?php

declare(strict_types=1);

namespace Svea\Admin;

use Svea\Exceptions\SveaApiException;
use Svea\Transport\SveaConnector;

/**
 * Fluent request builder scoped to a specific delivery within a Svea Admin order.
 *
 * Obtained via {@see AdminOrderRequest::delivery()} — never constructed directly.
 *
 * Provides two credit (refund) entry points:
 * - `credit()` — returns a {@see CreditRequest} builder for row-based or new-row credits
 * - `creditAmount()` — directly credits a fixed amount (minor currency units)
 *
 * Example:
 * ```php
 * // Credit specific rows
 * $task = Svea::admin()->order('12345678')->delivery(456)->credit()->rows([101, 102])->send();
 *
 * // Credit a fixed amount
 * $task = Svea::admin()->order('12345678')->delivery(456)->creditAmount(9900);
 * ```
 */
class AdminDeliveryRequest
{
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
     * Start a fluent credit (refund) builder for this delivery.
     *
     * Chain with `rows()` or `newRow()` then call `send()` to submit.
     */
    public function credit(): CreditRequest
    {
        return new CreditRequest($this->connector, $this->orderId, $this->deliveryId);
    }

    /**
     * Credit a fixed amount on this delivery directly.
     *
     * Use this when you want to refund a specific monetary amount rather than
     * specific order rows.
     *
     * @param  int  $amount  Amount to credit in minor currency units (e.g. öre for SEK).
     *
     * @throws SveaApiException On any non-2xx response.
     */
    public function creditAmount(int $amount): TaskResponse
    {
        $response = $this->connector->patch(
            "api/v1/orders/{$this->orderId}/deliveries/{$this->deliveryId}",
            ['CreditedAmount' => $amount]
        );
        $reference = $response->json['CreditId']
            ?? ($response->headers['Location'][0] ?? '');

        return TaskResponse::pending((string) $reference)->withLastResponse($response);
    }
}
