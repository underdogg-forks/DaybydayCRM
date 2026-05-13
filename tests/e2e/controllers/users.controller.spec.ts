import { test as guestTest, expect as guestExpect } from '@playwright/test';
import { test as authTest, expect as authExpect } from '../helpers/fixtures';
import { PLAYWRIGHT_BASE_URL } from '../helpers/config';

const endpoints = ['/users', '/users/data', '/users/users', '/users/calendar-users'];

authTest.describe('UsersController', () => {
  for (const endpoint of endpoints) {
    authTest(`endpoint loads: ${endpoint}`, async ({ page }) => {
      /* Arrange */
      /* Act */
      const response = await page.goto(`${PLAYWRIGHT_BASE_URL}${endpoint}`);
      /* Assert */
      authExpect(response).not.toBeNull();
      authExpect(response!.status()).toBeLessThan(500);
    });
  }
});

guestTest.describe('UsersController guest restrictions', () => {
  for (const endpoint of endpoints) {
    guestTest(`guest access is restricted: ${endpoint}`, async ({ page }) => {
      /* Arrange */
      const originalUrl = `${PLAYWRIGHT_BASE_URL}${endpoint}`;
      /* Act */
      const response = await page.goto(originalUrl);
      /* Assert */
      guestExpect(response).not.toBeNull();
      const status = response!.status();
      const isAuthDenial = status === 401 || status === 403;
      const finalPathname = new URL(page.url()).pathname;

      if (!isAuthDenial) {
        guestExpect(finalPathname).toBe('/login');
      } else {
        guestExpect(isAuthDenial).toBe(true);
      }
    });
  }
});
