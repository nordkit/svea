# Introduction

`nordkit/svea` is a modern PHP SDK for the [Svea Payments](https://paymentsdocs.svea.com/) APIs.

It covers all four Svea API surfaces:

- **Checkout** — create, get, update, cancel orders
- **Payment Admin** — deliver, cancel, credit, modify rows
- **Webhook Subscriptions** — full CRUD and verification
- **Inbound Webhook verification** — HMAC-SHA256, timing-safe

The core is **framework-agnostic** (PHP 8.2+, no Laravel required). A dedicated **Laravel integration** ships in `src/Laravel/` with a service provider, facade, Artisan commands, and an event-based webhook bridge.

## Why this SDK?

| | nordkit/svea | Official PHP SDK |
| --- | --- | --- |
| PHP version | 8.2+ | older PHP versions |
| `final readonly` value objects | ✅ | ❌ |
| Fluent builders + `when()` / `unless()` | ✅ | ❌ |
| Typed exception hierarchy | ✅ | mostly array errors |
| Test doubles (`Svea::fake()`) | ✅ | ❌ |
| First-class Laravel integration | ✅ | manual wiring |
| Strict types, PHPStan level 6 | ✅ | — |

## Next steps

- [Install the package](./installation)
- [Quick start](./quick-start)
- [Configure for your environment](./configuration)
- [Laravel integration guide](./laravel)

> 📖 For the canonical Svea API contract see the official docs at [paymentsdocs.svea.com](https://paymentsdocs.svea.com/).

