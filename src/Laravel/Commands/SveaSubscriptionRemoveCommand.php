<?php

declare(strict_types=1);

namespace Svea\Laravel\Commands;

use Illuminate\Console\Command;
use Svea\Laravel\Svea;

/**
 * Removes a registered Svea webhook subscription by ID.
 *
 * Prompts for confirmation unless --force is passed. After removal Svea
 * will no longer deliver events to the subscription's callback URL.
 *
 * Usage:
 *   php artisan svea:subscription:remove {id}
 *   php artisan svea:subscription:remove {id} --force
 */
class SveaSubscriptionRemoveCommand extends Command
{
    protected $signature = 'svea:subscription:remove
        {id : The GUID of the subscription to remove}
        {--force : Skip the confirmation prompt}';

    protected $description = 'Remove a registered Svea webhook subscription.';

    public function handle(): int
    {
        $id = $this->argument('id');

        if (! $this->option('force') && ! $this->confirm("Remove subscription [{$id}]? This cannot be undone.")) {
            $this->info('Aborted.');

            return self::SUCCESS;
        }

        Svea::subscriptions()->remove($id);

        $this->info("Subscription [{$id}] removed.");

        return self::SUCCESS;
    }
}
