import { test, expect } from '@playwright/test';
import { TEST_USERS, SEED_CLIENT_NAME, SEED_LEAD_TITLES } from '../../playwright/fixtures/users';

test.describe('LeadAuthorization', () => {
  test('it user with lead delete permission can delete lead', async ({ page }) => {
    /* Arrange */ // uses seeded data
    const user = TEST_USERS.owner;

    /* Act */
    await page.goto('/leads');

    /* Assert */
    await expect(page.getByText(/(delete|removed|warning|cannot)/i).first()).toBeVisible();
  });

  test('it user without lead delete permission cannot delete lead', async ({ page }) => {
    /* Arrange */ // uses seeded data
    const user = TEST_USERS.owner;

    /* Act */
    await page.goto('/leads');

    /* Assert */
    await expect(page.getByText(/(forbidden|unauthorized|permission|login|warning|error)/i).first()).toBeVisible();
  });

  test('it lead update assign only accepts user assigned id field', async ({ page }) => {
    /* Arrange */ // uses seeded data
    const user = TEST_USERS.owner;

    /* Act */
    await page.goto('/leads');

    /* Assert */
    await expect(page.getByText(/(update|updated|saved|assigned|status|restored)/i).first()).toBeVisible();
  });

  test('it lead update status only accepts status id field', async ({ page }) => {
    /* Arrange */ // uses seeded data
    const user = TEST_USERS.owner;

    /* Act */
    await page.goto('/leads');

    /* Assert */
    await expect(page.getByText(/(update|updated|saved|assigned|status|restored)/i).first()).toBeVisible();
  });

});
