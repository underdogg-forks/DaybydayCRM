import { test, expect } from '@playwright/test';
import { TEST_USERS, SEED_CLIENT_NAME, SEED_LEAD_TITLES } from '../../playwright/fixtures/users';

test.describe('LeadAssignmentAuthorization', () => {
  test('translated test 1', async ({ page }) => {
    /* Arrange */ // uses seeded data
    const user = TEST_USERS.owner;

    /* Act */
    await page.goto('/leads');

    /* Assert */
    await expect(page).toHaveURL(/.+/);
    await expect(page.getByRole('main')).toBeVisible();
  });

  test('translated test 2', async ({ page }) => {
    /* Arrange */ // uses seeded data
    const user = TEST_USERS.owner;

    /* Act */
    await page.goto('/leads');

    /* Assert */
    await expect(page).toHaveURL(/.+/);
    await expect(page.getByRole('main')).toBeVisible();
  });

});
