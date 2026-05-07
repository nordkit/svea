# Fix Prompt — `nordkit/svea` Pre-Release Issues

Fix all items below in `packages/svea/`. Read each file before editing.

---

**B1 — `phpunit.xml`** ← CRITICAL (CI runs zero tests, exit code 2)  
Replace `<testsuite name="Feature"><directory>tests/Feature</directory></testsuite>`  
with `<testsuite name="Integration"><directory>tests/Integration</directory></testsuite>`

**B2 — Delete `svea-bug-report.md`**  
Delete the file. Add `svea-bug-report.md export-ignore` to `.gitattributes`.

**N1 — `src/SveaResource.php`**  
Remove the `@internal` tag from the `withLastResponse()` docblock (keep the description, drop the tag).

**N2 — `LICENSE.md`**  
Change `Copyright (c) Nordkit` → `Copyright (c) 2024–2026 Nordkit`

**N3 — `.gitignore`**  
Add `*.log` and `.vscode/` entries.

**N4 — `.github/workflows/tests.yml`**  
Add PHP 8.3 to the test matrix alongside 8.2.

**N5 — `CONTRIBUTING.md`**  
In the Development Roadmap, update row 11: change "144 tests" → "188 tests" and mark Integration as ✅.

---

After all edits:
1. `cd packages/svea && vendor/bin/pint --dirty`
2. `vendor/bin/pest --compact` — must exit 0 with 188 tests passing
3. `vendor/bin/pint --test` — must exit 0

