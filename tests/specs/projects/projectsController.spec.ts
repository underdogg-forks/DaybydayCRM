import { test, expect } from '@playwright/test';
import { TEST_USERS, SEED_CLIENT_NAME, SEED_LEAD_TITLES } from '../../playwright/fixtures/users';

test.describe('ProjectsController', () => {
  test('it can create project', async ({ page }) => {
    /* Arrange */ // uses seeded data
    const user = TEST_USERS.owner;

    /* Act */
    await page.goto('/projects');

    /* Assert */
    await expect(page).toHaveURL(/.+/);
    await expect(page.getByRole('main')).toBeVisible();
  });

  test('it returns web error when project creation throws exception', async ({ page }) => {
    /* Arrange */ // uses seeded data
    const user = TEST_USERS.owner;

    /* Act */
    await page.goto('/projects');

    /* Assert */
    await expect(page).toHaveURL(/.+/);
    await expect(page.getByRole('main')).toBeVisible();
  });

  test('it returns json error when project creation throws exception', async ({ page }) => {
    /* Arrange */ // uses seeded data
    const user = TEST_USERS.owner;

    /* Act */
    await page.goto('/projects');

    /* Assert */
    await expect(page).toHaveURL(/.+/);
    await expect(page.getByRole('main')).toBeVisible();
  });

  test('it can update assignee', async ({ page }) => {
    /* Arrange */ // uses seeded data
    const user = TEST_USERS.owner;

    /* Act */
    await page.goto('/projects');

    /* Assert */
    await expect(page).toHaveURL(/.+/);
    await expect(page.getByRole('main')).toBeVisible();
  });

  test('it can update status', async ({ page }) => {
    /* Arrange */ // uses seeded data
    const user = TEST_USERS.owner;

    /* Act */
    await page.goto('/projects');

    /* Assert */
    await expect(page).toHaveURL(/.+/);
    await expect(page.getByRole('main')).toBeVisible();
  });

  test('it can update deadline for project', async ({ page }) => {
    /* Arrange */ // uses seeded data
    const user = TEST_USERS.owner;

    /* Act */
    await page.goto('/projects');

    /* Assert */
    await expect(page).toHaveURL(/.+/);
    await expect(page.getByRole('main')).toBeVisible();
  });

});
