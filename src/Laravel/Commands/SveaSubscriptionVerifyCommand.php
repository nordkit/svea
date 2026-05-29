<?php

declare(strict_types=1);

namespace Svea\Laravel\Commands;

use Illuminate\Console\Command;
use Svea\Laravel\Svea;

/**
 * Verifies a Svea webhook subscription by triggering a Ping event.
 *
 * Svea sends a Ping event to the subscription's registered CallbackUri.
 * If the endpoint responds successfully, Svea marks the subscription as
 * verified and begins delivering events.
 *
 * Must be called after registering a new subscription or after changing
 * the callback URL via svea:subscription:update.
 *
 * Usage:
 *   php artisan svea:subscription:verify {id}
 */
class SveaSubscriptionVerifyCommand extends Command
{
    protected $signature = 'svea:subscription:verify {id : The GUID of the subscription to verify}';

    protected $description = 'Verify a Svea webhook subscription by sending a Ping event to its callback URL.';

    public function handle(): int
    {
        $id = $this->argument('id');

        $this->info("Sending verification Ping to subscription [{$id}]...");

        Svea::subscriptions()->verify($id);

        $this->info('Ping sent. If your endpoint responded with HTTP 2xx, the subscription is now verified.');
        $this->line('Run svea:subscription:get '.$id.' to confirm the Verified status.');

        return self::SUCCESS;
    }
}
