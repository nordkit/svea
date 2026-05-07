# Changelog

All notable changes to `nordkit/svea` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.1.0] - 2026-05-08

### Added
- `docs/` — VitePress documentation site scaffold for **https://nordkit.github.io/svea/**:
  - `docs/.vitepress/config.ts` — site config (nav, sidebar, sitemap, OG meta, local search, edit-on-GitHub, last-updated, base path `/svea/`)
  - `docs/index.md` — home page with branded hero and feature grid (full API coverage, fluent & strict, first-class testing, production transport, framework-agnostic core, end-to-end docs)
  - `docs/guide/` — 11 narrative guides: getting-started, installation, quick-start, configuration, authentication, laravel, standalone, testing, error-handling, retries-idempotency, middleware
  - `docs/api/` — 5 reference pages: checkout, admin, subscriptions, webhooks, response-objects
  - `docs/public/logo.svg` — placeholder hero/favicon logo (Nordic blue gradient)
  - `docs/package.json` — VitePress 1.6, `dev`/`build`/`preview` scripts; `docs/.gitignore` for `node_modules/` and build output
  - `docs/README.md` — local development, structure, deployment, and custom-domain instructions
- `.github/workflows/docs.yml` — GitHub Actions workflow that builds and deploys the docs to GitHub Pages on every push to `main` touching `docs/**` (Node 20, npm cache, artifact upload + Pages deploy)
- `.gitattributes` — `docs/ export-ignore` so the docs source stays in the GitHub repo but ships out of the Composer dist tarball
- Laravel 13 support — README badge, requirements table, and `composer.json` `suggest` updated; `orchestra/testbench` constraint widened to `^10.0 || ^11.0` to cover testing on both Laravel 12 and 13
- README — link to the official Svea API documentation ([paymentsdocs.svea.com](https://paymentsdocs.svea.com/)) at the top of the file
- README — replaced static badge row with live Packagist version, total downloads, GitHub Actions test status, PHPStan level 6, PHP version (from `composer.json`), Laravel 11/12, and license badges
- README — new "At a glance" feature matrix summarising all supported capabilities (Checkout, Admin, Subscriptions, Webhooks, Laravel, fakes, idempotency, retries, async tasks, conditionable, typed exceptions, PHP 8.2–8.4)
- `composer.json` — added `keywords` array (`svea`, `svea-checkout`, `svea-payments`, `payments`, `payment-gateway`, `checkout`, `ecommerce`, `laravel`, `laravel-package`, `nordic`, `sweden`, `webhooks`, `sdk`, `php-sdk`) for Packagist discoverability
- `.github/FUNDING.yml` — enables the GitHub Sponsors button on the repo (`nordkit` org)
- `.github/ISSUE_TEMPLATE/bug_report.yml` — structured bug report form (version, PHP/Laravel, API surface, reproduction, expected/actual)
- `.github/ISSUE_TEMPLATE/feature_request.yml` — structured feature request form (problem, proposed API, alternatives, API surface, Svea docs link)
- `.github/ISSUE_TEMPLATE/config.yml` — disables blank issues; routes questions to Discussions, security to private advisories, and Svea-API questions to the official docs
- `.github/PULL_REQUEST_TEMPLATE.md` — PR checklist (Pint, Pest, PHPStan, CHANGELOG, README) with type/API-surface tagging

## [1.0.1] - 2026-05-07

### Fixed
- `NAMING.md` — file structure was significantly stale: `src/Laravel/Facades/Svea.php` corrected to `src/Laravel/Svea.php` (no `Facades/` subdirectory); `SveaServiceProvider` comment no longer references removed Wiretap integration; added missing `DeliverResponse.php`, `CheckoutResponse.php`, all `Checkout/` support classes, `Contracts/` directory, full `Subscriptions/` and `Webhooks/` listings, and `Events/SveaWebhookReceived.php`; `SubscriptionService` comment corrected from `register(), list(), get(), delete()` to `add(), list(), get(), update(), remove(), verify()`
- `CONTRIBUTING.md` — directory layout diagram updated to show correct `src/Laravel/` structure (`Svea.php`, `WebhookService.php`, `Events/`) and added `Contracts/` to module list; `composer.json` skeleton now includes `phpstan/phpstan: ^2.1` in `require-dev`; Roadmap Phase 11 no longer claims `tests/Integration/` is scaffolded (directory was removed)
- `README.md` — Package Structure section: fixed root path from `packages/svea/` (old monorepo path) to `src/`; removed stale `src/SveaServiceProvider.php` entry (file lives at `src/Laravel/SveaServiceProvider.php`); condensed the tree to a high-level overview — full annotated structure lives in `CONTRIBUTING.md`

## [1.0.0] - 2026-05-07

### Added
- PHPStan static analysis at level 6 — `phpstan/phpstan ^2.1` added to `require-dev`; `phpstan.neon` configured to analyse `src/` (excluding `src/Laravel/`); zero errors at level 6
- PHPStan job added to `tests.yml` CI workflow (runs on PHP 8.2, `--memory-limit=512M`); automatically included in the Release workflow via `workflow_call`
- PHP 8.3 and 8.4 added to the test matrix in `tests.yml`; PHP 8.5 added as an experimental (non-blocking) matrix entry
- `tests/Unit/` reorganised into subdirectories mirroring `src/`: `Admin/`, `Checkout/`, `Webhooks/`, `Laravel/`, `Testing/`, `Transport/`, `Core/` — `Subscriptions/` was already structured this way
- README — Subscriptions section expanded: intro explaining the push-notification model, comparison table (subscriptions vs task polling), full `EventType` reference table (all 10 cases with descriptions and Svea API strings), registration workflow with verification step, re-verification note after URL change
- README — Admin "Poll a task" section expanded: explains async nature of Admin API operations (HTTP 202), practical poll loop example, `pending()` method documented, production guidance (queued job)
- README — cross-reference added between Subscriptions and "Poll a task" (disambiguation table)

### Fixed
- `phpunit.xml` and `tests/Pest.php` — `Integration` testsuite pointed at non-existent `tests/Integration/`; removed from both config files; `vendor/bin/pest --compact` now exits cleanly
- `phpunit.xml` — `Feature` testsuite pointed at non-existent `tests/Feature/`; replaced with `Integration` testsuite pointing at `tests/Integration/`
- `SveaResource` — added `@implements ArrayAccess<string, mixed>` to satisfy PHPStan generics check
- `SveaResource::make()`, `AdminOrderRow::make()`, `CheckoutOrder::make()`, `OrderRow::make()` — added `@phpstan-ignore new.static` (return type is `static`, required for the factory-on-subclass pattern)
- `FakeAdminOrderRequest::$idempotencyKey` — added `@phpstan-ignore property.onlyWritten` with explanatory comment (key is intentionally stored but not forwarded — no real HTTP in the fake)
- `SveaResource::withLastResponse()` — removed stale `@internal` annotation; method is `public` by design (called by service classes) and must not carry `@internal`

### Changed
- `LICENSE.md` — added copyright year: `Copyright (c) Nordkit` → `Copyright (c) 2024–2026 Nordkit`
- `.gitignore` — added `*.log` and `.vscode/` entries
- `.gitattributes` — added `svea-bug-report.md export-ignore` safeguard entry
- `CONTRIBUTING.md` — Development Roadmap row 11: updated test count (144 → 188); marked Integration directory as ✅ (scaffolded)
- `RELEASING.md` — added Section 0 pre-release checklist (`pest`, `pint`, `phpstan`) before the changelog step; updated CI table to reflect PHPStan job

### Removed
- `svea-bug-report.md` — deleted scratch file that matched the `*-bug-report.md` exclusion pattern
- `tests/Integration/` — empty directory removed; integration-style tests are covered by the `MockHandler` approach documented in the README

### Changed (Documentation)
- README — Subscriptions "Available event types" section: added callout clarifying that checkout order `Finalized` is **not** a subscription event; it is delivered via the per-order merchant push (`pushUri`) as `{"type": "Finalized"}`; recipients must call `Svea::admin()->order($orderId)->get()` to read the Payment Admin status; corrected earlier note that incorrectly referenced `Svea::checkout()->get()` and omitted the `Finalized` push type name

### Changed (Documentation)
- README — added fluent callback style example for `checkout.create()` alongside the named-constructor form in the API Reference section
- README — split "Get / Update / Cancel" into three separate sections; `update()` now shows a full fluent callback example with its `CheckoutResponse` return value; `cancel()` notes it returns `void`
- README — added "Optional fields" snippet to the Checkout Create section documenting `merchantData()`, `partnerKey()`, `recurring()`, `requireElectronicIdAuthentication()`, and `metadata()`
- README — **bug fix**: `deliver()` was documented as returning `TaskResponse` (with `$task->reference`); corrected to `DeliverResponse` with `->deliveryId()` and `->taskReference()` in API Reference and Advanced Usage sections
- README — expanded Admin "Get order details" section with undocumented `AdminOrderResponse` helpers: `deliveries()`, `delivery()`, `deliveryRowIds()`, `hasAction()`, `hasStatus()`
- README — added new "Modify order rows" section documenting `addOrderRow()`, `updateOrderRow()`, and `replaceOrderRows()` with full fluent callback examples (these were entirely absent)
- README — updated Package Structure comments for `AdminOrderRequest.php`, `AdminOrderResponse.php`; added missing `DeliverResponse.php` entry

### Fixed
- `AdminOrderRequest::cancel()` — was sending `PUT` with `{"CancelledAmount": -1}`; corrected to `PATCH` with `{"IsCancelled": true}` per Svea Admin API spec
- `AdminOrderRequest::cancelAmount()` — was sending `PUT`; corrected to `PATCH` (payload unchanged)
- `AdminOrderRequest::cancelRow()` — was sending `PUT` to `/rows/{rowId}` with `{"IsCancelled": true}`; corrected to `PATCH` `/rows/cancelOrderRows/` with `{"OrderRowIds": [rowId]}` per Svea Admin API spec

### Changed (Test suite)
- `AdminOrderRequestTest` — added three new tests asserting correct HTTP method, endpoint, and payload for `cancel()`, `cancelAmount()`, and `cancelRow()`

### Changed (Test suite hygiene — `tests/`)
- `SignatureVerifierTest` — replaced meaningless `expect(true)->toBeTrue()` assertion with `expect(fn() => ...)->not->toThrow()`; added two new test cases: empty signature and uppercase (case-sensitive) signature mismatch
- `CheckoutOrderTest` — expanded from 1 test to 6: new coverage for all merchant URI setters (`confirmationUri`, `termsUri`, `checkoutUri`), multi-row accumulation, `OrderRow` minor-unit conversion for `quantity` and `vatPercent`, `discountPercent` conversion, and `clientOrderNumber`/`countryCode`
- `AdminOrderRequestTest` — added `@param`/`@return` docblock to `adminConnector()` helper; blank line added after `declare`
- `CreditRequestTest` — added `@param`/`@return` docblock to `creditConnector()` helper
- `SubscriptionTest` — added `@return array<string, mixed>` docblock to `subscriptionFixture()` helper
- All new test closures use explicit `: void` return types

### Added (New test files)
- `CheckoutServiceTest` — 5 new tests covering the real `CheckoutService` with Guzzle `MockHandler`: `create()` POST payload and response mapping, `getLastResponse()` attachment, `get()` GET to correct path, `update()` POST payload, `cancel()` POST to cancel endpoint
- `SveaConnectorExceptionMappingTest` — dataset-driven tests for HTTP status → exception class mapping (401 → `SveaAuthenticationException`, 404 → `SveaNotFoundException`, 429 → `SveaRateLimitException`, 400/500/503 → `SveaApiException`); asserts `$e->statusCode` and `$e->getLastResponse()` on `SveaApiException`; dataset of successful statuses (200/201/202/204) that must not throw
- `SveaOrderStatusTest` — dataset-driven tests for all 4 `SveaOrderStatus` enum cases: `from()` resolution, `value` string match, `tryFrom()` null for unknown, count assertion

### Changed (Laravel integration — `src/Laravel/`)
- `SveaServiceProvider` — removed all Wiretap wiring; the provider no longer references `nordkit/wiretap` in any way. HTTP tracing is now the responsibility of the consuming application — override the `SveaClient` singleton and inject a `HandlerStack` in your own service provider (see README for a Wiretap example)
- `WebhookService` (Laravel bridge) — expanded class-level PHPDoc documenting why this bridge layer exists (PSR-7 vs `Illuminate\Http\Request`), the container binding, and a full controller usage example; `fromRequest()` `@param`/`@throws` annotations added
- `Svea` (Facade) — expanded class-level PHPDoc with usage examples for all four API surfaces and the `fake()` testing workflow; added static assertion proxy methods (`assertCheckoutCreated`, `assertDelivered`, `assertCredited`, `assertCancelledOrder`, `assertTaskPolled`, `assertSubscriptionRegistered`, `assertSubscriptionAdded`, `assertSubscriptionFetched`, `assertSubscriptionsListed`, `assertSubscriptionUpdated`, `assertSubscriptionRemoved`, `assertSubscriptionVerified`, `assertNothingSent`) that delegate to the active `SveaFakeAssertions` — enabling the `Svea::assertDelivered(...)` pattern without storing the return value of `fake()`; private `resolveAssertions()` helper throws `RuntimeException` when called outside `fake()` context
- `SveaWebhookReceived` — expanded class-level PHPDoc with `dispatch()` usage, listener registration example, and `handle()` pattern with event type matching

### Changed (Testing — `src/Testing/`)
- `SveaFakeAssertions` — expanded class-level PHPDoc documenting generic vs domain-specific assertions and the facade shortcut pattern; added three new public assertion methods: `assertCalled(string $method)`, `assertNotCalled(string $method)`, `assertCalledTimes(string $method, int $times)`; improved `assertNothingSent()` docblock
- `FakeSveaClient` — expanded class-level PHPDoc with standalone and facade usage examples, full list of supported fake keys, and links to related classes; `webhook()` method documented to explain why the real `WebhookService` is returned (pure HMAC — no I/O to fake)
- `FakeAdminService` — expanded class-level PHPDoc listing all operation call keys and cross-linking to `FakeAdminOrderRequest`
- `FakeAdminOrderRequest` — expanded class-level PHPDoc with full list of supported call keys
- `FakeAdminDeliveryRequest` — expanded class-level PHPDoc documenting `credit()` and `creditAmount()` paths
- `FakeCreditRequest` — expanded class-level PHPDoc noting state-accumulation behaviour matching the real `CreditRequest`

### Added (Tests)
- `LaravelServiceProviderTest` — 11 new tests covering: container singleton binding, `WebhookService` binding, `svea` alias, `Svea::fake()` return type and singleton swap, seeded response passthrough, and all static assertion proxy methods including the `RuntimeException` guard
- `FakeSveaClientTest` — expanded from 8 to 30 tests: `assertCalled`, `assertNotCalled`, `assertCalledTimes` (including failure case), all subscription assertion methods, `assertCredited`, `assertTaskPolled`, and `webhook()` instance check

### Changed
- `AdminDeliveryRequest` — `creditAmount()` now uses PACH method instead of POST
- `SveaConnector` - Added patch method to support `AdminDeliveryRequest::creditAmount()`
- `SveaClient` — expanded class-level PHPDoc documenting all supported config keys, usage examples, and property-vs-method access distinction; added `@return`/`@throws` annotations to all public methods
- `SveaResource` — expanded class-level PHPDoc; all public methods now carry full `@param`/`@return`/`@throws` annotations; `offsetSet()` and `offsetUnset()` now throw `\BadMethodCallException` (response objects are read-only)

### Added
- `Svea\Contracts\CheckoutServiceInterface` — contract for `CheckoutService` and `FakeCheckoutService`; declares `create()`, `get()`, `update()`, `cancel()` with full PHPDoc
- `Svea\Contracts\SubscriptionServiceInterface` — contract for `SubscriptionService` and `FakeSubscriptionService`; declares `on()`, `list()`, `get()`, `add()`, `update()`, `remove()`, `verify()` with full PHPDoc

### Changed
- `AdminServiceInterface` — improved class-level and method PHPDoc; `order()` return type documented as `mixed` with rationale for the builder-compatibility pattern
- `CheckoutService` — now implements `CheckoutServiceInterface`; added method-level PHPDoc
- `SubscriptionService` — now implements `SubscriptionServiceInterface`
- `FakeCheckoutService` — now implements `CheckoutServiceInterface` instead of extending `CheckoutService`; removes the `// Skip parent::__construct` hack; added full method PHPDoc
- `FakeSubscriptionService` — now implements `SubscriptionServiceInterface`

### Changed (Exceptions)
- All 8 exception classes enriched with descriptive class-level PHPDoc
- `SveaException` — documents the complete exception hierarchy in its docblock
- `SveaApiException` — documents `$statusCode` and `$sveaError` properties; `@param`/`@return` on constructor and `getLastResponse()`
- `SveaInvalidRequestException` — documents `$errors` property and constructor `@param`
- `SveaAuthenticationException`, `SveaNotFoundException`, `SveaRateLimitException`, `SveaConnectionException`, `SignatureVerificationException` — expanded docblocks with context and handling guidance

### Changed (Transport)
- `SveaConnector` — expanded class-level PHPDoc documenting auth format, redirect behaviour, and config keys; full `@param`/`@throws` on all public and private methods
- `SveaResponse` — expanded class-level and property PHPDoc; documented the 302/303-as-success behaviour; `@param` on constructor and `@return`-equivalent note on `successful()`
- `RetryMiddleware` — expanded class-level PHPDoc with retry conditions, delay formula, and usage example; `@param`/`@return` on all methods

### Changed (Checkout)
- `CheckoutOrder` — expanded class-level PHPDoc with full usage example and `Conditionable` note; `@param` on all setter methods and `@return` on `toArray()`
- `CheckoutResponse` — expanded class-level PHPDoc listing all named getters; each getter documents what it returns and common values
- `OrderRow` — expanded class-level PHPDoc with unit conversion table and usage example; `@param` on all setter methods; internal × 100 conversions for `quantity`, `vatPercent`, and `discountPercent` documented
- `CheckoutService` — `@param` on all methods (added in Folder 2, noted here for completeness)

### Changed (Admin)
- `AdminService` — expanded class-level PHPDoc with usage example; `@param` on both public methods
- `AdminOrderRequest` — expanded class-level PHPDoc listing all operations and `Conditionable` example; `@param`/`@throws` on all public methods; constructor `@param` added
- `AdminOrderResponse` — expanded class-level PHPDoc with guard-gate example; full `@param`/`@return` on all methods
- `AdminDeliveryRequest` — expanded class-level PHPDoc with both credit strategies and examples; constructor and method `@param`/`@throws` added
- `CreditRequest` — expanded class-level PHPDoc with all three crediting strategies and examples; `@param`/`@throws` on all methods; constructor `@param` added
- `AdminOrderRow` — expanded class-level PHPDoc noting no SDK-level unit conversion (unlike Checkout `OrderRow`); `@param` on all setters
- `TaskResponse` — expanded class-level PHPDoc with polling pattern example; all methods annotated; `pending()` constructor documented
- `DeliverResponse` — expanded class-level PHPDoc; both methods annotated
- `SveaOrderStatus` — added class-level PHPDoc with doc link; inline case descriptions added

### Changed (Subscriptions)
- `SubscriptionService` — constructor `@param` added; all methods were already fully annotated
- `SubscriptionBuilder` — constructor `@param` added; all methods were already fully annotated
- `Subscription`, `EventType` — already had comprehensive docblocks; no changes needed

### Changed (Webhooks)
- `SignatureVerifier` — removed stale TODO comment; expanded class-level PHPDoc documenting HMAC-SHA256 algorithm, timing-safe comparison, and direct usage example; `@param`/`@throws` added to `verify()`
- `Webhook` — expanded class-level PHPDoc with full Laravel controller usage example and `match` dispatch pattern; `@param`/`@throws` added to `constructEvent()`
- `WebhookEvent` — expanded class-level PHPDoc with Svea payload shape; all methods annotated including `type()` null-return rationale for unknown future event types
- `WebhookService` — expanded class-level PHPDoc with DI binding and controller usage examples; constructor `@param` and `fromRequest()` `@param`/`@throws` added

### Changed (Support)
- `Conditionable` — expanded class-level PHPDoc documenting zero-dependency rationale, all classes that consume the trait, and a usage example; `@param` descriptions improved for both `when()` and `unless()`

### Added
- `SubscriptionService::add()`, `get()`, `update()`, `remove()`, `verify()` — full CRUD for webhook subscriptions, cross-validated against Svea Payments API docs
- `FakeSubscriptionService` — complete fake mirroring the full `SubscriptionService` API; added `get()`, `add()`, `update()`, `remove()`, `verify()`; fixed `delete()` → `remove()` and `EventTypes` → `Events` key
- `SveaFakeAssertions` — added `assertSubscriptionAdded()`, `assertSubscriptionFetched()`, `assertSubscriptionsListed()`, `assertSubscriptionUpdated()`, `assertSubscriptionRemoved()`, `assertSubscriptionVerified()`
- `SubscriptionBuilderTest` — 4 tests covering fluent chain payload, accessor correctness, `getLastResponse()`, and `on()` replacement behaviour
- `SubscriptionServiceTest` — 11 tests covering all service methods, HTTP verb/path assertions, error handling (401)
- `SubscriptionTest` — 15 tests covering all `Subscription` resource accessors and `SveaResource` base behaviour

### Fixed
- `SubscriptionService::list()` was treating `SveaResponse` as a plain array; corrected to use `$response->json`
- `EventTypeTest` — rewrote with correct enum cases (`CheckoutOrderCreated`, etc.); previous test referenced non-existent `PaymentDelivered`/`CheckoutCompleted` cases
- `AdminOrderRequestTest`, `AdminDeliveryRequestTest`, `CreditRequestTest` — were passing a `GuzzleHttp\Client` as the third `SveaConnector` constructor argument instead of `?HandlerStack`
- `FakeSveaClientTest`, `WebhookTest` — were referencing non-existent `EventType::PaymentDelivered`; updated to `EventType::CheckoutOrderCreated`
- `AdminOrderRequestTest` — was calling `$task->reference()` on `DeliverResponse` which exposes `taskReference()`, not `reference()`

### Changed
- All `Subscriptions/` source classes enriched with comprehensive PHPDoc: API endpoint refs, `@param`/`@return`/`@throws`, usage examples (`SubscriptionService`, `SubscriptionBuilder`, `Subscription`, `EventType`)
- `EventType` enum — confirmed all 10 cases against Svea API docs; enriched class-level PHPDoc with doc link
- README — corrected assertion method names (`assertSubscriptionRegistered`, added all new assertions), fixed stale `EventType::PaymentDelivered` references in webhook example, added `isVerified()` to subscription accessor docs, closed resolved open questions for base URLs and EventType list


### Added
- `SveaClient` — main entry point; lazily resolves `CheckoutService`, `AdminService`, `SubscriptionService`, and `WebhookService`
- `SveaConnector` — central HTTP transport with HMAC-SHA512 auth, idempotency key support, configurable retry middleware, and typed exception mapping (`SveaAuthenticationException`, `SveaNotFoundException`, `SveaRateLimitException`, `SveaApiException`, `SveaConnectionException`)
- `SveaResource` — base class for all API response objects; magic property access, `ArrayAccess`, and `withLastResponse()` for raw HTTP debugging
- `SveaResponse` — wraps PSR-7 responses; exposes `->json`, `->headers`, `->statusCode`, and `->successful()`
- `CheckoutService` — create, get, and update Svea Checkout orders
- `CheckoutOrder` — fluent builder for checkout order payloads; `currency()`, `locale()`, `countryCode()`, `clientOrderNumber()`, `pushUri()`, `confirmationUri()`, `termsUri()`, `checkoutUri()`, `addRow()`
- `OrderRow` — fluent builder for checkout order row items
- `AdminService` — `order(string $orderId)` returns `AdminOrderRequest`; `task(string $taskUrl)` polls async tasks
- `AdminOrderRequest` — fluent builder for all order operations: `get()`, `deliver()`, `cancel()`, `cancelAmount()`, `cancelRow()`, `delivery()`, `addOrderRow()`, `updateOrderRow()`, `replaceOrderRows()`; supports `withIdempotencyKey()`
- `AdminOrderResponse` — typed wrapper for order details; `status()`, `actions()`, `canDeliver()`, `canCredit()`, `canCancel()`, `deliveries()`, `delivery()`, `deliveryRowIds()`, `hasAction()`, `hasStatus()`
- `AdminDeliveryRequest` — delivery-scoped operations: `credit()` (returns `CreditRequest`), `creditAmount(int $amount)`
- `CreditRequest` — fluent refund builder: `rows(array $rowIds, ?array $rowCreditingOptions)`, `newRow(callable)`, `send()`
- `AdminOrderRow` — fluent builder for order row payloads: `name()`, `quantity()`, `unitPrice()`, `vatPercent()`, `sku()`, `discountPercent()`, `unit()`, `rowType()`, `temporaryReference()`, `merchantData()`
- `TaskResponse` — async task resource; `reference()`, `resource()`, `completed()`, `failed()`, `pending(string $reference)` named constructor
- `SveaOrderStatus` enum — `Open`, `Delivered`, `Cancelled`, `Final`
- `SubscriptionService` — register, list, get, and delete webhook subscriptions
- `WebhookService` — inbound webhook signature verification via HMAC-SHA256
- `SveaServiceProvider` — Laravel auto-discovered service provider; binds `SveaClient` as a singleton; publishes `config/svea.php`
- `Svea` facade — `Svea::checkout()`, `Svea::admin()`, `Svea::subscriptions()`, `Svea::webhook()`
- `FakeSveaClient` — test double with `fake()` named constructor; `fakeCheckout()`, `fakeAdmin()`, `fakeSubscriptions()`
- `FakeCheckoutService`, `FakeAdminService`, `FakeSubscriptionService` — record calls, seed responses, and assert interactions
- `FakeAdminOrderRequest`, `FakeAdminDeliveryRequest`, `FakeCreditRequest` — mirror the real API for unit tests
- `SveaFakeAssertions` — shared assertion helpers: `assertCalled()`, `assertNotCalled()`, `assertCalledTimes()`, `preventStrayRequests()`
- `Conditionable` trait — `when()` / `unless()` for inline conditional builder chains
- `RetryMiddleware` — configurable exponential-backoff retry on 429 and 5xx responses

[Unreleased]: https://github.com/nordkit/svea/compare/v1.1.0...HEAD
[1.1.0]: https://github.com/nordkit/svea/compare/v1.0.1...v1.1.0
[1.0.1]: https://github.com/nordkit/svea/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/nordkit/svea/releases/tag/v1.0.0

