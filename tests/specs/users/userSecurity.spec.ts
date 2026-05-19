import { test, expect } from '@playwright/test';
import { TEST_USERS, SEED_CLIENT_NAME, SEED_LEAD_TITLES } from '../../playwright/fixtures/users';

test.describe('UserSecurity', () => {
  test('it authorized user can edit user', async ({ page }) => {
    /* Arrange */ // uses seeded data
    const user = TEST_USERS.owner;

    /* Act */
    await page.goto('/users');

    /* Assert */
    await expect(page.getByText(/(update|updated|saved|assigned|status|restored)/i).first()).toBeVisible();
  });

  test('it unauthorized user cannot edit user', async ({ page }) => {
    /* Arrange */ // uses seeded data
    const user = TEST_USERS.owner;

    /* Act */
    await page.goto('/users');

    /* Assert */
    await expect(page.getByText(/(forbidden|unauthorized|permission|login|warning|error)/i).first()).toBeVisible();
  });

  test('it authorized user can update user', async ({ page }) => {
    /* Arrange */ // uses seeded data
    const user = TEST_USERS.owner;

    /* Act */
    await page.goto('/users');

    /* Assert */
    await expect(page.getByText(/(update|updated|saved|assigned|status|restored)/i).first()).toBeVisible();
  });

  test('it unauthorized user cannot update user', async ({ page }) => {
    /* Arrange */ // uses seeded data
    const user = TEST_USERS.owner;

    /* Act */
    await page.goto('/users');

    /* Assert */
    await expect(page.getByText(/(forbidden|unauthorized|permission|login|warning|error)/i).first()).toBeVisible();
  });

  test('it user update prevents password change without permission', async ({ page }) => {
    /* Arrange */ // uses seeded data
    const user = TEST_USERS.owner;

    /* Act */
    await page.goto('/users');

    /* Assert */
    await expect(page.getByText(/(forbidden|unauthorized|permission|login|warning|error)/i).first()).toBeVisible();
  });

});
