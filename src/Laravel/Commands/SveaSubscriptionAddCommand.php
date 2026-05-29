<?php

declare(strict_types=1);

namespace Svea\Laravel\Commands;

use Illuminate\Console\Command;
use Svea\Laravel\Svea;
use Svea\Subscriptions\EventType;
use Svea\Subscriptions\Subscription;

/**
 * Registers a new Svea webhook subscription and optionally verifies it.
 *
 * Defaults to the app URL webhook endpoint and all available event types
 * unless overridden via options.
 *
 * Usage:
 *   php artisan svea:subscription:add
 *   php artisan svea:subscription:add --url=https://myapp.com/webhooks/svea
 *   php artisan svea:subscription:add --events=CheckoutOrder.Created,CheckoutOrder.Delivered
 *   php artisan svea:subscription:add --no-verify
 */
class SveaSubscriptionAddCommand extends Command
{
    protected $signature = 'svea:subscription:add
        {--url= : The HTTPS callback URL (defaults to APP_URL/v2/webhooks/svea/subscription)}
        {--events= : Comma-separated event types to subscribe to (defaults to all)}
        {--no-verify : Skip sending a verification Ping after registering}';

    protected $description = 'Register a new Svea webhook subscription and verify the callback URL.';

    public function handle(): int
    {
        $url = (string) ($this->option('url') ?: rtrim((string) config('app.url'), '/').'/v2/webhooks/svea/subscription');
        $eventTypes = $this->resolveEventTypes((string) ($this->option('events') ?? ''));

        $this->info("Registering subscription for [{$url}]...");
        $this->line('Events: '.implode(', ', array_map(fn (EventType $e) => $e->value, $eventTypes)));

        $subscription = Svea::subscriptions()->add($url, $eventTypes);

        $subscriptionId = $subscription->id();

        if ($subscriptionId === '') {
            $rawBody = $subscription->getLastResponse()?->body ?? '(no response)';
            $this->error('Subscription ID is empty — unexpected API response:');
            $this->line($rawBody);

            return self::FAILURE;
        }

        $this->info("Subscription registered: [{$subscriptionId}]");

        if (! $this->option('no-verify')) {
            $this->verifySubscription($subscription);
        }

        return self::SUCCESS;
    }

    /**
     * Send a Ping to verify the subscription's callback URL.
     */
    private function verifySubscription(Subscription $subscription): void
    {
        $this->info('Verifying callback URL via Ping event...');

        Svea::subscriptions()->verify($subscription->id());

        $this->info('Verification Ping sent. Check your webhook endpoint received it.');
    }

    /**
     * Resolve the event types from a comma-separated string, or return all if empty.
     *
     * @return EventType[]
     */
    private function resolveEventTypes(string $input): array
    {
        if ($input === '') {
            return array_values(array_filter(
                EventType::cases(),
                fn (EventType $e) => $e !== EventType::Ping,
            ));
        }

        return array_map(
            fn (string $value) => EventType::from(trim($value)),
            explode(',', $input),
        );
    }
}
