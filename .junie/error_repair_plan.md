# Error Repair Guidelines

Refer to **[.github/TESTING.md](../.github/TESTING.md)** for detailed isolation standards and common fix patterns.

## Commit Linting Requirement
- Every commit must be linted before push/PR.
- Run: `git ls-files '*.php' | xargs -n1 php -l`
- CI also enforces this via the `php-lint` workflow.

## Quick Fix Summary
- **SQLSTATE 1364 (Missing Default):** Ensure model uses `HasExternalId` or update the factory.
- **SQLSTATE 1062 (Duplicate Entry):** Flush cache and reload user (`$user->fresh()`) after role/permission changes.
- **Member function on null:** Ensure related models are correctly setup in test (e.g., `primaryContact`).
- **PHPUnit 10 Compatibility:** Use attributes (`#[Test]`, `#[Group]`) and native PHP property checks.
- **VAT/Tax Calculation Errors:** Check for double division - VAT stored as `percentage × 100`, divide by 10000 not 100.
- **Expected 302 got 200/403:** JSON requests return different status codes (200/403) vs web (302).
- **Status Validation Failures:** Use full class names (`Task::class`) not strings (`'task'`) for `source_type`.
- **Null Trait Methods:** Add null checks before accessing optional properties in traits (e.g., DeadlineTrait).
- **Storage/File Tests:** Storage services need test doubles returning fake content in testing environment.

## Junie's Workflow
1. Add `#[Group('junie_repaired')]` attribute.
2. Fix the error/failure following the isolation rules.
3. Verify the fix in isolation.
4. Document the fix in the session summary.

---

## Playwright Translation Protocol (Laravel PHPUnit → Playwright TS)

When translating `tests/Feature/` PHPUnit files into Playwright:

1. **Inventory First (no code before this):** recursively scan `tests/Feature/` and produce a table of every PHP file containing at least one `#[Test]` method with its count.
2. **1:1 File Mapping:** map each `tests/Feature/{Namespace}/{Name}Test.php` to `tests/specs/{namespace-lower}/{name}.spec.ts` (drop `Test`).
3. **Database Rules:** assume DB is seeded once by `globalSetup.ts`; never reset/re-seed DB inside tests; never run migrations in test bodies.
4. **UI-only execution:** never call APIs directly; always drive behavior through Page Objects.
5. **Seeder constants:** always import test users and seed constants from `playwright/fixtures/users.ts`; never hard-code numeric IDs or total counts.
6. **Cardinality lock:** for each source file, state cardinality (`X #[Test]` → `X test()`), write exactly `X` tests, then re-confirm final count.
7. **AAA required:** each Playwright test must contain exactly and in order:
   - `/* Arrange */`
   - `/* Act */`
   - `/* Assert */`
8. **Selector priority:** use `getByRole` first, `getByLabel` second, scoped `getByText` third; never CSS class selectors; never `nth()` for seeded records.
9. **Waiting strategy:** never `page.waitForTimeout()`; use deterministic waits like `waitForResponse()`/`waitForURL()`.
10. **Data mutation safety:** never modify/delete seeded records; only delete records created in the same test.
11. **Process order:**
   1) print inventory table,
   2) translate files in inventory order,
   3) do not skip files,
   4) do not merge multiple PHP files into one Playwright file.

