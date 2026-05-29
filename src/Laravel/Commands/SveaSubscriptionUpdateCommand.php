<?php

declare(strict_types=1);

namespace Svea\Laravel\Commands;

use Illuminate\Console\Command;
use Svea\Laravel\Svea;
use Svea\Subscriptions\EventType;

/**
 * Updates an existing Svea webhook subscription's callback URL and/or event types.
 *
 * Note: If the callback URL is changed, the subscription will need to be
 * re-verified. Use --verify or run svea:subscription:verify afterwards.
 *
 * Usage:
 *   php artisan svea:subscription:update {id} --url=https://myapp.com/webhooks/svea
 *   php artisan svea:subscription:update {id} --events=CheckoutOrder.Created,CheckoutOrder.Delivered
 *   php artisan svea:subscription:update {id} --url=https://new.myapp.com/webhooks --verify
 */
class SveaSubscriptionUpdateCommand extends Command
{
    protected $signature = 'svea:subscription:update
        {id : The GUID of the subscription to update}
        {--url= : The new HTTPS callback URL}
        {--events= : Comma-separated event types to subscribe to}
        {--verify : Send a verification Ping after updating}';

    protected $description = 'Update an existing Svea webhook subscription.';

    public function handle(): int
    {
        $id = $this->argument('id');

        /** Fetch existing subscription to use as defaults for unchanged fields. */
        $existing = Svea::subscriptions()->get($id);

        $url = (string) ($this->option('url') ?: $existing->callbackUrl());
        $eventTypes = $this->option('events')
            ? $this->resolveEventTypes((string) $this->option('events'))
            : $existing->events();

        $this->info("Updating subscription [{$id}]...");

        $updated = Svea::subscriptions()->update($id, $url, $eventTypes);

        $this->info("Subscription updated: [{$updated->id()}]");
        $this->line("URL:    {$updated->callbackUrl()}");
        $this->line('Events: '.implode(', ', array_map(fn ($e) => $e->value, $updated->events())));

        if ($this->option('verify')) {
            $this->info('Sending verification Ping...');
            Svea::subscriptions()->verify($updated->id());
            $this->info('Verification Ping sent.');
        } elseif ($this->option('url')) {
            $this->warn('Callback URL changed — run svea:subscription:verify '.$updated->id().' to re-verify.');
        }

        return self::SUCCESS;
    }

    /**
     * @return EventType[]
     */
    private function resolveEventTypes(string $input): array
    {
        return array_map(
            fn (string $value) => EventType::from(trim($value)),
            explode(',', $input),
        );
    }
}
