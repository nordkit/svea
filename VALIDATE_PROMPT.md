# Validation Prompt — nordkit/svea Extraction Completeness

Use this prompt with an AI assistant to verify that **all work described in `EXTRACT_PROMPT.md` has been completed** before the package is extracted and published.

Go through every check below. For each item, inspect the actual source files and tests. Do **not** rely on `CHANGELOG.md` alone — cross-check against the real code.

---

## How to Run This Validation

For each section:
1. Read the relevant source files listed.
2. Apply every listed check — mark it ✅ **Pass**, ❌ **Fail**, or ⚠️ **Partial**.
3. If anything fails or is partial, note what is missing and create a follow-up task.
4. After all sections pass, confirm the package is ready for extraction.

---

## Section 1 — `src/` Root: `SveaClient.php` & `SveaResource.php`

**Files to inspect:** `src/SveaClient.php`, `src/SveaResource.php`

- [ ] `SveaClient` has a descriptive class-level PHPDoc block documenting all supported config keys, usage examples, and the property-vs-method access distinction.
- [ ] All public methods on `SveaClient` carry `@return` and `@throws` annotations.
- [ ] `SveaResource` has a descriptive class-level PHPDoc block.
- [ ] All public methods on `SveaResource` carry full `@param` / `@return` / `@throws` annotations.
- [ ] `SveaResource::offsetSet()` and `SveaResource::offsetUnset()` throw `\BadMethodCallException` (responses are read-only).
- [ ] Both files begin with `declare(strict_types=1)`.

---

## Section 2 — `src/Contracts/`

**Files to inspect:** `src/Contracts/AdminServiceInterface.php`, `src/Contracts/CheckoutServiceInterface.php`, `src/Contracts/SubscriptionServiceInterface.php`

- [ ] `AdminServiceInterface` exists with full PHPDoc.
- [ ] `CheckoutServiceInterface` exists, declaring `create()`, `get()`, `update()`, `cancel()` with full PHPDoc.
- [ ] `SubscriptionServiceInterface` exists, declaring `on()`, `list()`, `get()`, `add()`, `update()`, `remove()`, `verify()` with full PHPDoc.
- [ ] `CheckoutService` implements `CheckoutServiceInterface`.
- [ ] `SubscriptionService` implements `SubscriptionServiceInterface`.
- [ ] `FakeCheckoutService` implements `CheckoutServiceInterface` (not extends `CheckoutService`).
- [ ] `FakeSubscriptionService` implements `SubscriptionServiceInterface`.
- [ ] All three files begin with `declare(strict_types=1)`.

---

## Section 3 — `src/Exceptions/`

**Files to inspect:** all files in `src/Exceptions/`

- [ ] A base `SveaException` class exists and all other exception classes extend it.
- [ ] `SveaException` PHPDoc describes the complete exception hierarchy.
- [ ] `SveaApiException` documents `$statusCode` and `$sveaError` properties; constructor and `getLastResponse()` are annotated.
- [ ] `SveaInvalidRequestException` documents `$errors` and has a `@param` constructor annotation.
- [ ] `SveaAuthenticationException`, `SveaNotFoundException`, `SveaRateLimitException`, `SveaConnectionException`, `SignatureVerificationException` all have expanded PHPDoc with context and handling guidance.
- [ ] All files begin with `declare(strict_types=1)`.

---

## Section 4 — `src/Transport/`

**Files to inspect:** `src/Transport/SveaConnector.php`, `src/Transport/SveaResponse.php`, `src/Transport/RetryMiddleware.php`

- [ ] `SveaConnector` class-level PHPDoc documents auth format (HMAC-SHA512), redirect behaviour, and config keys.
- [ ] All public and private methods on `SveaConnector` have `@param` / `@throws` annotations.
- [ ] `SveaResponse` documents the 302/303-as-success behaviour.
- [ ] `RetryMiddleware` PHPDoc includes retry conditions, delay formula, and a usage example.
- [ ] All files begin with `declare(strict_types=1)`.

---

## Section 5 — `src/Checkout/`

**Files to inspect:** `src/Checkout/CheckoutService.php`, `src/Checkout/CheckoutOrder.php`, `src/Checkout/CheckoutResponse.php`, `src/Checkout/OrderRow.php`

- [ ] `CheckoutOrder` class-level PHPDoc includes a full usage example and mentions `Conditionable`.
- [ ] `OrderRow` PHPDoc includes a unit conversion table (×100 for `quantity`, `vatPercent`, `discountPercent`).
- [ ] `CheckoutResponse` lists all named getters in its PHPDoc.
- [ ] `CheckoutService` has `@param` on all methods.
- [ ] All files begin with `declare(strict_types=1)`.

---

## Section 6 — `src/Admin/`

**Files to inspect:** all files in `src/Admin/`

- [ ] `AdminService` class-level PHPDoc includes a usage example.
- [ ] `AdminOrderRequest` lists all supported operations and shows a `Conditionable` example; all methods have `@param` / `@throws`.
- [ ] `AdminOrderResponse` has a guard-gate usage example in its PHPDoc.
- [ ] `AdminDeliveryRequest` documents both credit strategies.
- [ ] `CreditRequest` documents all three crediting strategies.
- [ ] `AdminOrderRow` notes the absence of SDK-level unit conversion.
- [ ] `TaskResponse` PHPDoc shows a polling pattern example.
- [ ] `SveaOrderStatus` has case-level inline descriptions.
- [ ] `DeliverResponse` has class-level and method-level PHPDoc.
- [ ] All files begin with `declare(strict_types=1)`.

---

## Section 7 — `src/Subscriptions/`

**Files to inspect:** `src/Subscriptions/SubscriptionService.php`, `src/Subscriptions/SubscriptionBuilder.php`, `src/Subscriptions/Subscription.php`, `src/Subscriptions/EventType.php`

- [ ] `SubscriptionService` implements all CRUD methods: `on()`, `list()`, `get()`, `add()`, `update()`, `remove()`, `verify()`.
- [ ] Constructor `@param` is present on `SubscriptionService` and `SubscriptionBuilder`.
- [ ] `EventType` enum has all 10 cases confirmed against Svea API docs, with a class-level PHPDoc including a doc link.
- [ ] All files begin with `declare(strict_types=1)`.

---

## Section 8 — `src/Webhooks/`

**Files to inspect:** `src/Webhooks/SignatureVerifier.php`, `src/Webhooks/Webhook.php`, `src/Webhooks/WebhookEvent.php`, `src/Webhooks/WebhookService.php`

- [ ] `SignatureVerifier` PHPDoc documents the HMAC-SHA256 algorithm, timing-safe comparison, and a direct usage example.
- [ ] No stale TODO comments remain in `SignatureVerifier`.
- [ ] `Webhook` PHPDoc shows a full Laravel controller usage example with a `match` dispatch pattern.
- [ ] `WebhookEvent` PHPDoc describes the Svea payload shape; `type()` null-return for unknown event types is explained.
- [ ] `WebhookService` PHPDoc (core, not Laravel bridge) includes DI binding and controller usage examples.
- [ ] All files begin with `declare(strict_types=1)`.

---

## Section 9 — `src/Support/`

**Files to inspect:** `src/Support/Conditionable.php`

- [ ] `Conditionable` PHPDoc documents zero-dependency rationale, all consumer classes, and a usage example.
- [ ] No Laravel imports or coupling exist in this file.
- [ ] File begins with `declare(strict_types=1)`.

---

## Section 10 — `src/Laravel/`

**Files to inspect:** `src/Laravel/SveaServiceProvider.php`, `src/Laravel/WebhookService.php`, `src/Laravel/Svea.php` (Facade), `src/Laravel/Events/SveaWebhookReceived.php`

- [ ] `SveaServiceProvider` has **no** top-level `use` imports for `nordkit/wiretap`.
- [ ] Wiretap integration is guarded by both `class_exists('Nordkit\Wiretap\Guzzle\WiretapClient')` **and** `$app->bound(...)`.
- [ ] FQCNs are used inside the Wiretap conditional block.
- [ ] `SveaServiceProvider` PHPDoc explains all bindings, the Wiretap integration, and publish/register usage.
- [ ] `WebhookService` (Laravel bridge) PHPDoc explains why the bridge exists (PSR-7 vs `Illuminate\Http\Request`) and includes a controller usage example.
- [ ] `Svea` Facade PHPDoc shows usage examples for all four API surfaces and the `fake()` testing workflow.
- [ ] `Svea` Facade has static assertion proxy methods: `assertCheckoutCreated`, `assertDelivered`, `assertCredited`, `assertCancelledOrder`, `assertTaskPolled`, `assertSubscriptionRegistered`, `assertSubscriptionAdded`, `assertSubscriptionFetched`, `assertSubscriptionsListed`, `assertSubscriptionUpdated`, `assertSubscriptionRemoved`, `assertSubscriptionVerified`, `assertNothingSent`.
- [ ] `Svea::resolveAssertions()` throws `RuntimeException` when called outside a `fake()` context.
- [ ] `SveaWebhookReceived` PHPDoc shows `dispatch()` usage, listener registration, and `handle()` pattern.
- [ ] All files begin with `declare(strict_types=1)`.

---

## Section 11 — `src/Testing/`

**Files to inspect:** all files in `src/Testing/`

- [ ] `SveaFakeAssertions` has `assertCalled(string $method)`, `assertNotCalled(string $method)`, `assertCalledTimes(string $method, int $times)`.
- [ ] `SveaFakeAssertions` PHPDoc documents generic vs domain-specific assertions and the facade shortcut pattern.
- [ ] `FakeSveaClient` PHPDoc shows standalone and facade usage examples, full list of supported fake keys, and links to related classes.
- [ ] `FakeSveaClient::webhook()` is documented to explain why the real `WebhookService` is returned.
- [ ] `FakeSubscriptionService` mirrors all `SubscriptionService` methods: `on()`, `list()`, `get()`, `add()`, `update()`, `remove()`, `verify()`.
- [ ] `FakeAdminService`, `FakeAdminOrderRequest`, `FakeAdminDeliveryRequest`, `FakeCreditRequest` all have expanded class-level PHPDoc.
- [ ] `Testing/` classes are excluded from production autoload in `composer.json`.
- [ ] All files begin with `declare(strict_types=1)`.

---

## Section 12 — `tests/`

**Files to inspect:** all files in `tests/Unit/` and `tests/Feature/` (if present)

- [ ] `SignatureVerifierTest` has `expect(fn() => ...)->not->toThrow()` assertions (not `expect(true)->toBeTrue()`); includes empty signature and uppercase mismatch cases.
- [ ] `CheckoutOrderTest` covers: all merchant URI setters, multi-row accumulation, `OrderRow` unit conversions (`quantity`, `vatPercent`, `discountPercent`, `discountPercent`), `clientOrderNumber`, `countryCode`.
- [ ] `CheckoutServiceTest` exists with ≥5 tests: `create()` POST payload, `getLastResponse()`, `get()`, `update()`, `cancel()`.
- [ ] `SveaConnectorExceptionMappingTest` exists with dataset-driven tests mapping HTTP status codes to exception classes (401, 404, 429, 400, 500, 503).
- [ ] `SveaOrderStatusTest` exists with dataset-driven tests for all 4 `SveaOrderStatus` cases.
- [ ] `SubscriptionBuilderTest` exists with ≥4 tests.
- [ ] `SubscriptionServiceTest` exists with ≥11 tests covering all service methods plus error handling.
- [ ] `SubscriptionTest` exists with ≥15 tests covering all `Subscription` resource accessors.
- [ ] `LaravelServiceProviderTest` exists with ≥11 tests including: container binding, `WebhookService` binding, `svea` alias, `Svea::fake()` return type, seeded response passthrough, and all static assertion proxy methods.
- [ ] `FakeSveaClientTest` has ≥30 tests including: `assertCalled`, `assertNotCalled`, `assertCalledTimes`, all subscription assertions, `assertCredited`, `assertTaskPolled`, `webhook()` instance check.
- [ ] No tests use PHPUnit syntax — all tests use Pest `test()` / `it()` / `expect()`.
- [ ] No tests use `expect(true)->toBeTrue()` as a presence check.
- [ ] All test files begin with `declare(strict_types=1)`.

---

## Section 13 — `composer.json` & Open-Source Readiness

**Files to inspect:** `composer.json`, `phpstan.neon` (if present), `.github/workflows/` (if present)

- [ ] `composer.json` has `name: nordkit/svea`.
- [ ] Minimum PHP version is `^8.2`.
- [ ] `illuminate/support` (and other Laravel packages) are in `suggest`, not `require`.
- [ ] `Testing/` namespace is in `autoload-dev`, not `autoload`.
- [ ] `phpstan.neon` exists with a sensible baseline level.
- [ ] GitHub Actions workflow exists for: lint (`vendor/bin/pint --test`), tests (`vendor/bin/pest`), and static analysis (`phpstan`).
- [ ] `CHANGELOG.md` is in Keep a Changelog format and covers all work done.
- [ ] `README.md` documents all four API surfaces, the Testing layer, the Laravel integration, and the `Svea` Facade assertion proxies.
- [ ] `CONTRIBUTING.md` exists (or the absence is an accepted trade-off — document the decision).

---

## Section 14 — Final Cross-Cutting Checks

- [ ] Run `vendor/bin/pint --test` — zero style violations.
- [ ] Run `vendor/bin/pest` — all tests pass with zero failures or skips.
- [ ] Run `vendor/bin/phpstan analyse src` (if PHPStan is configured) — zero errors at the declared level.
- [ ] Grep for any remaining `use Nordkit\Wiretap` at the top level of non-Laravel files — must return nothing.
- [ ] Grep for `declare(strict_types=1)` — must appear in every `.php` file under `src/` and `tests/`.
- [ ] Grep for `TODO` / `FIXME` / `HACK` — resolve or document every hit.
- [ ] Confirm `packages/svea/` has no path-repository coupling to `freightseeker-api-v2` in its own `composer.json`.

---

## Pass Criteria

The package is ready to extract when **every checkbox above is ✅ Pass** and:

- `vendor/bin/pint --test` exits 0
- `vendor/bin/pest` exits 0
- No unresolved TODO / FIXME comments in `src/`
- `CHANGELOG.md` `[Unreleased]` section is either empty or intentionally left open for the next release cycle

