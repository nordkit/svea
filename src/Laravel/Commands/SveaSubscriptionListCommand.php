<?php

declare(strict_types=1);

namespace Svea\Laravel\Commands;

use Illuminate\Console\Command;
use Svea\Laravel\Svea;
use Svea\Subscriptions\Subscription;

/**
 * Lists all registered Svea webhook subscriptions for the configured merchant.
 *
 * Usage:
 *   php artisan svea:subscription:list
 *
 * Outputs a table of SubscriptionId, CallbackUri, Verified status, and
 * subscribed event types. Useful for auditing what is currently registered
 * in Svea's system.
 */
class SveaSubscriptionListCommand extends Command
{
    protected $signature = 'svea:subscription:list';

    protected $description = 'List all registered Svea webhook subscriptions for the configured merchant.';

    public function handle(): int
    {
        $subscriptions = Svea::subscriptions()->list();

        if (empty($subscriptions)) {
            $this->info('No subscriptions registered.');

            return self::SUCCESS;
        }

        $this->table(
            headers: ['ID', 'Callback URL', 'Verified', 'Events'],
            rows: array_map(fn (Subscription $s) => [
                $s->id(),
                $s->callbackUrl(),
                $s->isVerified() ? '<fg=green>✓ Yes</>' : '<fg=yellow>✗ No</>',
                implode(', ', array_map(fn ($e) => $e->value, $s->events())),
            ], $subscriptions),
        );

        return self::SUCCESS;
    }
}
