<?php

declare(strict_types=1);

namespace Svea\Testing;

use Svea\Laravel\Svea;
use Svea\SveaClient;
use Svea\Webhooks\WebhookService;

/**
 * In-memory fake that replaces the real {@see SveaClient} in tests.
 *
 * Activated by `Svea::fake()` (Laravel facade) or constructed directly in plain
 * PHP tests. Exposes the same `checkout()`, `admin()`, `subscriptions()`, and
 * `webhook()` entry points as `SveaClient`, but all operations are recorded in
 * memory — no real HTTP requests are made.
 *
 * **Via the Laravel facade (recommended for Laravel tests):**
 * ```php
 * Svea::fake([
 *     'admin.get'     => AdminOrderResponse::make(['OrderStatus' => 'Open', 'Actions' => ['CanDeliverOrder']]),
 *     'admin.deliver' => TaskResponse::pending('https://paymentadminapi.svea.com/api/v1/tasks/fake-123'),
 *     'admin.task'    => TaskResponse::completed(),
 * ]);
 *
 * (new CaptureOrder)->execute($payment);
 *
 * Svea::assertDelivered('12345678');
 * Svea::assertNothingSent();
 * ```
 *
 * **Standalone (framework-agnostic tests):**
 * ```php
 * $fake   = new FakeSveaClient(['checkout.create' => CheckoutResponse::make([...])]);
 * $result = $fake->checkout()->create(fn ($o) => $o->currency('SEK'));
 *
 *
 * $fake->assertions()->assertCheckoutCreated();
 * $fake->assertions()->assertCalledTimes('checkout.create', 1);
 * ```
 *
 * **Seeded fake responses:**
 * Pass an array keyed by `"service.method"` to pre-seed specific responses.
 * Any un-seeded call returns a sensible default stub unless
 * `preventStrayRequests()` is called.
 *
 * Supported keys: `checkout.create`, `checkout.get`, `checkout.update`,
 * `admin.get`, `admin.deliver`, `admin.task`, `admin.credit`, `admin.creditAmount`,
 * `admin.addOrderRow`, `subscriptions.register`, `subscriptions.add`,
 * `subscriptions.get`, `subscriptions.list`, `subscriptions.update`.
 *
 * @see SveaFakeAssertions  All assertion methods
 * @see Svea::fake()  Facade entry point
 */
class FakeSveaClient
{
    private ?FakeCheckoutService $checkoutService = null;

    private ?FakeAdminService $adminService = null;

    private ?FakeSubscriptionService $subscriptionService = null;

    private readonly SveaFakeAssertions $sveaFakeAssertions;

    /** @param array<string, mixed> $fakes Pre-seeded fake responses. */
    public function __construct(array $fakes = [])
    {
        $this->sveaFakeAssertions = new SveaFakeAssertions($fakes);
    }

    /**
     * Return the shared assertion helper for this fake client.
     *
     * Use this to make domain-specific or generic assertions after running
     * code under test:
     *
     * ```php
     * $fake = new FakeSveaClient;
     * $fake->admin()->order('12345678')->deliver();
     * $fake->assertions()->assertDelivered('12345678');
     * $fake->assertions()->assertCalledTimes('admin.deliver', 1);
     * ```
     */
    public function assertions(): SveaFakeAssertions
    {
        return $this->sveaFakeAssertions;
    }

    /**
     * Return the fake checkout service.
     *
     * Lazily instantiated and shared across calls so all recorded calls
     * accumulate in the same {@see SveaFakeAssertions} instance.
     */
    public function checkout(): FakeCheckoutService
    {
        return $this->checkoutService ??= new FakeCheckoutService($this->sveaFakeAssertions);
    }

    /**
     * Return the fake admin service.
     *
     * Lazily instantiated and shared across calls so all recorded calls
     * accumulate in the same {@see SveaFakeAssertions} instance.
     */
    public function admin(): FakeAdminService
    {
        return $this->adminService ??= new FakeAdminService($this->sveaFakeAssertions);
    }

    /**
     * Return the fake subscription service.
     *
     * Lazily instantiated and shared across calls so all recorded calls
     * accumulate in the same {@see SveaFakeAssertions} instance.
     */
    public function subscriptions(): FakeSubscriptionService
    {
        return $this->subscriptionService ??= new FakeSubscriptionService($this->sveaFakeAssertions);
    }

    /**
     * Returns the real {@see WebhookService} with an empty secret.
     *
     * Webhook signature verification is pure HMAC computation with no I/O,
     * so no faking is necessary. In tests, construct a real
     * `Webhook::constructEvent()` call with a known secret and payload instead.
     */
    public function webhook(): WebhookService
    {
        return new WebhookService('');
    }
}
