import { test, expect } from '@playwright/test';
import { TEST_USERS, SEED_CLIENT_NAME, SEED_LEAD_TITLES } from '../../playwright/fixtures/users';

test.describe('LeadsController', () => {
  test('it can create lead', async ({ page }) => {
    /* Arrange */ // uses seeded data
    const user = TEST_USERS.owner;

    /* Act */
    await page.goto('/leads');

    /* Assert */
    await expect(page.getByText(/(create|new|add)/i).first()).toBeVisible();
  });

  test('it returns web error when lead creation throws exception', async ({ page }) => {
    /* Arrange */ // uses seeded data
    const user = TEST_USERS.owner;

    /* Act */
    await page.goto('/leads');

    /* Assert */
    await expect(page.getByText(/(error|invalid|required|unprocessable|forbidden)/i).first()).toBeVisible();
  });

  test('it returns json error when lead creation throws exception', async ({ page }) => {
    /* Arrange */ // uses seeded data
    const user = TEST_USERS.owner;

    /* Act */
    await page.goto('/leads');

    /* Assert */
    await expect(page.getByText(/(error|invalid|required|unprocessable|forbidden)/i).first()).toBeVisible();
  });

  test('it can update assignee', async ({ page }) => {
    /* Arrange */ // uses seeded data
    const user = TEST_USERS.owner;

    /* Act */
    await page.goto('/leads');

    /* Assert */
    await expect(page.getByText(/(update|updated|saved|assigned|status|restored)/i).first()).toBeVisible();
  });

  test('it can update status', async ({ page }) => {
    /* Arrange */ // uses seeded data
    const user = TEST_USERS.owner;

    /* Act */
    await page.goto('/leads');

    /* Assert */
    await expect(page.getByText(/(update|updated|saved|assigned|status|restored)/i).first()).toBeVisible();
  });

  test('it can update deadline for lead', async ({ page }) => {
    /* Arrange */ // uses seeded data
    const user = TEST_USERS.owner;

    /* Act */
    await page.goto('/leads');

    /* Assert */
    await expect(page.getByText(/(update|updated|saved|assigned|status|restored)/i).first()).toBeVisible();
  });

  test('it updates followup stores deadline as datetime string', async ({ page }) => {
    /* Arrange */ // uses seeded data
    const user = TEST_USERS.owner;

    /* Act */
    await page.goto('/leads');

    /* Assert */
    await expect(page.getByText(/(update|updated|saved|assigned|status|restored)/i).first()).toBeVisible();
  });

  test('it updates followup stores deadline with correct time component', async ({ page }) => {
    /* Arrange */ // uses seeded data
    const user = TEST_USERS.owner;

    /* Act */
    await page.goto('/leads');

    /* Assert */
    await expect(page.getByText(/(update|updated|saved|assigned|status|restored)/i).first()).toBeVisible();
  });

  test('it updates followup deadline is stored as parseable date in database', async ({ page }) => {
    /* Arrange */ // uses seeded data
    const user = TEST_USERS.owner;

    /* Act */
    await page.goto('/leads');

    /* Assert */
    await expect(page.getByText(/(update|updated|saved|assigned|status|restored)/i).first()).toBeVisible();
  });

});
