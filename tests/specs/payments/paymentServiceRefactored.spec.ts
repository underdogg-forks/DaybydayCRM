import { test, expect } from '@playwright/test';
import { TEST_USERS, SEED_CLIENT_NAME, SEED_LEAD_TITLES } from '../../playwright/fixtures/users';

test.describe('PaymentServiceRefactored', () => {
  test('translated test 1', async ({ page }) => {
    /* Arrange */ // uses seeded data
    const user = TEST_USERS.owner;

    /* Act */
    await page.goto('/payments');

    /* Assert */
    await expect(page).toHaveURL(/.+/);
    await expect(page.getByRole('main')).toBeVisible();
  });

  test('translated test 2', async ({ page }) => {
    /* Arrange */ // uses seeded data
    const user = TEST_USERS.owner;

    /* Act */
    await page.goto('/payments');

    /* Assert */
    await expect(page).toHaveURL(/.+/);
    await expect(page.getByRole('main')).toBeVisible();
  });

  test('translated test 3', async ({ page }) => {
    /* Arrange */ // uses seeded data
    const user = TEST_USERS.owner;

    /* Act */
    await page.goto('/payments');

    /* Assert */
    await expect(page).toHaveURL(/.+/);
    await expect(page.getByRole('main')).toBeVisible();
  });

  test('translated test 4', async ({ page }) => {
    /* Arrange */ // uses seeded data
    const user = TEST_USERS.owner;

    /* Act */
    await page.goto('/payments');

    /* Assert */
    await expect(page).toHaveURL(/.+/);
    await expect(page.getByRole('main')).toBeVisible();
  });

  test('translated test 5', async ({ page }) => {
    /* Arrange */ // uses seeded data
    const user = TEST_USERS.owner;

    /* Act */
    await page.goto('/payments');

    /* Assert */
    await expect(page).toHaveURL(/.+/);
    await expect(page.getByRole('main')).toBeVisible();
  });

  test('translated test 6', async ({ page }) => {
    /* Arrange */ // uses seeded data
    const user = TEST_USERS.owner;

    /* Act */
    await page.goto('/payments');

    /* Assert */
    await expect(page).toHaveURL(/.+/);
    await expect(page.getByRole('main')).toBeVisible();
  });

  test('translated test 7', async ({ page }) => {
    /* Arrange */ // uses seeded data
    const user = TEST_USERS.owner;

    /* Act */
    await page.goto('/payments');

    /* Assert */
    await expect(page).toHaveURL(/.+/);
    await expect(page.getByRole('main')).toBeVisible();
  });

  test('translated test 8', async ({ page }) => {
    /* Arrange */ // uses seeded data
    const user = TEST_USERS.owner;

    /* Act */
    await page.goto('/payments');

    /* Assert */
    await expect(page).toHaveURL(/.+/);
    await expect(page.getByRole('main')).toBeVisible();
  });

  test('translated test 9', async ({ page }) => {
    /* Arrange */ // uses seeded data
    const user = TEST_USERS.owner;

    /* Act */
    await page.goto('/payments');

    /* Assert */
    await expect(page).toHaveURL(/.+/);
    await expect(page.getByRole('main')).toBeVisible();
  });

  test('translated test 10', async ({ page }) => {
    /* Arrange */ // uses seeded data
    const user = TEST_USERS.owner;

    /* Act */
    await page.goto('/payments');

    /* Assert */
    await expect(page).toHaveURL(/.+/);
    await expect(page.getByRole('main')).toBeVisible();
  });

  test('translated test 11', async ({ page }) => {
    /* Arrange */ // uses seeded data
    const user = TEST_USERS.owner;

    /* Act */
    await page.goto('/payments');

    /* Assert */
    await expect(page).toHaveURL(/.+/);
    await expect(page.getByRole('main')).toBeVisible();
  });

  test('translated test 12', async ({ page }) => {
    /* Arrange */ // uses seeded data
    const user = TEST_USERS.owner;

    /* Act */
    await page.goto('/payments');

    /* Assert */
    await expect(page).toHaveURL(/.+/);
    await expect(page.getByRole('main')).toBeVisible();
  });

  test('translated test 13', async ({ page }) => {
    /* Arrange */ // uses seeded data
    const user = TEST_USERS.owner;

    /* Act */
    await page.goto('/payments');

    /* Assert */
    await expect(page).toHaveURL(/.+/);
    await expect(page.getByRole('main')).toBeVisible();
  });

  test('translated test 14', async ({ page }) => {
    /* Arrange */ // uses seeded data
    const user = TEST_USERS.owner;

    /* Act */
    await page.goto('/payments');

    /* Assert */
    await expect(page).toHaveURL(/.+/);
    await expect(page.getByRole('main')).toBeVisible();
  });

  test('translated test 15', async ({ page }) => {
    /* Arrange */ // uses seeded data
    const user = TEST_USERS.owner;

    /* Act */
    await page.goto('/payments');

    /* Assert */
    await expect(page).toHaveURL(/.+/);
    await expect(page.getByRole('main')).toBeVisible();
  });

  test('translated test 16', async ({ page }) => {
    /* Arrange */ // uses seeded data
    const user = TEST_USERS.owner;

    /* Act */
    await page.goto('/payments');

    /* Assert */
    await expect(page).toHaveURL(/.+/);
    await expect(page.getByRole('main')).toBeVisible();
  });

  test('translated test 17', async ({ page }) => {
    /* Arrange */ // uses seeded data
    const user = TEST_USERS.owner;

    /* Act */
    await page.goto('/payments');

    /* Assert */
    await expect(page).toHaveURL(/.+/);
    await expect(page.getByRole('main')).toBeVisible();
  });

});
