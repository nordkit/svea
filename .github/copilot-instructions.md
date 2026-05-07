# Copilot Instructions

## Project

`nordkit/svea` is a modern PHP SDK for the Svea Checkout API. It covers all four API surfaces: Checkout (create/get/update orders), Payment Admin (deliver, cancel, credit), Webhook Subscriptions (register/list/get/delete), and inbound Webhook verification.

## Language & stack

- PHP 8.2+, framework-agnostic core
- Laravel integration in `src/Laravel/` only (service provider, facade)
- Tests: **Pest 4** only — never PHPUnit syntax
- Style: **Laravel Pint** (run `vendor/bin/pint` to fix)

## Conventions

- All source files use `declare(strict_types=1)`
- Value objects are `final readonly` classes
- No Laravel imports outside `src/Laravel/` — the core must remain framework-agnostic
- Prefer named constructor arguments in tests for readability
- No output in tests — use `expect()` assertions only
- Keep `README.md` up to date when adding or changing features, config keys, or public API
- Keep `CHANGELOG.md` updated under `[Unreleased]` as work progresses — never leave changes undocumented

## Releasing

- Versioning follows [Semantic Versioning](https://semver.org): bug fixes = PATCH, new features = MINOR, breaking = MAJOR
- Always update `CHANGELOG.md` using [Keep a Changelog](https://keepachangelog.com) format before releasing
- Move `[Unreleased]` content into a new versioned section with today's date
- Update the comparison links at the bottom of `CHANGELOG.md`
- Commit with `chore: prepare release vX.Y.Z` and push to `main`
- **Never tag manually** — trigger the Release workflow via GitHub Actions → Release → Run workflow (enter version without `v` prefix)

## Git

- Always use `git --no-pager` to prevent output from being blocked by a pager
- Always run `vendor/bin/pint` and `vendor/bin/pest` before committing — fix any errors before proceeding
- Commit messages use the [Conventional Commits](https://www.conventionalcommits.org) format (e.g. `fix:`, `feat:`, `chore:`)
- Push directly to `main` for release preparation commits; use PRs for feature work

## Documentation

- When the user says "svea docs", always refer to the MCP server Context7 library `svea payments` (library ID: `/websites/payments_svea`) for documentation lookups.
- When the user says "wiretap docs", always refer to the MCP server Context7 library `wiretap` (library ID: `/nordkit/wiretap`) for documentation lookups.
- When the user says "laravel docs", always refer to the MCP server Context7 library `Laravel` (library ID: `/websites/laravel_13_x`) for documentation lookups.
- When the user says "guzzle docs", always refer to the MCP server Context7 library `Guzzle` (library ID: `/guzzle/guzzle`) for documentation lookups.

## Key types

| Type | Purpose |
|---|---|
| `SveaClient` | Main entry point — lazily resolves service instances |
| `SveaConnector` | HTTP transport — HMAC auth, retry, idempotency, error mapping |
| `SveaResource` | Base class for all API response objects |
| `AdminOrderRequest` | Fluent builder for all Payment Admin order operations |
| `AdminOrderResponse` | Typed response wrapper with helper methods |
| `CreditRequest` | Fluent refund builder — rows, quantities, or new rows |
| `TaskResponse` | Async task polling resource |
| `FakeSveaClient` | Test double entry point — mirrors `SveaClient` API |
| `SveaFakeAssertions` | Shared assertion state for fake services |

