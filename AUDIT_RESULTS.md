# Release Audit Results — `nordkit/svea` v1.0.0
**Audited:** 2026-05-07  
**Verdict:** ❌ Not Ready — 2 blockers, 6 non-blocking issues

---

## Section 1 — Repository Hygiene

| # | Check | Status | Notes |
|---|---|---|---|
| 1.1 | No secrets/credentials/env files | ✅ Pass | — |
| 1.2 | `.gitignore` excludes all required paths | ⚠️ Partial | Missing `*.log` and `.vscode/` entries |
| 1.3 | `.gitattributes` export-ignores all dev files | ⚠️ Partial | `svea-bug-report.md` is tracked in git but NOT marked `export-ignore` |
| 1.4 | No scratch/debug files in repo | ❌ Fail | `svea-bug-report.md` is tracked in git — matches `*-bug-report.md` pattern |
| 1.5 | No references to `freightseeker-api-v2` in source/tests/config/docs | ⚠️ Partial | References exist only in `RELEASE_PROMPT.md`, `VALIDATE_PROMPT.md`, `EXTRACT_PROMPT.md` — all already `export-ignore`'d, not in src/tests |
| 1.6 | No hardcoded internal URLs/credentials in `src/` | ✅ Pass | — |

---

## Section 2 — `composer.json` & Packagist Readiness

| # | Check | Status | Notes |
|---|---|---|---|
| 2.1 | `name` = `nordkit/svea` | ✅ Pass | — |
| 2.2 | `description` suitable for Packagist | ✅ Pass | — |
| 2.3 | `license` = `MIT` | ✅ Pass | — |
| 2.4 | `type` = `library` | ✅ Pass | — |
| 2.5 | `require` has no `illuminate/*` | ✅ Pass | — |
| 2.6 | `illuminate/support` in `suggest` | ✅ Pass | — |
| 2.7 | `require-dev` contains only dev tools | ✅ Pass | — |
| 2.8 | `autoload` maps only `Svea\\ → src/` (no Testing/) | ✅ Pass | — |
| 2.9 | `autoload-dev` maps `Svea\\Tests\\ → tests/` | ✅ Pass | — |
| 2.10 | `extra.laravel` block present | ✅ Pass | — |
| 2.11 | No `repositories`/`path` entries | ✅ Pass | — |
| 2.12 | `minimum-stability` not `dev` | ✅ Pass | — |

---

## Section 3 — Changelog & Versioning

| # | Check | Status | Notes |
|---|---|---|---|
| 3.1 | Keep a Changelog format with `[1.0.0] - YYYY-MM-DD` | ✅ Pass | `[1.0.0] - 2026-04-20` |
| 3.2 | `[Unreleased]` section is intentionally open | ✅ Pass | Contains real post-1.0.0 changes already in code |
| 3.3 | `[1.0.0]` comprehensive for a new reader | ✅ Pass | — |
| 3.4 | No aspirational entries in `[Unreleased]` | ✅ Pass | — |
| 3.5 | Comparison links present and correct | ✅ Pass | Both links at bottom are correct |

---

## Section 4 — README Quality

| # | Check | Status | Notes |
|---|---|---|---|
| 4.1 | Opening paragraph clear, covers all 4 surfaces | ✅ Pass | — |
| 4.2 | Requirements table accurate | ✅ Pass | — |
| 4.3 | Installation instructions correct | ✅ Pass | — |
| 4.4 | Quick Start complete and runnable | ✅ Pass | — |
| 4.5 | All 4 API surfaces documented | ✅ Pass | — |
| 4.6 | Checkout `create()` shows both named + fluent styles | ✅ Pass | — |
| 4.7 | `deliver()` documents `DeliverResponse` (not `TaskResponse`) | ✅ Pass | `->deliveryId()` and `->taskReference()` documented correctly |
| 4.8 | Admin row management methods documented | ✅ Pass | `addOrderRow`, `updateOrderRow`, `replaceOrderRows` all present |
| 4.9 | Testing section complete | ✅ Pass | `fake()`, all `assertXxx()`, `preventStrayRequests()`, `MockHandler` |
| 4.10 | Error handling hierarchy shown | ✅ Pass | — |
| 4.11 | Configuration env vars with Required/Optional | ✅ Pass | — |
| 4.12 | Laravel section complete | ✅ Pass | Auto-discovery, facade, webhook event, Wiretap example |
| 4.13 | No broken internal anchor links | ✅ Pass | — |
| 4.14 | No placeholder text | ✅ Pass | — |
| 4.15 | Realistic variable names in examples | ✅ Pass | — |

---

## Section 5 — License

| # | Check | Status | Notes |
|---|---|---|---|
| 5.1 | `LICENSE.md` exists | ✅ Pass | — |
| 5.2 | Contains MIT text | ✅ Pass | — |
| 5.3 | Copyright year and holder correct | ⚠️ Partial | Reads `Copyright (c) Nordkit` — no year. Should be `Copyright (c) 2024–2026 Nordkit` |

---

## Section 6 — GitHub Repository Health

| # | Check | Status | Notes |
|---|---|---|---|
| 6.1 | `SECURITY.md` points to GitHub Security Advisories | ✅ Pass | — |
| 6.2 | `tests.yml` runs on push to `main` and PR | ✅ Pass | — |
| 6.3 | `tests.yml` tests PHP 8.2 | ✅ Pass | — |
| 6.4 | `tests.yml` tests a higher PHP version | ⚠️ Partial | Only PHP 8.2 matrix — no 8.3/8.4 (not blocking) |
| 6.5 | Separate Pest + Pint jobs | ✅ Pass | `test` and `lint` are separate jobs |
| 6.6 | `release.yml` requires CI before tagging | ✅ Pass | `needs: ci` on the tag job |
| 6.7 | No hardcoded credentials in workflows | ✅ Pass | — |
| 6.8 | Issue templates / PR template / CoC | ⚠️ Partial | None of these exist (not blocking for 1.0.0) |

---

## Section 7 — Public API Stability

| # | Check | Status | Notes |
|---|---|---|---|
| 7.1 | All public signatures intentional | ✅ Pass | — |
| 7.2 | `SveaClient::__get()` throws `BadMethodCallException` | ✅ Pass | Message includes the unknown service name |
| 7.3 | `SveaResource` is read-only | ✅ Pass | `offsetSet`/`offsetUnset` throw `BadMethodCallException` |
| 7.4 | `Svea::fake()` safe without `illuminate/support` | ✅ Pass | Lives in `src/Laravel/` — only auto-loaded with illuminate |
| 7.5 | No `public` methods marked `@internal` | ⚠️ Partial | `SveaResource::withLastResponse()` is `public` with `@internal`. Must stay public (called by service classes). **Fix: remove the `@internal` annotation.** |
| 7.6 | No enum over-exposure | ✅ Pass | — |
| 7.7 | Exception messages are developer-friendly | ✅ Pass | — |

---

## Section 8 — Test Suite Quality

| # | Check | Status | Notes |
|---|---|---|---|
| 8.1 | `vendor/bin/pest --compact` passes | ❌ **BLOCKER** | **Exit code 2. Zero tests run.** `phpunit.xml` declares a `Feature` testsuite pointing at `tests/Feature/` which does not exist. Pest aborts. CI is broken. Fix: replace `tests/Feature` with `tests/Unit` + `tests/Integration` in `phpunit.xml`. (188 tests pass when run directly via `php vendor/bin/pest tests/Unit tests/Integration`) |
| 8.2 | All tests use Pest syntax | ✅ Pass | No PHPUnit class-based tests found |
| 8.3 | No `expect(true)->toBeTrue()` | ✅ Pass | — |
| 8.4 | No tests hit the real Svea API | ✅ Pass | — |
| 8.5 | No hardcoded real credentials | ✅ Pass | — |
| 8.6 | All test files have `declare(strict_types=1)` | ✅ Pass | — |
| 8.7 | `AdminOrderRequestTest` uses `DeliverResponse::taskReference()` | ✅ Pass | Confirmed correct |
| 8.8 | Happy + unhappy path coverage | ✅ Pass | — |

---

## Section 9 — Code Style & Static Analysis

| # | Check | Status | Notes |
|---|---|---|---|
| 9.1 | `vendor/bin/pint --test` → 0 violations | ✅ Pass | Exit 0, 78 files checked |
| 9.2 | No `TODO`/`FIXME`/`HACK`/`XXX` in `src/` | ✅ Pass | — |
| 9.3 | No `use Illuminate\` outside `src/Laravel/` | ✅ Pass | — |
| 9.4 | All `.php` files have `declare(strict_types=1)` | ✅ Pass | — |
| 9.5 | PHPStan configured | ⚠️ Partial | No `phpstan.neon`, not in `require-dev`. Consider adding before/after 1.0.0. |

---

## Section 10 — CONTRIBUTING.md & Developer Experience

| # | Check | Status | Notes |
|---|---|---|---|
| 10.1 | Architecture, test setup, PR flow documented | ✅ Pass | — |
| 10.2 | Framework coupling rule documented | ✅ Pass | — |
| 10.3 | `RELEASING.md` step-by-step | ✅ Pass | — |
| 10.4 | Roadmap reflects actual state | ⚠️ Partial | Row 11 says "✅ Unit (144 tests)" — now 188 tests. Row 11 also shows "🔲 Integration" but `tests/Integration/` exists (empty dir). |
| 10.5 | `RELEASING.md` documents no `v` prefix | ✅ Pass | Explicitly stated |

---

## Summary of Issues

### ❌ Blockers (must fix before release)

| # | File | Issue | Fix |
|---|---|---|---|
| B1 | `phpunit.xml` | `vendor/bin/pest --compact` exits code 2 — CI is broken. `phpunit.xml` references `tests/Feature/` which does not exist; Pest aborts before running any tests. | Replace the `Feature` testsuite entry (`tests/Feature`) with `tests/Unit` and `tests/Integration`. |
| B2 | Root directory | `svea-bug-report.md` is tracked in git — matches the `*-bug-report.md` scratch-file exclusion pattern. | Delete the file, commit the deletion. Also add it to `.gitattributes` as `export-ignore` or keep it deleted. |

### ⚠️ Non-Blocking (fix before or shortly after release)

| # | File | Issue | Fix |
|---|---|---|---|
| N1 | `src/SveaResource.php` | `withLastResponse()` is `public` but annotated `@internal`. Annotation contracts (`pick one`) is violated. | Remove the `@internal` annotation — the method must stay public. |
| N2 | `LICENSE.md` | Copyright line missing year: `Copyright (c) Nordkit`. | Change to `Copyright (c) 2024–2026 Nordkit`. |
| N3 | `.gitignore` | Missing `*.log` and `.vscode/` entries. | Add both lines. |
| N4 | `.github/workflows/tests.yml` | CI matrix only covers PHP 8.2 — no forward-compat check. | Add a second matrix entry for PHP 8.3 or 8.4. |
| N5 | `CONTRIBUTING.md` | Development Roadmap row 11 shows stale test count (144 → 188) and marks Integration as `🔲` despite the directory existing. | Update count and mark Integration as `✅` (empty dir) or clarify. |
| N6 | None | No PHPStan configured. | Add `phpstan/phpstan` to `require-dev` and a `phpstan.neon`. |

---

## Fix Prompt

Use the following prompt to action all fixes in one pass:

```
You are fixing the `nordkit/svea` PHP package to make it ready for a public v1.0.0 release on GitHub and Packagist.
Apply every fix below. For each change, read the actual file first, make the minimal edit, then verify.

## Blockers

### B1 — Fix `phpunit.xml` (CRITICAL — CI is broken)
File: `packages/svea/phpunit.xml`
Problem: The `Feature` testsuite points at `tests/Feature/` which does not exist.
`vendor/bin/pest --compact` exits with code 2 and runs zero tests.
Fix: Replace the `<testsuite name="Feature"><directory>tests/Feature</directory></testsuite>` entry
with `<testsuite name="Integration"><directory>tests/Integration</directory></testsuite>`.
Verify: Run `vendor/bin/pest --compact` from `packages/svea/` — must exit 0 with 188 tests passing.

### B2 — Delete `svea-bug-report.md`
File: `packages/svea/svea-bug-report.md`
Problem: Tracked scratch file matching `*-bug-report.md` exclusion pattern.
Fix: Delete the file. Also add `svea-bug-report.md export-ignore` to `.gitattributes` as a safeguard in case it is re-created.
Verify: `git ls-files | grep bug-report` returns nothing.

## Non-Blockers

### N1 — Remove `@internal` from `SveaResource::withLastResponse()`
File: `packages/svea/src/SveaResource.php`
Problem: The method is `public` but annotated `@internal` — the audit rule says pick one.
The method must stay public (service classes call it). Remove the `@internal` line from the docblock.
Keep the descriptive note in the docblock but drop the `@internal` tag.

### N2 — Add copyright year to `LICENSE.md`
File: `packages/svea/LICENSE.md`
Problem: `Copyright (c) Nordkit` has no year.
Fix: Change to `Copyright (c) 2024–2026 Nordkit`.

### N3 — Add missing `.gitignore` entries
File: `packages/svea/.gitignore`
Problem: Missing `*.log` and `.vscode/` entries.
Fix: Append both lines to the end of the file.

### N4 — Add PHP 8.3 to CI matrix
File: `packages/svea/.github/workflows/tests.yml`
Problem: Only PHP 8.2 is tested.
Fix: Convert the `test` job to a matrix strategy with `php: [8.2, 8.3]`.
Update the job `name` to include the matrix PHP version.

### N5 — Update CONTRIBUTING.md roadmap
File: `packages/svea/CONTRIBUTING.md`
Problem: Row 11 says "✅ Unit (144 tests)" — count is now 188. Integration dir exists (empty).
Fix: Update row 11 to reflect 188 tests. Mark Integration as ✅ (directory scaffolded, no tests yet).

After all edits:
1. Run `vendor/bin/pint --dirty` from `packages/svea/` to fix any style violations.
2. Run `vendor/bin/pest --compact` from `packages/svea/` — must show 188 passed, exit 0.
3. Run `vendor/bin/pint --test` from `packages/svea/` — must show exit 0.
4. Confirm `git ls-files packages/svea/ | grep bug-report` is empty.
```

