<?php

declare(strict_types=1);

namespace Svea\Laravel;

use Illuminate\Support\ServiceProvider;
use Svea\SveaClient;

/**
 * Laravel service provider for the Svea SDK.
 *
 * Auto-discovered via `composer.json` `extra.laravel.providers` — no manual
 * registration is needed in a standard Laravel application.
 *
 * **What it does:**
 * - Merges `config/svea.php` so `config('svea.*')` is always available.
 * - Binds {@see SveaClient} as a singleton behind both its class name and the
 *   `'svea'` abstract alias (resolves via `app('svea')` or `app(SveaClient::class)`).
 * - Binds {@see WebhookService} so it can be injected into controllers.
 * - Publishes `config/svea.php` under the `svea-config` tag.
 *
 * **Publish config:**
 * ```bash
 * php artisan vendor:publish --tag=svea-config
 * ```
 *
 * **Manual registration (if auto-discovery is disabled):**
 * ```php
 * // bootstrap/providers.php
 * Svea\Laravel\SveaServiceProvider::class,
 * ```
 */
class SveaServiceProvider extends ServiceProvider
{
    /**
     * Register all Svea SDK bindings into the service container.
     *
     * - Merges `config/svea.php` under the `svea` key.
     * - Binds {@see SveaClient} as a singleton (also aliased as `'svea'`).
     * - Binds {@see WebhookService} using `webhook_secret` from config.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/svea.php', 'svea');

        $this->app->singleton(SveaClient::class, function ($app): SveaClient {
            return new SveaClient(
                config: (array) $app['config']['svea'],
            );
        });

        $this->app->alias(SveaClient::class, 'svea');

        $this->app->bind(WebhookService::class, function ($app): WebhookService {
            return new WebhookService(
                secret: (string) ($app['config']['svea']['webhook_secret'] ?? '')
            );
        });
    }

    /**
     * Bootstrap the Svea SDK package.
     *
     * Publishes `config/svea.php` to the host application's config directory
     * under the `svea-config` tag when running in the console:
     *
     * ```bash
     * php artisan vendor:publish --tag=svea-config
     * ```
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/svea.php' => config_path('svea.php'),
            ], 'svea-config');
        }
    }
}
