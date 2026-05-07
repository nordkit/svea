<?php

declare(strict_types=1);

namespace Svea\Laravel;

use Illuminate\Support\Facades\Facade;
use Svea\SveaClient;
use Svea\Testing\FakeSveaClient;
use Svea\Testing\SveaFakeAssertions;

/**
 * Laravel Facade for the Svea SDK.
 *
 * Resolves the {@see SveaClient} singleton from the container so that all
 * Svea API surfaces are accessible with clean static syntax:
 *
 * ```php
 * // Checkout
 * $order = Svea::checkout()->create(fn (CheckoutOrder $o) => $o->currency('SEK')->addRow(...));
 *
 * // Admin
 * $task = Svea::admin()->order('12345678')->withIdempotencyKey($id)->deliver();
 *
 * // Subscriptions
 * $sub = Svea::subscriptions()->on(EventType::CheckoutOrderDelivered)->notifyAt($url)->register();
 *
 * // Webhooks (Laravel bridge — wraps Illuminate\Http\Request)
 * $event = Svea::webhook()->fromRequest($request);
 * ```
 *
 * **Testing — swap the real client for a fake:**
 *
 * ```php
 * Svea::fake([
 *     'admin.get'     => AdminOrderResponse::make(['OrderStatus' => 'Open', 'Actions' => ['CanDeliverOrder']]),
 *     'admin.deliver' => TaskResponse::pending('https://paymentadminapi.svea.com/api/v1/tasks/fake-123'),
 * ]);
 *
 * // Run code under test
 * (new CaptureOrder)->execute($payment);
 *
 * // Assert via the facade (proxied to SveaFakeAssertions)
 * Svea::assertDelivered('12345678');
 * Svea::assertNothingSent();
 * // — or via the return value of fake():
 * $assertions = Svea::fake();
 * $assertions->assertCalled('admin.deliver');
 * ```
 *
 * @method static \Svea\Checkout\CheckoutService checkout()
 * @method static \Svea\Admin\AdminService admin()
 * @method static \Svea\Subscriptions\SubscriptionService subscriptions()
 * @method static \Svea\Laravel\WebhookService webhook()
 *
 * @see SveaClient
 */
class Svea extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return class-string<SveaClient>
     */
    protected static function getFacadeAccessor(): string
    {
        return SveaClient::class;
    }

    /**
     * Replace the SveaClient singleton with a {@see FakeSveaClient} for testing.
     *
     * Returns a {@see SveaFakeAssertions} instance that can be used to make
     * assertions directly, or ignored when using the static assertion proxies
     * (`Svea::assertDelivered(...)`, `Svea::assertNothingSent()`, etc.).
     *
     * @param  array<string, mixed>  $fakes  Pre-seeded fake responses keyed by "service.method".
     *
     * @example
     * ```php
     * Svea::fake(['checkout.create' => CheckoutResponse::make([...])]);
     * // — or store for direct assertion access:
     * $assertions = Svea::fake();
     * $assertions->preventStrayRequests();
     * ```
     */
    public static function fake(array $fakes = []): SveaFakeAssertions
    {
        $fake = new FakeSveaClient($fakes);
        static::swap($fake);

        return $fake->assertions();
    }

    // -------------------------------------------------------------------------
    // Static assertion proxies — delegate to the active SveaFakeAssertions.
    // These are only valid after Svea::fake() has been called.
    // -------------------------------------------------------------------------

    /**
     * Assert that a checkout order was created.
     */
    public static function assertCheckoutCreated(): void
    {
        static::resolveAssertions()->assertCheckoutCreated();
    }

    /**
     * Assert that the given order was delivered (captured).
     *
     * @param  string  $orderId  The Svea order ID.
     * @param  int[]  $rows  When non-empty, assert that exactly these row IDs were delivered.
     */
    public static function assertDelivered(string $orderId, array $rows = []): void
    {
        static::resolveAssertions()->assertDelivered($orderId, $rows);
    }

    /**
     * Assert that the given order was credited (refunded).
     *
     * @param  string  $orderId  The Svea order ID.
     */
    public static function assertCredited(string $orderId): void
    {
        static::resolveAssertions()->assertCredited($orderId);
    }

    /**
     * Assert that the given order was cancelled.
     *
     * @param  string  $orderId  The Svea order ID.
     */
    public static function assertCancelledOrder(string $orderId): void
    {
        static::resolveAssertions()->assertCancelledOrder($orderId);
    }

    /**
     * Assert that the given async task URL was polled.
     *
     * @param  string  $taskUrl  The full task URL returned by a deliver/credit operation.
     */
    public static function assertTaskPolled(string $taskUrl): void
    {
        static::resolveAssertions()->assertTaskPolled($taskUrl);
    }

    /**
     * Assert that a subscription was registered via the fluent builder
     * (`on()->notifyAt()->register()`).
     *
     * @param  string  $callbackUrl  The callback URL expected to have been registered.
     */
    public static function assertSubscriptionRegistered(string $callbackUrl): void
    {
        static::resolveAssertions()->assertSubscriptionRegistered($callbackUrl);
    }

    /**
     * Assert that a subscription was added via the direct `add()` method.
     *
     * @param  string  $callbackUrl  The callback URL expected to have been used.
     */
    public static function assertSubscriptionAdded(string $callbackUrl): void
    {
        static::resolveAssertions()->assertSubscriptionAdded($callbackUrl);
    }

    /**
     * Assert that a subscription was fetched by ID.
     *
     * @param  string  $subscriptionId  The GUID expected to have been fetched.
     */
    public static function assertSubscriptionFetched(string $subscriptionId): void
    {
        static::resolveAssertions()->assertSubscriptionFetched($subscriptionId);
    }

    /**
     * Assert that the subscription list was retrieved.
     */
    public static function assertSubscriptionsListed(): void
    {
        static::resolveAssertions()->assertSubscriptionsListed();
    }

    /**
     * Assert that a subscription was updated.
     *
     * @param  string  $subscriptionId  The GUID expected to have been updated.
     */
    public static function assertSubscriptionUpdated(string $subscriptionId): void
    {
        static::resolveAssertions()->assertSubscriptionUpdated($subscriptionId);
    }

    /**
     * Assert that a subscription was removed.
     *
     * @param  string  $subscriptionId  The GUID expected to have been removed.
     */
    public static function assertSubscriptionRemoved(string $subscriptionId): void
    {
        static::resolveAssertions()->assertSubscriptionRemoved($subscriptionId);
    }

    /**
     * Assert that a subscription's callback URL was verified.
     *
     * @param  string  $subscriptionId  The GUID expected to have been verified.
     */
    public static function assertSubscriptionVerified(string $subscriptionId): void
    {
        static::resolveAssertions()->assertSubscriptionVerified($subscriptionId);
    }

    /**
     * Assert that no Svea API calls were made.
     */
    public static function assertNothingSent(): void
    {
        static::resolveAssertions()->assertNothingSent();
    }

    /**
     * Resolve the active SveaFakeAssertions from the current fake root.
     *
     * @throws \RuntimeException When called outside of a Svea::fake() context.
     */
    private static function resolveAssertions(): SveaFakeAssertions
    {
        $root = static::getFacadeRoot();

        if (! ($root instanceof FakeSveaClient)) {
            throw new \RuntimeException(
                'Svea assertion methods require Svea::fake() to be called first in your test.'
            );
        }

        return $root->assertions();
    }
}
