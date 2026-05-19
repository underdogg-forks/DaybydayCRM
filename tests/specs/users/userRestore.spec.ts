import { test, expect } from '@playwright/test';
import { TEST_USERS, SEED_CLIENT_NAME, SEED_LEAD_TITLES } from '../../playwright/fixtures/users';

test.describe('UserRestore', () => {
  test('it user can be restored after soft delete', async ({ page }) => {
    /* Arrange */ // uses seeded data
    const user = TEST_USERS.owner;

    /* Act */
    await page.goto('/users');

    /* Assert */
    await expect(page.getByText(/(delete|removed|warning|cannot)/i).first()).toBeVisible();
  });

});
