<?php

declare(strict_types=1);

namespace Svea\Subscriptions;

/**
 * Svea webhook subscription event types.
 *
 * Used when registering a subscription to specify which events Svea should
 * deliver to your callback URL. Pass one or more cases to
 * SubscriptionService::add(), SubscriptionService::update(), or the fluent
 * builder via SubscriptionService::on().
 *
 * All event type string values are fixed by Svea's API and must be passed
 * exactly as defined (e.g. 'CheckoutOrder.Created'). This enum provides
 * type-safe access to every supported value.
 *
 * Example:
 *   $svea->subscriptions()
 *       ->on(EventType::CheckoutOrderCreated, EventType::CheckoutOrderDelivered)
 *       ->notifyAt('https://myapp.com/webhooks/svea')
 *       ->register();
 *
 * The Ping case is sent automatically by Svea when verify() is called and
 * does not need to be included in a subscription's event list — but your
 * webhook handler should accept it without error.
 *
 * @see https://docs.payments.svea.com/docs/manage-order/callbacks/callback_events
 */
enum EventType: string
{
    /**
     * Checkout order created
     * Event raised when a checkout order has been created.
     * IsPending field returns true/false, indicates whether the Order is waiting to be Approved by Svea.
     */
    case CheckoutOrderCreated = 'CheckoutOrder.Created';

    /**
     * Checkout order updated
     * Event triggered for when an order is edited or when an explicit order sync is performed. It is recommended to
     * implement this event for full order sync via API order GET.
     */
    case CheckoutOrderUpdated = 'CheckoutOrder.Updated';

    /**
     * Checkout order delivered
     * Event raised when a checkout order is partially delivered or delivered in full.
     */
    case CheckoutOrderDelivered = 'CheckoutOrder.Delivered';

    /**
     * Checkout order credit succeeded
     * Event raised when a checkout order is successfully credited
     */
    case CheckoutOrderCreditSucceeded = 'CheckoutOrder.CreditSucceeded';

    /**
     * Checkout order credit failed
     * Event raised when an accepted credit operation fails.
     */
    case CheckoutOrderCreditFailed = 'CheckoutOrder.CreditFailed';

    /**
     * Checkout order closed
     * Event raised when a checkout order is closed. The reason for closing the order is provided in the event body
     * when due to explicit operation, valid close reasons are ‘Cancelled’ and ‘Expired’.
     */
    case CheckoutOrderClosed = 'CheckoutOrder.Closed';

    /**
     * Checkout order pending released
     * Event triggered when an order is Approved by Svea from Pending status.
     */
    case CheckoutOrderPendingStatusReleased = 'CheckoutOrder.PendingStatusReleased';

    /**
     * Standalone order pending released
     * Event triggered when a Standalone order is Approved by Svea from Pending status.
     */
    case StandaloneOrderPendingStatusReleased = 'StandaloneOrder.PendingStatusReleased';

    /**
     * Standalone order closed
     * Event raised when a Standalone order is closed. The reason for closing the order is provided in the event body.
     */
    case StandaloneOrderClosed = 'StandaloneOrder.Closed';

    /**
     * Ping event
     * Event sent by the verify endpoint to check the connectivity and validity of the webhook.
     */
    case Ping = 'Ping';
}
