<?php

declare(strict_types=1);

namespace Svea\Admin;

/**
 * Enum of Svea Payment Admin order statuses.
 *
 * Returned by {@see AdminOrderResponse::status()} after fetching an order
 * via {@see AdminOrderRequest::get()}.
 *
 * @see https://docs.payments.svea.com/docs/manage-order/admin-api/get_order
 */
enum SveaOrderStatus: string
{
    /** Order has been created and is awaiting delivery. */
    case Open = 'Open';

    /** Order has been fully or partially delivered (captured). */
    case Delivered = 'Delivered';

    /** Order has been cancelled before delivery. */
    case Cancelled = 'Cancelled';

    /** Order is in a terminal state — fully processed and closed. */
    case Final = 'Final';
}
