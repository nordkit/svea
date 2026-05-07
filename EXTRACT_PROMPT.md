# Open-Source Extraction Prompt — nordkit/svea

Use this prompt with an AI assistant to review, critique, and plan the extraction of `nordkit/svea` into a standalone open-source PHP package.

---

## Context

**nordkit/svea** is a modern PHP SDK for the [Svea Payments](https://docs.payments.svea.com/) API. It is currently embedded as a path-repository inside a private Laravel application (`freightseeker-api-v2`) and needs to be extracted into its own standalone open-source package — ready to be published on Packagist, usable by any PHP 8.2+ project, and optionally enhanced with first-class Laravel support.

The goal is **not** to rewrite the SDK. The goal is to identify and fix any issues that would prevent it from being a high-quality, trustworthy open-source package — then prepare it for extraction.

---

## What the Package Does

The SDK covers four API surfaces:

| Surface | Entry point | What it does |
|---|---|---|
| **Checkout** | `$svea->checkout()` | Create, get, update, cancel checkout orders |
| **Payment Admin** | `$svea->admin()` | Deliver, cancel, credit orders and rows |
| **Webhook Subscriptions** | `$svea->subscriptions()` | Register, list, get, update, verify, delete subscriptions |
| **Inbound Webhooks** | `$svea->webhook()` | Verify HMAC-SHA256 signatures, parse events |

---

## Current File Structure

```
packages/svea/
├── composer.json                         # name: nordkit/svea, namespace: Svea\
├── config/
│   └── svea.php                          # Laravel config stub
├── src/
│   ├── SveaClient.php                    # Main entry point — lazily resolves all services
│   ├── SveaResource.php                  # Base class: ArrayAccess, magic __get, withLastResponse()
│   │
│   ├── Admin/
│   │   ├── AdminService.php              # order(id): AdminOrderRequest, task(url): TaskResponse
│   │   ├── AdminOrderRequest.php         # Fluent builder: get, deliver, cancel, add/update/replace rows
│   │   ├── AdminOrderResponse.php        # Typed response: status(), canDeliver(), deliveryRowIds()
│   │   ├── AdminDeliveryRequest.php      # Delivery-scoped: credit(), creditAmount()
│   │   ├── AdminOrderRow.php             # Fluent row builder
│   │   ├── CreditRequest.php             # Fluent refund builder: rows(), newRow(), send()
│   │   ├── TaskResponse.php              # Async task: reference(), completed(), failed()
│   │   └── SveaOrderStatus.php           # Enum: Open, Delivered, Cancelled, Final
│   │
│   ├── Checkout/
│   │   ├── CheckoutService.php           # create(), get(), update(), cancel()
│   │   ├── CheckoutOrder.php             # Fluent order builder: currency, locale, addRow, merchantSettings
│   │   ├── CheckoutResponse.php          # Typed checkout response
│   │   └── OrderRow.php                  # Fluent checkout row builder
│   │
│   ├── Contracts/
│   │   └── AdminServiceInterface.php     # Interface for AdminService (used by FakeAdminService)
│   │
│   ├── Exceptions/
│   │   ├── SveaApiException.php          # 4xx/5xx non-specific errors
│   │   ├── SveaAuthenticationException.php  # 401 responses
│   │   ├── SveaConnectionException.php   # Transport-level failures
│   │   ├── SveaNotFoundException.php     # 404 responses
│   │   ├── SveaRateLimitException.php    # 429 responses
│   │   └── SignatureVerificationException.php
│   │
│   ├── Laravel/
│   │   ├── SveaServiceProvider.php       # Singleton binding, Wiretap integration, config publish
│   │   ├── WebhookService.php            # Laravel-specific re-export? (check if duplicate)
│   │   └── Events/
│   │       └── SveaWebhookReceived.php   # Laravel event dispatched on inbound webhook
│   │
│   ├── Subscriptions/
│   │   ├── SubscriptionService.php       # on(), list(), get(), add(), update(), remove(), verify()
│   │   ├── SubscriptionBuilder.php       # Fluent: on(EventType...), notifyAt(), register()
│   │   ├── Subscription.php              # Resource: id(), callbackUrl(), events(), isVerified()
│   │   └── EventType.php                 # Enum of all Svea callback event types
│   │
│   ├── Support/
│   │   └── Conditionable.php             # Trait: when() / unless() for builder chains
│   │
│   ├── Testing/
│   │   ├── FakeSveaClient.php            # fake() named constructor; fakeCheckout/Admin/Subscriptions
│   │   ├── FakeCheckoutService.php       # Records calls, seeds responses, assertion helpers
│   │   ├── FakeAdminService.php          # order() returns FakeAdminOrderRequest
│   │   ├── FakeAdminOrderRequest.php     # Mirrors AdminOrderRequest for tests
│   │   ├── FakeAdminDeliveryRequest.php  # Mirrors AdminDeliveryRequest for tests
│   │   ├── FakeCreditRequest.php         # Mirrors CreditRequest for tests
│   │   ├── FakeSubscriptionService.php   # Records calls, seeds responses
│   │   └── SveaFakeAssertions.php        # assertCalled(), assertNotCalled(), preventStrayRequests()
│   │
│   ├── Transport/
│   │   ├── SveaConnector.php             # HTTP: HMAC-SHA512 auth, retry, idempotency key, error mapping
│   │   ├── SveaResponse.php              # Wraps PSR-7: ->json, ->headers, ->statusCode, ->successful()
│   │   └── RetryMiddleware.php           # Exponential backoff on 429/5xx
│   │
│   └── Webhooks/
│       ├── SignatureVerifier.php         # HMAC-SHA256 verify logic
│       ├── Webhook.php                   # constructEvent(): parses payload + verifies signature
│       ├── WebhookEvent.php              # Resource: eventType(), orderId(), payload
│       └── WebhookService.php            # PSR-7 wrapper: fromRequest(RequestInterface): WebhookEvent
│
└── tests/
    └── Unit/
        ├── AdminDeliveryRequestTest.php
        ├── AdminOrderRequestTest.php
        ├── AdminOrderResponseTest.php
        ├── CheckoutOrderTest.php
        ├── CreditRequestTest.php
        ├── FakeSveaClientTest.php
        ├── SignatureVerifierTest.php
        ├── Subscriptions/
        ├── SveaObjectTest.php
        ├── TaskResponseTest.php
        └── WebhookTest.php
```

---

## Known Issues & Coupling

1. **`SveaServiceProvider` imports `nordkit/wiretap`** — an optional internal HTTP tracing package. For open-source the Wiretap integration must be optional (guard with `class_exists`) or extracted to a separate integration package.
2. **`Laravel/WebhookService.php`** — may duplicate `Webhooks/WebhookService.php`. Needs clarification.
3. **Package name is `nordkit/svea`** — appropriate for open-source but should be verified.
4. **No integration tests** — `tests/Integration/` is empty. HTTP interactions use Guzzle mocks inline in unit tests.
5. **`Contracts/` has only one interface** — `AdminServiceInterface`. Consider whether `CheckoutServiceInterface` and `SubscriptionServiceInterface` should exist for consistency and testability.

---

## Your Task

Please review the package thoroughly and produce a prioritised action plan for extraction. Address each of the following areas:

### 1. Architecture & Contracts

- Are service interfaces (`CheckoutServiceInterface`, `SubscriptionServiceInterface`) missing and needed?
- Should `SveaResource` be an interface or abstract class? Is `ArrayAccess` the right contract for API responses?
- Is the `SveaClient` fluent lazy-resolution pattern idiomatic for a standalone SDK, or would a builder/factory be cleaner?
- Is the `Conditionable` trait (`when` / `unless`) pulling in Laravel coupling? Should it be a standalone trait or removed?

### 2. Laravel Integration Layer

- `SveaServiceProvider` currently hard-couples to `nordkit/wiretap`. How should this be decoupled for open-source?
- Should the Laravel layer (`src/Laravel/`) stay in the same package, or be extracted to a separate `nordkit/svea-laravel` package?
- Is `SveaWebhookReceived` the right design for Laravel event dispatching, or should the service fire it internally?
- The `Svea::` facade name is generic — is this a concern for namespace collision in user applications?

### 3. Naming Conventions

- `AdminOrderRequest` is a *fluent builder*, not a PSR request — is this confusing? Should it be `AdminOrderBuilder` or `AdminOrder`?
- `AdminDeliveryRequest` follows the same pattern — should it be `DeliveryBuilder` or `AdminDelivery`?
- `CreditRequest` lives in `Admin/` but is named without the `Admin` prefix — consistent or confusing?
- `SveaOrderStatus` is an enum in `Admin/` — should it drop the `Svea` prefix since it's already namespaced (`Svea\Admin\OrderStatus`)?
- `SveaConnector` vs `HttpClient` vs `SveaHttpClient` — which best describes the transport layer?
- `Webhooks/WebhookService` and `Laravel/WebhookService` — name collision risk, how to resolve?

### 4. File & Directory Structure

- Is the top-level `SveaClient.php` and `SveaResource.php` placement (root `src/`) correct, or should they move to a `Core/` or `Client/` subdirectory?
- The `Testing/` directory contains 8 fake classes — is this discoverable for consumers? Should it be documented more prominently?
- Should `Exceptions/` classes follow a hierarchy (e.g. all extend `SveaException` base)?
- The `Support/` folder has only `Conditionable.php` — will it grow, or should the trait move to its namespace?

### 5. Open-Source Readiness

- `composer.json` suggests `illuminate/support` but doesn't require it. Is the optional Laravel integration correctly declared?
- Are there any `declare(strict_types=1)` files missing?
- Is the `Testing/` namespace (`Svea\Testing\`) correct for a library — should it be excluded from production autoload?
- What CI/CD workflows (GitHub Actions) are needed: lint (Pint), test (Pest), static analysis (PHPStan)?
- Is the `CHANGELOG.md` format (Keep a Changelog) correct and complete?
- What minimum PHP version (currently `^8.2`) and Guzzle version (`^7.8`) make sense for the target audience?
- Should the package ship with a `phpstan.neon` config?
- Is there a `CONTRIBUTING.md`? Is it adequate?

### 6. Testing

- Unit tests exist but `Integration/` is empty — is this acceptable, or should at least Guzzle mock-based feature tests be added?
- Do the `Fake*` classes fully mirror their real counterparts' method signatures?
- Is the `SveaFakeAssertions` name clear to consumers, or should it be `SveaAssertions` / `SveaTestHelper`?
- Are datasets used where appropriate (e.g. for exception mapping, status enum)?

---

## Execution Order — Folder by Folder

Work through the source tree one directory at a time, applying all relevant improvements from the sections above (architecture, naming, docblocks, open-source readiness) before moving on. After each folder is complete, update `CHANGELOG.md` and `README.md`.

Suggested folder order:
1. ✅ `src/` root — `SveaClient.php`, `SveaResource.php`
2. ✅ `src/Contracts/` — interfaces
3. ✅ `src/Exceptions/` — exception hierarchy
4. ✅ `src/Transport/` — HTTP layer
5. ✅ `src/Checkout/` — checkout service and builders
6. ✅ `src/Admin/` — admin service and builders
7. ✅ `src/Subscriptions/` — subscription service and builders
8. ✅ `src/Webhooks/` — inbound webhook handling
9. ✅ `src/Support/` — shared traits
10. ✅ `src/Laravel/` — Laravel integration layer
11. ✅ `src/Testing/` — fakes and assertions
12. ✅ `tests/` — test suite hygiene

---

## Standing Requirements (apply to every step)

- **Docblocks** — every file touched during a step must have a descriptive class-level PHPDoc block, and all public methods must have `@param`, `@return`, and `@throws` annotations where applicable.
- **CHANGELOG** — after each step, add a bullet under `[Unreleased]` in `packages/svea/CHANGELOG.md` describing what changed.
- **README** — after each step, update `packages/svea/README.md` to reflect any new classes, interfaces, behaviour changes, or usage notes introduced in that step.

---

## Output Format

For each area above, provide:

1. **Verdict** — keep as-is / minor tweak / significant change needed
2. **Rationale** — one or two sentences explaining why
3. **Concrete suggestion** — the exact rename, move, or change to make (with before/after if renaming)

Then produce a **prioritised action checklist** grouped by:
- 🔴 **Blocking** — must fix before extraction (breaks consumers or signals poor quality)
- 🟡 **Important** — should fix before first stable release
- 🟢 **Nice to have** — improvements for v2 or post-launch

---

## Constraints

- PHP 8.2+ only
- No framework required for the core SDK (`src/` outside `Laravel/`)
- Laravel support is optional — protected by `class_exists` or `suggest` in `composer.json`
- Guzzle is the only HTTP client dependency (PSR-7 for inbound webhooks)
- The package should work without any Laravel-specific code loaded
- Do not suggest adding Symfony HTTP Client, Saloon, or other HTTP abstractions
- Preserve Svea's own terminology: `deliver`, `credit`, `task`, `subscription` — don't rename to generic payment terms

