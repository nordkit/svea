# Installation

## Requirements

| Dependency | Version |
| --- | --- |
| PHP | ^8.2 |
| `guzzlehttp/guzzle` | ^7.8 |
| `illuminate/support` *(optional)* | ^11.0 \| ^12.0 \| ^13.0 — for the Laravel facade and service provider |

## Install via Composer

```bash
composer require nordkit/svea
```

## Laravel

The service provider and facade are auto-discovered. Publish the config file:

```bash
php artisan vendor:publish --tag=svea-config
```

Then add credentials to `.env`:

```ini
SVEA_MERCHANT_ID=...
SVEA_SHARED_SECRET=...
SVEA_ENVIRONMENT=test
SVEA_WEBHOOK_SECRET=...
```

See [Configuration](./configuration) for the full list of supported keys.

## Standalone (no Laravel)

Instantiate `SveaClient` directly with a config array:

```php
use Svea\SveaClient;

$svea = new SveaClient([
    'merchant_id'    => 'abc',
    'shared_secret'  => 'xyz',
    'environment'    => 'test',
    'webhook_secret' => 'whsec_...',
]);
```

See the [standalone guide](./standalone) for more.

