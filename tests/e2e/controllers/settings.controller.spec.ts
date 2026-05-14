import { test, expect, type Page } from '@playwright/test';
import { PLAYWRIGHT_BASE_URL } from '../helpers/config';

async function loginAsSeededAdmin(page: Page) {
  await page.goto(`${PLAYWRIGHT_BASE_URL}/login`);
  await page.getByLabel(/email/i).fill('admin@admin.com');
  await page.getByLabel(/password/i).fill('admin123');
  await page.getByRole('button', { name: /log ?in|sign ?in/i }).click();
}

test.describe('SettingsController', () => {
  test('seeded admin can open settings page', async ({ page }) => {
    /* Arrange */
    await loginAsSeededAdmin(page);

    /* Act */
    const response = await page.goto(`${PLAYWRIGHT_BASE_URL}/settings`);

    /* Assert */
    expect(response).not.toBeNull();
    expect(response!.status()).toBe(200);
    await expect(page).toHaveURL(/\/settings$/);
  });

  test('seeded admin can request settings json payload', async ({ page }) => {
    /* Arrange */
    await loginAsSeededAdmin(page);

    /* Act */
    const result = await page.evaluate(async (settingsUrl) => {
      const response = await fetch(settingsUrl, {
        headers: {
          Accept: 'application/json',
        },
      });

      return {
        status: response.status,
        payload: await response.json(),
      };
    }, `${PLAYWRIGHT_BASE_URL}/settings`);

    /* Assert */
    expect(result.status).toBe(200);
    expect(result.payload).toHaveProperty('settings');
    expect(result.payload).toHaveProperty('business_hours');
    expect(result.payload).toHaveProperty('currencies');
  });
});
