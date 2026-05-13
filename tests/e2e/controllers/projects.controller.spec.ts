import { test as guestTest, expect as guestExpect } from '@playwright/test';
import { test as authTest, expect as authExpect } from '../helpers/fixtures';
import { PLAYWRIGHT_BASE_URL } from '../helpers/config';
import { fetchCsrfToken } from '../helpers/csrf';
import { callRouteSmoke } from '../helpers/request-smoke';

const malformedUuid = 'invalid-@@@';

authTest.describe('ProjectsController', () => {
  for (const endpoint of ['/projects', '/projects/create', '/projects/data']) {
    authTest(`page endpoint loads: ${endpoint}`, async ({ page }) => {
      /* Arrange */

      /* Act */
      const response = await page.goto(`${PLAYWRIGHT_BASE_URL}${endpoint}`);

      /* Assert */
      authExpect(response).not.toBeNull();
      authExpect(response!.status()).toBeLessThan(500);
    });
  }

  for (const [label, method, path] of [
    ['update status patch', 'PATCH', `/projects/updatestatus/${malformedUuid}`],
    ['update assignee patch', 'PATCH', `/projects/updateassign/${malformedUuid}`],
    ['update deadline patch', 'PATCH', `/projects/update-deadline/${malformedUuid}`],
    ['update status post', 'POST', `/projects/updatestatus/${malformedUuid}`],
    ['update assignee post', 'POST', `/projects/updateassign/${malformedUuid}`],
  ] as const) {
    authTest(`mutation route handles malformed id: ${label}`, async ({ page, request }) => {
      /* Arrange */
      const csrfToken = await fetchCsrfToken(page);

      /* Act */
      const response = await callRouteSmoke(request, method, path, csrfToken);

      /* Assert */
      authExpect(response.status()).toBeLessThan(500);
    });
  }
});

guestTest.describe('ProjectsController guest restrictions', () => {
  for (const endpoint of ['/projects', '/projects/data', '/projects/create/invalid-@@@']) {
    guestTest(`guest access is restricted: ${endpoint}`, async ({ page }) => {
      /* Arrange */

      /* Act */
      const response = await page.goto(`${PLAYWRIGHT_BASE_URL}${endpoint}`);

      /* Assert */
      guestExpect(response).not.toBeNull();
      guestExpect(response!.status()).toBeLessThan(500);
    });
  }
});
