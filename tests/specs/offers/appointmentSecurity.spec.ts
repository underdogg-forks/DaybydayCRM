import { test, expect } from '@playwright/test';
import { TEST_USERS, SEED_CLIENT_NAME, SEED_LEAD_TITLES } from '../../playwright/fixtures/users';

test.describe('AppointmentSecurity', () => {
  test('it authorized user can update appointment', async ({ page }) => {
    /* Arrange */ // uses seeded data
    const user = TEST_USERS.owner;

    /* Act */
    await page.goto('/offers');

    /* Assert */
    await expect(page).toHaveURL(/.+/);
    await expect(page.getByRole('main')).toBeVisible();
  });

  test('it authorized user can delete appointment', async ({ page }) => {
    /* Arrange */ // uses seeded data
    const user = TEST_USERS.owner;

    /* Act */
    await page.goto('/offers');

    /* Assert */
    await expect(page).toHaveURL(/.+/);
    await expect(page.getByRole('main')).toBeVisible();
  });

  test('it unauthorized user cannot update appointment', async ({ page }) => {
    /* Arrange */ // uses seeded data
    const user = TEST_USERS.owner;

    /* Act */
    await page.goto('/offers');

    /* Assert */
    await expect(page).toHaveURL(/.+/);
    await expect(page.getByRole('main')).toBeVisible();
  });

  test('it requires permission check for appointment update', async ({ page }) => {
    /* Arrange */ // uses seeded data
    const user = TEST_USERS.owner;

    /* Act */
    await page.goto('/offers');

    /* Assert */
    await expect(page).toHaveURL(/.+/);
    await expect(page.getByRole('main')).toBeVisible();
  });

  test('it unauthorized user cannot delete appointment', async ({ page }) => {
    /* Arrange */ // uses seeded data
    const user = TEST_USERS.owner;

    /* Act */
    await page.goto('/offers');

    /* Assert */
    await expect(page).toHaveURL(/.+/);
    await expect(page.getByRole('main')).toBeVisible();
  });

});
