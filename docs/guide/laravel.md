# Laravel Integration

The `src/Laravel/` directory provides a service provider, facade, Artisan commands, and an event-based webhook bridge.

## Auto-discovery

`SveaServiceProvider` is auto-discovered via the `extra.laravel` key in `composer.json`. To register manually:

```php
// bootstrap/providers.php
Svea\Laravel\SveaServiceProvider::class,
```

## Facade

```php
use Svea\Laravel\Svea;

Svea::checkout()->create(...);
Svea::admin()->order('12345678')->deliver();
Svea::subscriptions()->list();
```

## Webhook event

```php
use Svea\Laravel\Events\SveaWebhookReceived;
use Svea\Laravel\WebhookService;

class SveaWebhookController
{
    public function __invoke(Request $request, WebhookService $webhookService): Response
    {
        $event = $webhookService->fromRequest($request);
        SveaWebhookReceived::dispatch($event);

        return response()->noContent();
    }
}
```

## Artisan commands

| Command | Description |
| --- | --- |
| `svea:subscription:add` | Register a webhook subscription |
| `svea:subscription:list` | List all subscriptions |
| `svea:subscription:get {id}` | Inspect one subscription |
| `svea:subscription:verify {id}` | Verify (Ping) a subscription |
| `svea:subscription:update {id}` | Change URL or events |
| `svea:subscription:remove {id}` | Delete a subscription |

See the [README — Artisan Commands](https://github.com/nordkit/svea/blob/main/README.md#artisan-commands) for full flag reference and examples.

## HTTP tracing with Wiretap (optional)

[`nordkit/wiretap`](https://github.com/nordkit/wiretap) integrates with `SveaClient` via a custom `HandlerStack` — see the [Custom Middleware guide](./middleware).

