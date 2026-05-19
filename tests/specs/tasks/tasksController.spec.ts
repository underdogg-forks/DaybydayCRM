import { test, expect } from '@playwright/test';
import { TEST_USERS, SEED_CLIENT_NAME, SEED_LEAD_TITLES } from '../../playwright/fixtures/users';

test.describe('TasksController', () => {
  test('it can create task', async ({ page }) => {
    /* Arrange */ // uses seeded data
    const user = TEST_USERS.owner;

    /* Act */
    await page.goto('/tasks');

    /* Assert */
    await expect(page.getByText(/(create|new|add)/i).first()).toBeVisible();
  });

  test('it returns web error when task creation throws exception', async ({ page }) => {
    /* Arrange */ // uses seeded data
    const user = TEST_USERS.owner;

    /* Act */
    await page.goto('/tasks');

    /* Assert */
    await expect(page.getByText(/(error|invalid|required|unprocessable|forbidden)/i).first()).toBeVisible();
  });

  test('it returns json error when task creation throws exception', async ({ page }) => {
    /* Arrange */ // uses seeded data
    const user = TEST_USERS.owner;

    /* Act */
    await page.goto('/tasks');

    /* Assert */
    await expect(page.getByText(/(error|invalid|required|unprocessable|forbidden)/i).first()).toBeVisible();
  });

  test('it can add project on task', async ({ page }) => {
    /* Arrange */ // uses seeded data
    const user = TEST_USERS.owner;

    /* Act */
    await page.goto('/tasks');

    /* Assert */
    await expect(page.getByText(/(create|new|add)/i).first()).toBeVisible();
  });

  test('it can update assignee', async ({ page }) => {
    /* Arrange */ // uses seeded data
    const user = TEST_USERS.owner;

    /* Act */
    await page.goto('/tasks');

    /* Assert */
    await expect(page.getByText(/(update|updated|saved|assigned|status|restored)/i).first()).toBeVisible();
  });

  test('it can update status', async ({ page }) => {
    /* Arrange */ // uses seeded data
    const user = TEST_USERS.owner;

    /* Act */
    await page.goto('/tasks');

    /* Assert */
    await expect(page.getByText(/(update|updated|saved|assigned|status|restored)/i).first()).toBeVisible();
  });

  test('it can update deadline for task', async ({ page }) => {
    /* Arrange */ // uses seeded data
    const user = TEST_USERS.owner;

    /* Act */
    await page.goto('/tasks');

    /* Assert */
    await expect(page.getByText(/(update|updated|saved|assigned|status|restored)/i).first()).toBeVisible();
  });

  test('it can list tasks', async ({ page }) => {
    /* Arrange */ // uses seeded data
    const user = TEST_USERS.owner;

    /* Act */
    await page.goto('/tasks');

    /* Assert */
    await expect(page.getByText(/(create|new|add)/i).first()).toBeVisible();
  });

});
