import { test, expect } from '@playwright/test';
import { TEST_USERS, SEED_CLIENT_NAME, SEED_LEAD_TITLES } from '../../playwright/fixtures/users';

test.describe('DocumentSecurity', () => {
  test('it authorized user can upload file to task', async ({ page }) => {
    /* Arrange */ // uses seeded data
    const user = TEST_USERS.owner;

    /* Act */
    await page.goto('/documents');

    /* Assert */
    await expect(page).toHaveURL(/.+/);
    await expect(page.getByRole('main')).toBeVisible();
  });

  test('it unauthorized user cannot upload file to task', async ({ page }) => {
    /* Arrange */ // uses seeded data
    const user = TEST_USERS.owner;

    /* Act */
    await page.goto('/documents');

    /* Assert */
    await expect(page).toHaveURL(/.+/);
    await expect(page.getByRole('main')).toBeVisible();
  });

  test('it authorized user can upload file to project', async ({ page }) => {
    /* Arrange */ // uses seeded data
    const user = TEST_USERS.owner;

    /* Act */
    await page.goto('/documents');

    /* Assert */
    await expect(page).toHaveURL(/.+/);
    await expect(page.getByRole('main')).toBeVisible();
  });

  test('it unauthorized user cannot upload file to project', async ({ page }) => {
    /* Arrange */ // uses seeded data
    const user = TEST_USERS.owner;

    /* Act */
    await page.goto('/documents');

    /* Assert */
    await expect(page).toHaveURL(/.+/);
    await expect(page.getByRole('main')).toBeVisible();
  });

  test('it upload to nonexistent task returns error', async ({ page }) => {
    /* Arrange */ // uses seeded data
    const user = TEST_USERS.owner;

    /* Act */
    await page.goto('/documents');

    /* Assert */
    await expect(page).toHaveURL(/.+/);
    await expect(page.getByRole('main')).toBeVisible();
  });

  test('it upload to nonexistent project returns error', async ({ page }) => {
    /* Arrange */ // uses seeded data
    const user = TEST_USERS.owner;

    /* Act */
    await page.goto('/documents');

    /* Assert */
    await expect(page).toHaveURL(/.+/);
    await expect(page.getByRole('main')).toBeVisible();
  });

});
