<?php

declare(strict_types=1);

namespace Svea\Testing;

use PHPUnit\Framework\Assert;
use Svea\Laravel\Svea;

/**
 * Shared assertion and call-recording helper for the Svea fake layer.
 *
 * Returned by {@see FakeSveaClient::assertions()} and by the
 * `Svea::fake()` facade method. Holds the call log and seeded fake responses
 * for the entire test, and exposes both generic and domain-specific assertions.
 *
 * **Generic assertions (any service method key):**
 * ```php
 * $assertions->assertCalled('admin.deliver');
 * $assertions->assertNotCalled('checkout.create');
 * $assertions->assertCalledTimes('admin.deliver', 2);
 * ```
 *
 * **Domain-specific assertions (preferred for readability):**
 * ```php
 * $assertions->assertDelivered('12345678');
 * $assertions->assertCredited('12345678');
 * $assertions->assertCheckoutCreated();
 * $assertions->assertNothingSent();
 * ```
 *
 * **Facade shortcuts (same as above, without storing the return value):**
 * ```php
 * Svea::fake([...]);
 * // ... run code under test ...
 * Svea::assertDelivered('12345678');
 * Svea::assertNothingSent();
 * ```
 *
 * @see Svea::fake()  Facade entry point
 * @see FakeSveaClient  Client fake that populates this class
 */
class SveaFakeAssertions
{
    private bool $preventStray = false;

    /** @var array<int, array{method: string, args: mixed[]}> */
    private array $calls = [];

    /** @var array<string, mixed> */
    private array $fakes = [];

    /** @param array<string, mixed> $fakes Pre-seeded fake responses keyed by "service.method". */
    public function __construct(array $fakes = [])
    {
        $this->fakes = $fakes;
    }

    /**
     * Throw an exception if any non-faked call is attempted.
     */
    public function preventStrayRequests(): static
    {
        $this->preventStray = true;

        return $this;
    }

    /**
     * Return whether stray request prevention is currently active.
     *
     * Used by `Fake*` classes to decide whether to throw on un-seeded calls.
     */
    public function isPreventingStrayRequests(): bool
    {
        return $this->preventStray;
    }

    /**
     * Record a fake call in the call log.
     *
     * Called internally by every `Fake*` class whenever a service method is
     * invoked. The `$method` key uses dot-notation (`admin.deliver`, etc.).
     *
     * @param  string  $method  Dot-notation method key.
     * @param  mixed[]  $args  Arguments passed to the method, used by predicate-based assertions.
     */
    public function recordCall(string $method, array $args = []): void
    {
        $this->calls[] = ['method' => $method, 'args' => $args];
    }

    /**
     * Return the pre-seeded fake response for the given key, or `null` if none is set.
     *
     * @param  string  $key  Dot-notation method key (e.g. `'admin.deliver'`).
     */
    public function fakeFor(string $key): mixed
    {
        return $this->fakes[$key] ?? null;
    }

    /**
     * Return whether a pre-seeded fake response exists for the given key.
     *
     * @param  string  $key  Dot-notation method key (e.g. `'admin.deliver'`).
     */
    public function hasFakeFor(string $key): bool
    {
        return array_key_exists($key, $this->fakes);
    }

    /**
     * Assert that a checkout order was created.
     */
    public function assertCheckoutCreated(): void
    {
        Assert::assertTrue(
            $this->hasCalled('checkout.create'),
            'Failed asserting that a Svea checkout order was created.'
        );
    }

    /**
     * Assert that the given order was delivered (captured).
     *
     * @param  string  $orderId  The Svea order ID.
     * @param  int[]  $rows  When non-empty, assert that exactly these row IDs were delivered.
     */
    public function assertDelivered(string $orderId, array $rows = []): void
    {
        Assert::assertTrue(
            $this->hasCalled('admin.deliver', fn (array $args) => ($args[0] ?? null) === $orderId &&
                (empty($rows) || ($args[1] ?? []) === $rows)
            ),
            "Failed asserting that order [{$orderId}] was delivered."
        );
    }

    /**
     * Assert that the given order was credited (refunded) via `delivery()->credit()->send()`.
     *
     * @param  string  $orderId  The Svea order ID.
     */
    public function assertCredited(string $orderId): void
    {
        Assert::assertTrue(
            $this->hasCalled('admin.credit', fn (array $args) => ($args[0] ?? null) === $orderId),
            "Failed asserting that order [{$orderId}] was credited."
        );
    }

    /**
     * Assert that the given order was cancelled.
     *
     * @param  string  $orderId  The Svea order ID.
     */
    public function assertCancelledOrder(string $orderId): void
    {
        Assert::assertTrue(
            $this->hasCalled('admin.cancel', fn (array $args) => ($args[0] ?? null) === $orderId),
            "Failed asserting that order [{$orderId}] was cancelled."
        );
    }

    /**
     * Assert that the given async task URL was polled via `admin()->task($url)`.
     *
     * @param  string  $taskUrl  The full task URL returned by a prior deliver/credit operation.
     */
    public function assertTaskPolled(string $taskUrl): void
    {
        Assert::assertTrue(
            $this->hasCalled('admin.task', fn (array $args) => ($args[0] ?? null) === $taskUrl),
            "Failed asserting that task [{$taskUrl}] was polled."
        );
    }

    /**
     * Assert that a subscription was registered via the fluent builder (on→notifyAt→register).
     *
     * @param  string  $callbackUrl  The callback URL expected to have been registered.
     */
    public function assertSubscriptionRegistered(string $callbackUrl): void
    {
        Assert::assertTrue(
            $this->hasCalled('subscriptions.register', fn (array $args) => ($args[0] ?? null) === $callbackUrl),
            "Failed asserting that a subscription was registered for [{$callbackUrl}]."
        );
    }

    /**
     * Assert that a subscription was added via the direct add() method.
     *
     * @param  string  $callbackUrl  The callback URL expected to have been used.
     */
    public function assertSubscriptionAdded(string $callbackUrl): void
    {
        Assert::assertTrue(
            $this->hasCalled('subscriptions.add', fn (array $args) => ($args[0] ?? null) === $callbackUrl),
            "Failed asserting that a subscription was added for [{$callbackUrl}]."
        );
    }

    /**
     * Assert that a subscription was fetched by ID.
     *
     * @param  string  $subscriptionId  The subscription GUID expected to have been fetched.
     */
    public function assertSubscriptionFetched(string $subscriptionId): void
    {
        Assert::assertTrue(
            $this->hasCalled('subscriptions.get', fn (array $args) => ($args[0] ?? null) === $subscriptionId),
            "Failed asserting that subscription [{$subscriptionId}] was fetched."
        );
    }

    /**
     * Assert that the subscription list was retrieved.
     */
    public function assertSubscriptionsListed(): void
    {
        Assert::assertTrue(
            $this->hasCalled('subscriptions.list'),
            'Failed asserting that the subscription list was retrieved.'
        );
    }

    /**
     * Assert that a subscription was updated.
     *
     * @param  string  $subscriptionId  The GUID of the subscription expected to have been updated.
     */
    public function assertSubscriptionUpdated(string $subscriptionId): void
    {
        Assert::assertTrue(
            $this->hasCalled('subscriptions.update', fn (array $args) => ($args[0] ?? null) === $subscriptionId),
            "Failed asserting that subscription [{$subscriptionId}] was updated."
        );
    }

    /**
     * Assert that a subscription was removed.
     *
     * @param  string  $subscriptionId  The GUID of the subscription expected to have been removed.
     */
    public function assertSubscriptionRemoved(string $subscriptionId): void
    {
        Assert::assertTrue(
            $this->hasCalled('subscriptions.remove', fn (array $args) => ($args[0] ?? null) === $subscriptionId),
            "Failed asserting that subscription [{$subscriptionId}] was removed."
        );
    }

    /**
     * Assert that a subscription's callback URL was verified.
     *
     * @param  string  $subscriptionId  The GUID of the subscription expected to have been verified.
     */
    public function assertSubscriptionVerified(string $subscriptionId): void
    {
        Assert::assertTrue(
            $this->hasCalled('subscriptions.verify', fn (array $args) => ($args[0] ?? null) === $subscriptionId),
            "Failed asserting that subscription [{$subscriptionId}] was verified."
        );
    }

    /**
     * Assert that a specific method key was called at least once.
     *
     * The method key matches the dot-notation used in fake seeding:
     * `checkout.create`, `admin.deliver`, `admin.cancel`, `subscriptions.add`, etc.
     *
     * @param  string  $method  Dot-notation method key, e.g. `'admin.deliver'`.
     */
    public function assertCalled(string $method): void
    {
        Assert::assertTrue(
            $this->hasCalled($method),
            "Failed asserting that [{$method}] was called."
        );
    }

    /**
     * Assert that a specific method key was never called.
     *
     * @param  string  $method  Dot-notation method key, e.g. `'admin.deliver'`.
     */
    public function assertNotCalled(string $method): void
    {
        Assert::assertFalse(
            $this->hasCalled($method),
            "Failed asserting that [{$method}] was not called."
        );
    }

    /**
     * Assert that a specific method key was called exactly the given number of times.
     *
     * @param  string  $method  Dot-notation method key, e.g. `'admin.deliver'`.
     * @param  int  $times  Expected call count.
     */
    public function assertCalledTimes(string $method, int $times): void
    {
        $count = count(array_filter($this->calls, fn (array $call) => $call['method'] === $method));
        Assert::assertSame(
            $times,
            $count,
            "Failed asserting that [{$method}] was called {$times} time(s). It was called {$count} time(s)."
        );
    }

    /**
     * Assert that no Svea API calls were made.
     */
    public function assertNothingSent(): void
    {
        Assert::assertEmpty($this->calls, 'Failed asserting that no Svea API calls were made.');
    }

    /**
     * Check whether the call log contains a call matching the given method key.
     *
     * @param  string  $method  Dot-notation method key (e.g. `'admin.deliver'`).
     * @param  callable|null  $predicate  Optional predicate receiving `$args` to further filter matches.
     */
    private function hasCalled(string $method, ?callable $predicate = null): bool
    {
        foreach ($this->calls as $call) {
            if ($call['method'] !== $method) {
                continue;
            }
            if ($predicate === null || $predicate($call['args'])) {
                return true;
            }
        }

        return false;
    }
}
