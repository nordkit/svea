# Contributing to `nordkit/svea`

This document covers architecture decisions, implementation internals, and development setup. It is intended for maintainers and contributors, not package consumers.

---

## Table of Contents

- [Why a new package?](#why-a-new-package)
- [Development Setup](#development-setup)
- [Architecture Decisions](#architecture-decisions)
  - [Framework coupling](#framework-coupling)
  - [Root namespace](#root-namespace)
  - [Two entry points](#two-entry-points)
  - [SveaResource — typed response objects](#svearesource--typed-response-objects)
  - [Webhook::constructEvent()](#webhookconstructevent)
  - [RetryMiddleware — backoff algorithm](#retrymiddleware--backoff-algorithm)
- [Internal Implementation Notes](#internal-implementation-notes)
  - [composer.json skeleton](#composerjson-skeleton)
  - [SveaResource base class](#svearesource-base-class)
  - [SveaResponse — wrapping PSR-7](#svearesponse--wrapping-psr-7)
  - [SveaConnector — HMAC auth and response threading](#sveaconnector--hmac-auth-and-response-threading)
  - [SveaClient — lazy service properties](#sveaclient--lazy-service-properties)
  - [Test bootstrap](#test-bootstrap)
- [Development Roadmap](#development-roadmap)

---

## Why a new package?

The `sveaekonomi/checkout` v1.7.1 package that preceded this SDK has fundamental limitations:

| Problem | Detail |
|---|---|
| PHP 5.3 era code | No strict types, no enums, no named arguments, no readonly properties |
| Raw cURL only | Hard-wired `CurlRequest` — untestable without real HTTP |
| Hard-coded URL allow-list | `Connector::validateBaseApiUrl()` rejects any URL not in its 4 constants — impossible to add the Subscription API |
| No Subscription API | Entirely missing — no webhook subscription register/list/delete |
| No inbound webhook verification | Signature validation not included |
| No testing layer | No `fake()` method, forces `Mockery::mock(CheckoutAdminClient::class)` everywhere |

---

## Development Setup

```bash
# Install dependencies
composer install

# Run tests
vendor/bin/pest

# Fix code style
vendor/bin/pint
```

Tests use [Orchestra Testbench](https://packages.tools/testbench) and Guzzle's `MockHandler` — no real Svea credentials are needed to run the test suite.

---

## Architecture Decisions

### Framework coupling

The **core SDK has zero framework dependencies** — it is pure PHP. Laravel is an optional integration layer only.

#### Core (`src/` except `src/Laravel/`)

- No `illuminate/*` dependencies whatsoever.
- `when()`/`unless()` fluent chaining is provided by a tiny custom `Svea\Support\Conditionable` trait (no illuminate needed).
- `Macroable` and `Tappable` are intentionally excluded — they add framework coupling without meaningful benefit for an HTTP SDK.
- `subscriptions()->list()` returns a plain `array<int, Subscription>`, not a `Collection`.
- Dates in `WebhookEvent` and `Subscription` use `DateTimeImmutable` (pure PHP). No `nesbot/carbon` dependency.
- `WebhookService::fromRequest()` accepts `Psr\Http\Message\RequestInterface` (PSR-7). The Laravel wrapper in `src/Laravel/` bridges `Illuminate\Http\Request` → PSR-7 before delegating.

#### Laravel integration (`src/Laravel/`)

- `SveaServiceProvider` and `Svea` facade live here.
- These files **do** depend on `illuminate/support` and are the only files that do.
- Auto-discovered via `composer.json` `extra.laravel` — no manual registration required.

#### Directory layout

```
src/
  SveaClient.php                  ← pure PHP
  SveaResource.php                ← pure PHP base class
  Support/
    Conditionable.php             ← tiny custom trait, no illuminate
  Laravel/
    SveaServiceProvider.php       ← illuminate/support only
    Svea.php                      ← facade
  Checkout/   Admin/   Subscriptions/   Webhooks/   Transport/   Exceptions/   Testing/
```

### Root namespace

All classes live under `Svea\`. Examples:

```
Svea\SveaClient
Svea\Checkout\CheckoutService
Svea\Admin\AdminService
Svea\Contracts\CheckoutServiceInterface
Svea\Contracts\AdminServiceInterface
Svea\Contracts\SubscriptionServiceInterface
Svea\Webhooks\Webhook
Svea\Exceptions\SveaApiException
Svea\Laravel\SveaServiceProvider
Svea\Laravel\Svea
```

### Two entry points

```php
// Laravel — static facade (zero config, reads config/svea.php)
Svea::checkout()->create(...);

// Framework-agnostic — explicit instance (portable, testable, multi-merchant)
$svea = new SveaClient([
    'merchant_id'   => 'abc',
    'shared_secret' => 'xyz',
    'environment'   => 'test',
]);
$svea->checkout->create(...);
$svea->admin->order('12345678')->deliver();
```

`SveaClient` exposes typed **service properties** (`->checkout`, `->admin`, `->subscriptions`, `->webhook`). Each property is lazily resolved on first access.

### `SveaResource` — typed response objects

Every response object is read-only after construction. Attempting to set or unset a key via array notation throws `\BadMethodCallException`. The `getLastResponse()` method returns the raw `SveaResponse` — always available for debugging.

### `Webhook::constructEvent()`

Pure static method — no dependency injection, no framework, works in any PHP context. Throws `SignatureVerificationException` on HMAC mismatch.

### Fluent builders — optional constructors and `make()`

All fluent builder classes (`CheckoutOrder`, `OrderRow`, `MerchantSettings`, `AdminOrderRow`) follow the same pattern:

- **All constructor params are optional** (null/default values). Previously required params are documented in the PHPDoc as required by the Svea API, but PHP no longer enforces them at construction time.
- **`make()` named constructor** — returns `new static()`. Identical to calling `new ClassName()` directly; provided as a self-documenting entry point at internal call sites (e.g. `Cart::addRow()`, `CheckoutService::create()`).

**Why not `blank()` + `ReflectionClass::newInstanceWithoutConstructor()`?**

The original design used reflection to bypass constructors that had required parameters (to support the fluent callback API while still enforcing required args on direct construction). This was replaced because:
- `new static()` with all-optional params is simpler, idiomatic PHP, and requires no reflection
- The "required at construction" enforcement was never actually meaningful — invalid payloads are caught by the Svea API, not the PHP constructor
- Reflection bypasses PHP's type system and is harder to reason about

The trade-off: required fields are now documented only in PHPDoc, not enforced by the constructor. The Svea API will reject malformed payloads regardless.



```
# Retry on: ConnectException, 429, 500, 503
# Delay formula: min(1000 * 2^attempt, 32000) ms + random 0–1000 ms jitter
# Example with max_retries=2: attempt 1 → ~2 s, attempt 2 → ~4 s
```

---

## Internal Implementation Notes

### `composer.json` skeleton

```json
{
    "name": "nordkit/svea",
    "description": "Modern PHP SDK for Svea Checkout — Checkout, Admin, Subscriptions, and Webhooks",
    "type": "library",
    "license": "MIT",
    "require": {
        "php": "^8.2",
        "guzzlehttp/guzzle": "^7.8"
    },
    "require-dev": {
        "orchestra/testbench": "^10.0",
        "pestphp/pest": "^4.0",
        "pestphp/pest-plugin-laravel": "^4.0",
        "laravel/pint": "^1.0"
    },
    "suggest": {
        "illuminate/support": "Required for the Laravel facade and service provider"
    },
    "autoload": {
        "psr-4": {
            "Svea\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Svea\\Tests\\": "tests/"
        }
    },
    "extra": {
        "laravel": {
            "providers": ["Svea\\Laravel\\SveaServiceProvider"],
            "aliases": { "Svea": "Svea\\Laravel\\Svea" }
        }
    },
    "config": {
        "allow-plugins": {
            "pestphp/pest-plugin": true
        }
    }
}
```

### `SveaResource` base class

```php
<?php

declare(strict_types=1);

namespace Svea;

use ArrayAccess;

abstract class SveaResource implements ArrayAccess
{
    private ?Transport\SveaResponse $lastResponse = null;

    /** @param array<string, mixed> $data */
    public function __construct(protected array $data = []) {}

    /** @param array<string, mixed> $data */
    public static function make(array $data = []): static
    {
        return new static($data);
    }

    public function __get(string $key): mixed
    {
        return $this->data[$key] ?? null;
    }

    public function __isset(string $key): bool
    {
        return isset($this->data[$key]);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->data[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->data[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \BadMethodCallException('SveaResource is read-only.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \BadMethodCallException('SveaResource is read-only.');
    }

    /** @internal Called by SveaConnector after every HTTP request */
    public function withLastResponse(Transport\SveaResponse $response): static
    {
        $this->lastResponse = $response;

        return $this;
    }

    public function getLastResponse(): ?Transport\SveaResponse
    {
        return $this->lastResponse;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->data;
    }
}
```

### `SveaResponse` — wrapping PSR-7

```php
<?php

declare(strict_types=1);

namespace Svea\Transport;

use Psr\Http\Message\ResponseInterface;

final readonly class SveaResponse
{
    public int $statusCode;
    /** @var array<string, string[]> */
    public array $headers;
    public string $body;
    /** @var array<string, mixed> */
    public array $json;

    public function __construct(ResponseInterface $response)
    {
        $this->statusCode = $response->getStatusCode();
        $this->headers    = $response->getHeaders();
        $this->body       = (string) $response->getBody();
        $this->json       = json_decode($this->body, true) ?? [];
    }

    public function successful(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }
}
```

### `SveaConnector` — HMAC auth and response threading

The connector builds a Guzzle client per API surface (checkout, admin, subscriptions). After every request it:
1. Wraps the PSR-7 response in `SveaResponse`
2. Returns the raw JSON array — service classes construct the typed `SveaResource` and call `->withLastResponse()` themselves

```php
<?php

declare(strict_types=1);

namespace Svea\Transport;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\HandlerStack;
use Svea\Exceptions\SveaAuthenticationException;
use Svea\Exceptions\SveaConnectionException;
use Svea\Exceptions\SveaApiException;
use Svea\Exceptions\SveaNotFoundException;
use Svea\Exceptions\SveaRateLimitException;

final class SveaConnector
{
    private Client $client;

    /** @param array<string, mixed> $config */
    public function __construct(private readonly array $config, string $baseUrl, ?HandlerStack $handlerStack = null)
    {
        $stack = $handlerStack ?? HandlerStack::create();
        $stack->push(RetryMiddleware::make($config['max_retries'] ?? 0));

        $this->client = new Client([
            'base_uri' => rtrim($baseUrl, '/') . '/',
            'timeout'  => $config['timeout'] ?? 10,
            'handler'  => $stack,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function post(string $path, array $data = [], ?string $idempotencyKey = null): array
    {
        return $this->send('POST', $path, $data, $idempotencyKey);
    }

    /** @return array<string, mixed> */
    public function get(string $path): array
    {
        return $this->send('GET', $path);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function send(string $method, string $path, array $data = [], ?string $idempotencyKey = null): array
    {
        $body    = $data ? json_encode($data) : '';
        $headers = array_filter([
            'Authorization'   => $this->buildAuthHeader((string) $body),
            'Content-Type'    => 'application/json',
            'Idempotency-Key' => $idempotencyKey,
        ]);

        try {
            $response = $this->client->request($method, $path, [
                'headers' => $headers,
                'body'    => $body,
            ]);
        } catch (ConnectException $e) {
            throw new SveaConnectionException($e->getMessage(), previous: $e);
        }

        $sveaResponse = new SveaResponse($response);
        $this->throwForStatus($sveaResponse);

        return $sveaResponse->json;
    }

    /**
     * Build the Authorization header.
     * Format: SveaCheckoutGateway {merchantId} {base64(sha512(body + sharedSecret))}
     */
    private function buildAuthHeader(string $body): string
    {
        $digest = base64_encode(hash('sha512', $body . $this->config['shared_secret'], binary: true));

        return "SveaCheckoutGateway {$this->config['merchant_id']} {$digest}";
    }

    private function throwForStatus(SveaResponse $response): void
    {
        if ($response->successful()) {
            return;
        }

        throw match (true) {
            $response->statusCode === 401 => new SveaAuthenticationException($response),
            $response->statusCode === 404 => new SveaNotFoundException($response),
            $response->statusCode === 429 => new SveaRateLimitException($response),
            default                       => new SveaApiException($response),
        };
    }
}
```

> **Key rule:** Every service method that returns a `SveaResource` must call `->withLastResponse()` with the `SveaResponse` before returning.

### `SveaClient` — lazy service properties

Service properties (`->checkout`, `->admin`, etc.) are lazily resolved the first time they are accessed:

```php
<?php

declare(strict_types=1);

namespace Svea;

use GuzzleHttp\HandlerStack;
use Svea\Admin\AdminService;
use Svea\Checkout\CheckoutService;
use Svea\Subscriptions\SubscriptionService;
use Svea\Transport\SveaConnector;
use Svea\Webhooks\WebhookService;

final class SveaClient
{
    private ?CheckoutService $checkoutService = null;
    private ?AdminService $adminService = null;
    private ?SubscriptionService $subscriptionService = null;
    private ?WebhookService $webhookService = null;

    /** @param array<string, mixed> $config */
    public function __construct(
        private readonly array $config,
        private readonly ?HandlerStack $handlerStack = null,
    ) {}

    public function __get(string $name): mixed
    {
        return match ($name) {
            'checkout'      => $this->checkoutService     ??= new CheckoutService($this->makeConnector('checkout')),
            'admin'         => $this->adminService        ??= new AdminService($this->makeConnector('admin')),
            'subscriptions' => $this->subscriptionService ??= new SubscriptionService($this->makeConnector('subscriptions')),
            'webhook'       => $this->webhookService      ??= new WebhookService($this->config['webhook_secret'] ?? ''),
            default         => throw new \BadMethodCallException("Unknown service: {$name}"),
        };
    }

    private function makeConnector(string $surface): SveaConnector
    {
        $env     = $this->config['environment'] ?? 'test';
        $baseUrl = $this->config['base_urls'][$surface] ?? $this->defaultBaseUrl($surface, $env);

        return new SveaConnector($this->config, $baseUrl, $this->handlerStack);
    }

    private function defaultBaseUrl(string $surface, string $env): string
    {
        $urls = [
            'checkout'      => ['test' => 'https://checkoutapistage.svea.com',      'production' => 'https://checkoutapi.svea.com'],
            'admin'         => ['test' => 'https://paymentadminapistage.svea.com',   'production' => 'https://paymentadminapi.svea.com'],
            'subscriptions' => ['test' => 'https://paymentadminapistage.svea.com',   'production' => 'https://paymentadminapi.svea.com'],
        ];

        return $urls[$surface][$env]
            ?? throw new \InvalidArgumentException("No URL configured for [{$surface}][{$env}]");
    }
}
```

### Test bootstrap

`tests/Pest.php`:

```php
<?php

declare(strict_types=1);

uses(\Orchestra\Testbench\TestCase::class)
    ->in('Unit', 'Integration');
```

`tests/helpers.php` — create a pre-configured `SveaClient` for unit tests:

```php
<?php

declare(strict_types=1);

function testClient(array $overrides = []): \Svea\SveaClient
{
    return new \Svea\SveaClient(array_merge([
        'merchant_id'    => 'test-merchant',
        'shared_secret'  => 'test-secret',
        'environment'    => 'test',
        'webhook_secret' => 'webhook-secret',
        'max_retries'    => 0,
        'timeout'        => 5,
    ], $overrides));
}
```

Integration tests use Guzzle's `MockHandler` + `HandlerStack` to return canned HTTP responses without hitting the real Svea API:

```php
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

$mock = new MockHandler([
    new Response(201, [], json_encode(['OrderId' => 12345678, 'Gui' => ['Snippet' => '<div/>']]))
]);

$svea = new \Svea\SveaClient(
    config: ['merchant_id' => 'test', 'shared_secret' => 'secret', 'environment' => 'test'],
    handlerStack: HandlerStack::create($mock),
);
```

---

## Development Roadmap

| Phase | Milestone | Status |
|---|---|---|
| 1 | Scaffold: `composer.json`, PSR-4, CI (GitHub Actions + Pint + Pest) | ✅ |
| 2 | `SveaResource` base class (ArrayAccess, magic `__get`, `getLastResponse`) | ✅ |
| 3 | `SveaConnector` — Guzzle wrapper, HMAC auth, `RetryMiddleware` | ✅ |
| 4 | Exception hierarchy — all 8 exception classes | ✅ |
| 5 | `CheckoutService` + `CheckoutOrder` builder + `CheckoutResponse` | ✅ |
| 6 | `AdminService` + `AdminOrderRequest` + `AdminDeliveryRequest` + `TaskResponse` | ✅ |
| 7 | `SubscriptionService` + `SubscriptionBuilder` + `EventType` enum | ✅ |
| 8 | `Webhook::constructEvent()` + `SignatureVerifier` + `WebhookEvent` | ✅ |
| 9 | `FakeSveaClient` + `SveaFakeAssertions` + `preventStrayRequests()` | ✅ |
| 10 | `SveaClient` service properties + `SveaServiceProvider` + `Svea` facade | ✅ |
| 10a | Decouple core from Laravel — `src/Support/Conditionable`, `src/Laravel/`, PSR-7 webhook | ✅ |
| 11 | Full Pest suite — Unit + Integration (Guzzle `HandlerStack` stubs) | ✅ Unit (188 tests) / ✅ Integration (directory scaffolded) |
| 12 | Extract to standalone GitHub repo + Packagist | 🔲 |
