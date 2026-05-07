<?php

declare(strict_types=1);

namespace Svea;

use GuzzleHttp\HandlerStack;
use Svea\Admin\AdminService;
use Svea\Checkout\CheckoutService;
use Svea\Subscriptions\SubscriptionService;
use Svea\Transport\SveaConnector;
use Svea\Webhooks\WebhookService;

/**
 * Main entry point for the Svea SDK.
 *
 * Lazily resolves service instances on first access and caches them for the
 * lifetime of the client. Each API surface gets its own {@see SveaConnector}
 * instance, scoped to the correct base URL for that surface and environment.
 *
 * Supported config keys:
 *   - merchant_id              (string)  Svea merchant identifier
 *   - shared_secret            (string)  HMAC-SHA512 signing secret
 *   - environment              (string)  'test' | 'production' — selects base URLs
 *   - webhook_secret           (string)  HMAC-SHA256 secret for inbound webhook verification
 *   - subscription_callback_url (string) Default callback URL for new subscriptions
 *   - max_retries              (int)     Number of automatic retries (default 0)
 *   - timeout                  (int)     HTTP timeout in seconds (default 10)
 *   - base_urls                (array)   Per-surface URL overrides, e.g. ['checkout' => 'https://...']
 *
 * Framework-agnostic usage:
 * ```php
 * $svea = new SveaClient([
 *     'merchant_id'   => 'abc',
 *     'shared_secret' => 'xyz',
 *     'environment'   => 'test',
 * ]);
 * $svea->checkout()->create(...);
 * $svea->admin()->order('12345678')->deliver();
 * ```
 *
 * Laravel facade usage (preferred inside Laravel apps):
 * ```php
 * Svea::checkout()->create(...);
 * Svea::admin()->order('12345678')->deliver();
 * ```
 *
 * Property-style access (convenience alias — method form preferred for IDE support):
 * ```php
 * $svea->checkout->create(...);
 * $svea->admin->order('12345678')->deliver();
 * ```
 */
final class SveaClient
{
    /**
     * Base URLs per API surface and environment.
     * These are fixed by Svea's infrastructure and not user-configurable.
     *
     * @var array<string, array<string, string>>
     */
    private const BASE_URLS = [
        'checkout' => [
            'test' => 'https://checkoutapistage.svea.com',
            'production' => 'https://checkoutapi.svea.com',
        ],
        'admin' => [
            'test' => 'https://paymentadminapistage.svea.com',
            'production' => 'https://paymentadminapi.svea.com',
        ],
        'subscriptions' => [
            'test' => 'https://paymentadminapistage.svea.com',
            'production' => 'https://paymentadminapi.svea.com',
        ],
    ];

    private ?CheckoutService $checkoutService = null;

    private ?AdminService $adminService = null;

    private ?SubscriptionService $subscriptionService = null;

    private ?WebhookService $webhookService = null;

    /** @param array<string, mixed> $config */
    public function __construct(
        private readonly array $config,
        private readonly ?HandlerStack $handlerStack = null,
    ) {}

    /**
     * Return the Checkout API service.
     *
     * Lazily initialised on first call; reused on subsequent calls.
     */
    public function checkout(): CheckoutService
    {
        return $this->checkoutService ??= new CheckoutService($this->makeConnector('checkout'));
    }

    /**
     * Return the Payment Admin API service.
     *
     * Lazily initialised on first call; reused on subsequent calls.
     */
    public function admin(): AdminService
    {
        return $this->adminService ??= new AdminService($this->makeConnector('admin'));
    }

    /**
     * Return the Webhook Subscription API service.
     *
     * Lazily initialised on first call; reused on subsequent calls.
     */
    public function subscriptions(): SubscriptionService
    {
        return $this->subscriptionService ??= new SubscriptionService(
            connector: $this->makeConnector('subscriptions'),
            defaultCallbackUrl: (string) ($this->config['subscription_callback_url'] ?? ''),
        );
    }

    /**
     * Return the inbound Webhook verification service.
     *
     * Lazily initialised on first call; reused on subsequent calls.
     */
    public function webhook(): WebhookService
    {
        return $this->webhookService ??= new WebhookService((string) ($this->config['webhook_secret'] ?? ''));
    }

    /**
     * Magic property access — convenience alias for the method form.
     *
     * Allows `$svea->checkout->create(...)` in addition to `$svea->checkout()->create(...)`.
     * Prefer the method form in typed, IDE-friendly code.
     *
     * @throws \BadMethodCallException When an unknown service name is accessed.
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'checkout' => $this->checkout(),
            'admin' => $this->admin(),
            'subscriptions' => $this->subscriptions(),
            'webhook' => $this->webhook(),
            default => throw new \BadMethodCallException("Unknown Svea service: [{$name}]"),
        };
    }

    private function makeConnector(string $surface): SveaConnector
    {
        $env = (string) ($this->config['environment'] ?? 'test');
        $override = (string) ($this->config['base_urls'][$surface] ?? '');
        $baseUrl = $override !== ''
            ? $override
            : (self::BASE_URLS[$surface][$env]
                ?? throw new \InvalidArgumentException("No URL configured for [{$surface}][{$env}]"));

        return new SveaConnector($this->config, $baseUrl, $this->handlerStack);
    }
}
