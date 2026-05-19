# Fundamental Architectural Analysis

Refer to **[.github/ARCHITECTURE.md](../.github/ARCHITECTURE.md)** for the full analysis.

## Commit Linting Requirement
- Every commit must be linted before push/PR.
- Run: `git ls-files '*.php' | xargs -n1 php -l`
- CI also enforces this via the `php-lint` workflow.

## Core Issues
1. **Infrastructure:** Legacy factories and inconsistent UUID generation across models.
2. **Logic Leaks:** Business logic scattered across controllers instead of Services/Actions.
3. **Authorization:** Aging Entrust implementation and missing policy consistency.
4. **Technical Debt:** Vue 2 EOL and legacy Webpack/Mix asset pipeline.
5. **Testing Strategy:** Slow execution due to heavy setup and lack of true isolation.

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

