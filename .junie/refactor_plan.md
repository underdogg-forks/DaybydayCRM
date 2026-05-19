# Project Refactor Plan

**For complete refactoring opportunities and details, see [.github/refactor.md](../.github/refactor.md)**

## Commit Linting Requirement
- Every commit must be linted before push/PR.
- Run: `git ls-files '*.php' | xargs -n1 php -l`
- CI also enforces this via the `php-lint` workflow.

## Quick Reference

### Documentation
- **[.github/refactor.md](../.github/refactor.md)** — Complete refactoring opportunities (12 major sections)
- **[.github/ROADMAP.md](../.github/ROADMAP.md)** — Current status and milestones
- **[.github/TESTING.md](../.github/TESTING.md)** — Testing standards and patterns
- **[.github/ARCHITECTURE.md](../.github/ARCHITECTURE.md)** — System architecture details

### Priority Goals
1. **Request Validation:** Create missing FormRequests for all controllers
2. **Service Extraction:** Move business logic from large controllers (>200 LOC) to services
3. **Enum Migration:** Convert model constants to type-safe enums
4. **Test Organization:** Move 39 HTTP tests from `tests/Unit/Controllers/` to `tests/Feature/Controllers/`
5. **Response Handling:** Standardize JSON vs Web response handling
6. **Permission Middleware:** Consolidate scattered permission checks

### High-Priority Refactorings (from .github/refactor.md)
1. **#1:** Standardize JSON vs Web Response Handling (~10 files, 8 hours)
2. **#2:** Consolidate Permission Checks in Middleware (~15 files, 12 hours)
3. **#8:** Missing FormRequest Validation (~15 controllers, 8 hours)
4. **#11:** Service Extraction (8 controllers, 40 hours)

### Estimated Total Effort
- **Files Affected:** 140+
- **Lines Changed:** ~2000+
- **Time Estimate:** ~108 hours
- **Risk Reduction:** High (security, maintainability, testability)

## Archive
Historical refactoring documents have been moved to `.github/archive/`:
- `refactoring.md` (merged into refactor.md)
- `refactoring-plan.md` (replaced by this summary)

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

