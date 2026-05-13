import { test as guestTest, expect as guestExpect } from '@playwright/test';
import { test as authTest, expect as authExpect } from '../helpers/fixtures';
import { PLAYWRIGHT_BASE_URL } from '../helpers/config';
import { fetchCsrfToken } from '../helpers/csrf';
import { callRouteSmoke } from '../helpers/request-smoke';

const malformedId = 'invalid-@@@';

authTest.describe('RolesController', () => {
  for (const endpoint of ['/roles', '/roles/data', '/roles/create']) {
    authTest(`endpoint loads: ${endpoint}`, async ({ page }) => {
      /* Arrange */
      /* Act */
      const response = await page.goto(`${PLAYWRIGHT_BASE_URL}${endpoint}`);
      /* Assert */
      authExpect(response).not.toBeNull();
      authExpect(response!.status()).toBeLessThan(500);
    });
  }

  authTest('role store invalid payload is handled', async ({ page, request }) => {
    /* Arrange */
    const csrfToken = await fetchCsrfToken(page);
    /* Act */
    const response = await callRouteSmoke(request, 'POST', '/roles', csrfToken);
    /* Assert */
    authExpect([200, 302, 400, 401, 403, 422]).toContain(response.status());
  });

  authTest('role update patch handles malformed id', async ({ page, request }) => {
    /* Arrange */
    const csrfToken = await fetchCsrfToken(page);
    /* Act */
    const response = await callRouteSmoke(request, 'PATCH', `/roles/update/${malformedId}`, csrfToken);
    /* Assert */
    authExpect(response.status()).toBeLessThan(500);
  });
});

guestTest.describe('RolesController guest restrictions', () => {
  for (const endpoint of ['/roles', '/roles/data', '/roles/create']) {
    guestTest(`guest access is restricted: ${endpoint}`, async ({ page }) => {
      /* Arrange */
      /* Act */
      const response = await page.goto(`${PLAYWRIGHT_BASE_URL}${endpoint}`);
      /* Assert */
      guestExpect(response).not.toBeNull();
      const status = response!.status();
      const isAuthDenial = status === 401 || status === 403;
      const isRedirect = status === 302 || status === 303;
      if (isRedirect) {
        const location = response!.headers()['location'];
        guestExpect(location).toContain('login');
      } else {
        guestExpect(isAuthDenial).toBe(true);
      }
    });
  }
});
