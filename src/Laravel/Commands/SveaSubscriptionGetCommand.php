<?php

declare(strict_types=1);

namespace Svea\Laravel\Commands;

use Illuminate\Console\Command;
use Svea\Laravel\Svea;

/**
 * Shows the details of a single Svea webhook subscription by ID.
 *
 * Usage:
 *   php artisan svea:subscription:get {id}
 */
class SveaSubscriptionGetCommand extends Command
{
    protected $signature = 'svea:subscription:get {id : The GUID of the subscription}';

    protected $description = 'Show details of a single Svea webhook subscription.';

    public function handle(): int
    {
        $subscription = Svea::subscriptions()->get($this->argument('id'));

        $this->table(
            headers: ['Field', 'Value'],
            rows: [
                ['ID', $subscription->id()],
                ['Callback URL', $subscription->callbackUrl()],
                ['Verified', $subscription->isVerified() ? '✓ Yes' : '✗ No'],
                ['Created', $subscription->createdAt()?->format('Y-m-d H:i:s') ?? '—'],
                ['Events', implode(', ', array_map(fn ($e) => $e->value, $subscription->events()))],
            ],
        );

        return self::SUCCESS;
    }
}
