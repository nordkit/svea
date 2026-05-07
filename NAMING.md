# Naming & Structure Review Prompt

Use this prompt with an AI assistant to review and suggest improvements to class names and file structure.

---

Please review the class names and file structure of the **nordkit/svea** PHP package and suggest improvements for clarity, consistency, and convention.

## Package Overview

**nordkit/svea** is a modern PHP SDK for the Svea Checkout API. It covers all four API surfaces: **Checkout** (create/get/update orders), **Payment Admin** (deliver, cancel, credit orders and rows), **Webhook Subscriptions** (register, list, get, delete), and inbound **Webhook verification** (HMAC-SHA256 signature validation). The main entry point is `SveaClient` and the Laravel facade is `Svea`.

## Current File Structure

```
src/
├── SveaClient.php                        # Main entry point — lazily resolves services
├── SveaResource.php                      # Base class for all API response objects
│
├── Admin/
│   ├── AdminService.php                  # order(string $id): AdminOrderRequest, task(string $url): TaskResponse
│   ├── AdminOrderRequest.php             # Fluent builder: get, deliver, cancel, add/update/replace rows
│   ├── AdminOrderResponse.php            # Typed response: status(), canDeliver(), deliveryRowIds(), etc.
│   ├── AdminDeliveryRequest.php          # Delivery-scoped: credit(), creditAmount()
│   ├── AdminOrderRow.php                 # Fluent row builder: name, quantity, unitPrice, vatPercent, etc.
│   ├── CreditRequest.php                 # Fluent refund builder: rows(), newRow(), send()
│   ├── DeliverResponse.php               # ->deliveryId(), ->taskReference()
│   ├── TaskResponse.php                  # Async task resource: reference(), completed(), failed()
│   └── SveaOrderStatus.php               # Enum: Open, Delivered, Cancelled, Final
│
├── Checkout/
│   ├── CheckoutService.php               # create(), get(), update(), cancel()
│   ├── CheckoutOrder.php                 # Fluent order builder: currency, locale, addRow, merchantSettings
│   ├── CheckoutResponse.php              # ->id(), ->snippet(), ->status(), ->successful()
│   ├── Cart.php
│   ├── OrderRow.php                      # Fluent checkout row builder
│   ├── MerchantSettings.php
│   ├── PresetValue.php
│   ├── IdentityFlags.php
│   ├── ShippingInformation.php
│   ├── FallbackOption.php
│   └── Validation.php
│
├── Contracts/
│   ├── AdminServiceInterface.php
│   ├── CheckoutServiceInterface.php
│   └── SubscriptionServiceInterface.php
│
├── Exceptions/
│   ├── SveaException.php
│   ├── SveaApiException.php              # 4xx/5xx non-specific errors
│   ├── SveaAuthenticationException.php   # 401 responses
│   ├── SveaInvalidRequestException.php   # 400 validation errors, carries ->errors[]
│   ├── SveaConnectionException.php       # Transport-level failures (ConnectException)
│   ├── SveaNotFoundException.php         # 404 responses
│   ├── SveaRateLimitException.php        # 429 responses
│   └── SignatureVerificationException.php
│
├── Laravel/
│   ├── SveaServiceProvider.php           # Singleton binding, config publish — auto-discovered
│   ├── Svea.php                          # Facade → SveaClient; static assertXxx() proxies for testing
│   ├── WebhookService.php                # Illuminate\Http\Request → Webhook::constructEvent() bridge
│   └── Events/
│       └── SveaWebhookReceived.php       # Dispatchable event wrapping WebhookEvent
│
├── Subscriptions/
│   ├── SubscriptionService.php           # add(), list(), get(), update(), remove(), verify()
│   ├── SubscriptionBuilder.php           # ->on()->notifyAt()->register()
│   ├── Subscription.php                  # ->id(), ->events(), ->callbackUrl(), ->createdAt(), ->isVerified()
│   └── EventType.php                     # Enum — all 10 subscription event types
│
├── Support/
│   └── Conditionable.php                 # Trait: when() / unless() for builder chains
│
├── Testing/
│   ├── FakeSveaClient.php                # fake() named constructor; fakeCheckout(), fakeAdmin(), fakeSubscriptions()
│   ├── FakeCheckoutService.php           # Records calls, seeds responses, assertion helpers
│   ├── FakeAdminService.php              # order() returns FakeAdminOrderRequest
│   ├── FakeAdminOrderRequest.php         # Mirrors AdminOrderRequest for tests
│   ├── FakeAdminDeliveryRequest.php      # Mirrors AdminDeliveryRequest for tests
│   ├── FakeCreditRequest.php             # Mirrors CreditRequest for tests
│   ├── FakeSubscriptionService.php       # Records calls, seeds responses
│   └── SveaFakeAssertions.php            # Shared: assertCalled(), assertNotCalled(), assertCalledTimes(), preventStrayRequests()
│
├── Transport/
│   ├── SveaConnector.php                 # HTTP transport: HMAC auth, retry, idempotency key, error mapping
│   ├── SveaResponse.php                  # Wraps PSR-7: ->json, ->headers, ->statusCode, ->successful()
│   └── RetryMiddleware.php               # Exponential backoff on 429 / 5xx
│
└── Webhooks/
    ├── Webhook.php                       # static constructEvent() — pure static, no framework deps
    ├── WebhookService.php                # ->fromRequest(RequestInterface) — PSR-7 aware
    ├── WebhookEvent.php                  # ->type(), ->orderId(), ->deliveryId(), ->occurredAt()
    └── SignatureVerifier.php             # Pure HMAC-SHA256 logic — fully unit-testable
```

## Naming Decisions Already Made

- `SveaClient` — main entry point (intentional, matches the brand)
- `SveaResource` — base response class (direction-agnostic, reused across all surfaces)
- `AdminOrderRequest` — fluent builder (not `OrderRequest` to avoid collision with Checkout)
- `AdminOrderResponse` — typed response wrapper (paired with `AdminOrderRequest`)
- `CreditRequest` — refund builder scoped to a delivery (not `RefundRequest` to match Svea's own terminology)
- `TaskResponse` — async task polling resource (Svea calls them "tasks")
- `SveaOrderStatus` — enum in `Admin/` namespace (status values are admin-specific)
- `deliver()` — intentional: matches Svea's terminology ("deliver" = capture for BNPL)
- `credit()` — intentional: matches Svea's terminology ("credit" = refund)
- `FakeSveaClient` — test double entry point (parallel to `SveaClient`)
- `SveaFakeAssertions` — shared assertion state object (not a trait to allow construction)

## Questions to Consider

1. Are any class names inconsistent with each other or with Laravel conventions?
2. Should `AdminOrderRow` live in `Admin/` or be promoted to a shared `Support/` builder?
3. Is `CreditRequest` well-named given it lives inside `Admin/` but is reached via `AdminDeliveryRequest::credit()`?
4. Should `SveaOrderStatus` be `OrderStatus` (scoped by namespace) or stay prefixed for clarity?
5. Is `SveaConnector` the right name for the transport layer, or should it be `HttpClient` / `SveaHttpClient`?
6. Does the `Testing/` folder correctly separate fake doubles from the real implementation?
7. Are there any names that would surprise or confuse a developer using this package for the first time?

