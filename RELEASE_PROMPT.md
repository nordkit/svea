# Release Readiness Prompt — `nordkit/svea` v1.0.0 Public GitHub Release

Use this prompt with an AI assistant to verify the package is ready for a **public first release on GitHub and Packagist**. This is a public-facing audit — go beyond internal correctness and apply the eye of an external developer encountering this package for the first time.

Go through every section below. Inspect the actual files — do **not** rely on `CHANGELOG.md` or memory alone. Mark each item ✅ **Pass**, ❌ **Fail**, or ⚠️ **Partial**, note what is missing, and create a follow-up task before proceeding.

---

## Section 1 — Repository Hygiene

**Files to inspect:** `.gitignore`, `.gitattributes`, root directory listing, `composer.json`

- [ ] No secrets, credentials, or `.env` files are present anywhere in the repository.
- [ ] `.gitignore` excludes `vendor/`, `.DS_Store`, `*.log`, `coverage/`, and IDE directories (`.idea/`, `.vscode/`).
- [ ] `.gitattributes` marks development-only files as `export-ignore` so they are excluded from `composer install` archives: `CHANGELOG.md`, `CONTRIBUTING.md`, `RELEASING.md`, `RELEASE_PROMPT.md`, `VALIDATE_PROMPT.md`, `EXTRACT_PROMPT.md`, `NAMING.md`, `tests/`, `phpunit.xml`, `.github/`.
- [ ] No leftover debug files, scratch files (`debug_task.php`, `todo.txt`, `*-bug-report.md`, `pending_task_reference`), or internal planning documents exist.
- [ ] No references to the host application (`freightseeker-api-v2`) in any source file, test, config, or documentation.
- [ ] No internal URLs, staging credentials, or merchant IDs are hardcoded anywhere in `src/`.

---

## Section 2 — `composer.json` & Packagist Readiness

**Files to inspect:** `composer.json`

- [ ] `name` is `nordkit/svea`.
- [ ] `description` is clear and suitable for a Packagist listing.
- [ ] `license` is `MIT`.
- [ ] `type` is `library`.
- [ ] `require` contains only `php: ^8.2`, `guzzlehttp/guzzle`, and `psr/http-message`. No `illuminate/*` packages in `require`.
- [ ] `illuminate/support` is in `suggest`, not `require`, with a clear description of when it is needed.
- [ ] `require-dev` contains only development tools (Pest, Pint, Testbench).
- [ ] `autoload` maps only `Svea\\` → `src/`. `Testing/` namespace is **not** in `autoload` (production autoload must be clean).
- [ ] `autoload-dev` maps `Svea\\Tests\\` → `tests/`.
- [ ] `extra.laravel` block is present for auto-discovery: `providers` and `aliases`.
- [ ] No `repositories` or `path` entries pointing to `freightseeker-api-v2` or any local path.
- [ ] `minimum-stability` is not set to `dev` (or is omitted entirely).

---

## Section 3 — Changelog & Versioning

**Files to inspect:** `CHANGELOG.md`

- [ ] Format follows [Keep a Changelog](https://keepachangelog.com) with `## [1.0.0] - YYYY-MM-DD` section.
- [ ] The `[Unreleased]` section above `[1.0.0]` is either empty or intentionally left open for the next cycle.
- [ ] `[1.0.0]` section is comprehensive — a new reader can understand the full scope of what is included.
- [ ] No `[Unreleased]` content describes work that is **not yet in `main`** (i.e. no aspirational changelog entries).
- [ ] Comparison links at the bottom are present and correct:
  - `[Unreleased]: https://github.com/nordkit/svea/compare/v1.0.0...HEAD`
  - `[1.0.0]: https://github.com/nordkit/svea/releases/tag/v1.0.0`

---

## Section 4 — README Quality (Public Audience)

**Files to inspect:** `README.md`

- [ ] The opening paragraph clearly states what the package does and which Svea API surfaces it covers.
- [ ] Requirements table is accurate (`PHP ^8.2`, Guzzle, optional `illuminate/support`).
- [ ] Installation instructions are correct (`composer require nordkit/svea`).
- [ ] Quick Start example is complete and runnable — a developer can copy it verbatim and have a working checkout.
- [ ] All four API surfaces are documented in the API Reference: Checkout, Admin, Subscriptions, Webhooks.
- [ ] The Checkout `create()` section shows **both** the named constructor and fluent callback styles.
- [ ] The Admin `deliver()` section correctly documents `DeliverResponse` return type (`->deliveryId()`, `->taskReference()`), not `TaskResponse`.
- [ ] Admin row management methods are documented: `addOrderRow()`, `updateOrderRow()`, `replaceOrderRows()`.
- [ ] The Testing section documents `Svea::fake()`, all `assertXxx()` methods, `preventStrayRequests()`, and the Guzzle `MockHandler` low-level alternative.
- [ ] Error handling section shows the full exception hierarchy tree.
- [ ] Configuration section lists all environment variables with Required/Optional status.
- [ ] Laravel integration section covers: auto-discovery, facade usage, webhook event dispatch, Wiretap middleware example.
- [ ] No broken internal links (run a link checker or manually verify all `#anchor` TOC links).
- [ ] No placeholder text (`...`, `TODO`, `FIXME`, `[LINK]`).
- [ ] Code examples use consistent, realistic variable names — not `$foo`, `$bar`.

---

## Section 5 — License

**Files to inspect:** `LICENSE.md`

- [ ] `LICENSE.md` exists at the repo root.
- [ ] It contains the MIT license text.
- [ ] The copyright year and holder are correct.

---

## Section 6 — GitHub Repository Health

**Files to inspect:** `.github/SECURITY.md`, `.github/workflows/tests.yml`, `.github/workflows/release.yml`

- [ ] `SECURITY.md` exists and points to GitHub Security Advisories for private reporting (not a public issue).
- [ ] `tests.yml` workflow runs on `push` to `main` and on `pull_request`.
- [ ] `tests.yml` runs tests on PHP **8.2** (the declared minimum).
- [ ] `tests.yml` also runs on at least one higher PHP version (e.g. 8.3 or 8.4) to catch forward-compatibility issues. *(If missing, note as ⚠️ Partial — not blocking.)*
- [ ] `tests.yml` runs both the Pest test suite and the Pint style check as separate jobs.
- [ ] `release.yml` workflow exists and requires CI to pass before creating a tag.
- [ ] No workflow step hard-codes credentials or tokens in plain text.
- [ ] Consider adding: issue templates, pull request template, `CODE_OF_CONDUCT.md`. *(Note if absent — not blocking for 1.0.0.)*

---

## Section 7 — Public API Stability (1.0.0 Contract)

Read through `src/` with the eye of a developer writing code against this package after `composer require`.

- [ ] All public method signatures are intentional and stable — nothing is `public` that should be `internal` or `private`.
- [ ] `SveaClient::__get()` throws `\BadMethodCallException` for unknown service names (not a generic PHP error).
- [ ] `SveaResource` responses are truly read-only — `offsetSet()` and `offsetUnset()` throw `\BadMethodCallException`.
- [ ] `Svea::fake()` is only available when `illuminate/support` is installed — it does not cause a fatal error in standalone usage.
- [ ] No `public` methods are marked `@internal` (move them to `protected`/`private` or remove the annotation — pick one).
- [ ] Enums expose only what is needed publicly — no accidental over-exposure of internal state.
- [ ] The error messages in thrown exceptions are developer-friendly (include the relevant value, not just "invalid argument").

---

## Section 8 — Test Suite Quality

**Files to inspect:** all files under `tests/`

- [ ] Run `vendor/bin/pest --compact` — **zero failures, zero errors, zero skipped tests**.
- [ ] All tests use Pest syntax (`test()` / `it()` / `expect()`) — no PHPUnit class-based tests.
- [ ] No `expect(true)->toBeTrue()` assertions used as presence checks.
- [ ] No tests hit the real Svea API — all HTTP is mocked via `Svea::fake()` or Guzzle `MockHandler`.
- [ ] No hardcoded real merchant IDs, shared secrets, or webhook secrets in test files.
- [ ] All test files begin with `declare(strict_types=1)`.
- [ ] `AdminOrderRequestTest` — `deliver()` assertions reference `DeliverResponse::taskReference()`, not `TaskResponse::reference()`.
- [ ] Tests cover the happy path **and** at least one unhappy path (error/exception) for each API surface.

---

## Section 9 — Code Style & Static Analysis

- [ ] Run `vendor/bin/pint --test` — **zero violations**.
- [ ] Grep `src/` for `TODO`, `FIXME`, `HACK`, `XXX` — resolve every hit or document an accepted trade-off.
- [ ] Grep `src/` (excluding `src/Laravel/`) for `use Illuminate\\` — must return nothing (core has zero Laravel coupling).
- [ ] Grep all `.php` files under `src/` and `tests/` for `declare(strict_types=1)` — every file must have it.
- [ ] If `phpstan.neon` exists: run `vendor/bin/phpstan analyse src` — zero errors at the declared level.
- [ ] If PHPStan is **not** configured: note it as ⚠️ Partial — consider adding it before 1.0.0 ships.

---

## Section 10 — CONTRIBUTING.md & Developer Experience

**Files to inspect:** `CONTRIBUTING.md`, `RELEASING.md`

- [ ] `CONTRIBUTING.md` explains the architecture, how to run tests locally, and how to submit a PR.
- [ ] `CONTRIBUTING.md` explains the framework coupling rule (core = zero `illuminate/*` dependencies).
- [ ] `RELEASING.md` documents the release process step by step (update changelog → trigger workflow → tag is created by CI only).
- [ ] The development roadmap in `CONTRIBUTING.md` reflects the actual current state — no ✅ items that are missing in the code, no 🔲 items that are actually complete.
- [ ] `RELEASING.md` makes it clear that the `v` prefix must **not** be included when entering the version in the Release workflow input.

---

## Pass Criteria

The package is ready for a public v1.0.0 GitHub release when **every checkbox above is ✅ Pass** and:

| Check | Command | Expected |
|---|---|---|
| Tests | `vendor/bin/pest --compact` | All pass, 0 skipped |
| Style | `vendor/bin/pint --test` | Exit 0, 0 violations |
| Static analysis | `vendor/bin/phpstan analyse src` *(if configured)* | 0 errors |
| Secrets scan | `grep -r "SVEA_\|merchant_id\|shared_secret" src/` | Only config key names, never real values |
| Laravel coupling | `grep -r "use Illuminate" src/ --include="*.php" \| grep -v Laravel` | No results |
| Strict types | `grep -rL "declare(strict_types=1)" src/ tests/` | No results |
| Debug artifacts | `find . -name "*.php" \| xargs grep -l "var_dump\|dd(\|dump("` | No results |

Once all checks pass: follow `RELEASING.md` to move `[Unreleased]` into `[1.0.0]`, commit `chore: prepare release v1.0.0`, and trigger the Release workflow on GitHub.

