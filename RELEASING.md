# Releasing

This document describes how to cut a new release of `nordkit/svea`.

> **Never create or push a tag manually.** Tags are created by the [Release workflow](.github/workflows/release.yml) only after all CI checks pass. A manually pushed tag bypasses those checks.

---

## Prerequisites

- Write access to the `main` branch on GitHub
- All changes merged into `main`
- `CHANGELOG.md` updated (see below)

---

## 0. Pre-release checks

Run all three checks locally before touching the changelog. All must be green.

```bash
# From packages/svea/

vendor/bin/pest --compact                            # 0 failures, 0 errors
vendor/bin/pint --test                               # 0 violations
vendor/bin/phpstan analyse src --memory-limit=512M  # No errors
```

The Release workflow repeats these in CI — if they pass locally they will pass there too.

---

## 1. Update the Changelog

Edit `CHANGELOG.md` before triggering the release:

1. Move everything under `## [Unreleased]` into a new versioned section:

```markdown
## [Unreleased]

## [1.1.0] - 2026-05-01

### Added
- ...

### Fixed
- ...
```

2. Update the comparison links at the bottom of the file:

```markdown
[Unreleased]: https://github.com/nordkit/svea/compare/v1.1.0...HEAD
[1.1.0]: https://github.com/nordkit/svea/compare/v1.0.0...v1.1.0
```

3. Commit and push directly to `main`:

```bash
git add CHANGELOG.md
git commit -m "chore: prepare release v1.1.0"
git push origin main
```

---

## 2. Trigger the Release Workflow

1. Go to **GitHub → Actions → Release**
2. Click **Run workflow**
3. Enter the version number — digits only, no `v` prefix (e.g. `1.1.0`)
4. Click **Run workflow**

The workflow will:

| Step | What happens |
|---|---|
| CI | Runs Pest tests (PHP 8.2–8.4) + PHPStan + Pint style check |
| Tag | Creates and pushes `v1.1.0` only if CI passes |
| GitHub Release | Auto-generates release notes from commit messages |

If CI fails the tag is **never created** and the workflow stops.

---

## Versioning

This project follows [Semantic Versioning](https://semver.org):

| Change | Version bump |
|---|---|
| Bug fixes, internal refactors | `PATCH` — `1.0.0 → 1.0.1` |
| New backwards-compatible features | `MINOR` — `1.0.0 → 1.1.0` |
| Breaking API or behaviour changes | `MAJOR` — `1.0.0 → 2.0.0` |

