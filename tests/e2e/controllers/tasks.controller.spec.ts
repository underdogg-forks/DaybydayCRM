import { test as guestTest, expect as guestExpect } from '@playwright/test';
import { test as authTest, expect as authExpect } from '../helpers/fixtures';
import { PLAYWRIGHT_BASE_URL } from '../helpers/config';
import { fetchCsrfToken } from '../helpers/csrf';
import { callRouteSmoke } from '../helpers/request-smoke';

const malformedUuid = 'invalid-@@@';

authTest.describe('TasksController', () => {
  authTest('tasks index loads', async ({ page }) => {
    /* Arrange */
    const endpoint = '/tasks';
    /* Act */
    const response = await page.goto(`${PLAYWRIGHT_BASE_URL}${endpoint}`);
    /* Assert */
    authExpect(response).not.toBeNull();
    authExpect(response!.status()).toBeLessThan(500);
  });

  authTest('tasks data endpoint loads', async ({ page }) => {
    /* Arrange */
    const endpoint = '/tasks/data';
    /* Act */
    const response = await page.goto(`${PLAYWRIGHT_BASE_URL}${endpoint}`);
    /* Assert */
    authExpect(response).not.toBeNull();
    authExpect(response!.status()).toBeLessThan(500);
  });

  authTest('tasks create by client route loads', async ({ page }) => {
    /* Arrange */
    const endpoint = '/tasks/create/00000000-0000-0000-0000-000000000001';
    /* Act */
    const response = await page.goto(`${PLAYWRIGHT_BASE_URL}${endpoint}`);
    /* Assert */
    authExpect(response).not.toBeNull();
    authExpect(response!.status()).toBeLessThan(500);
  });

  for (const [label, method, path] of [
    ['update status patch', 'PATCH', `/tasks/updatestatus/${malformedUuid}`],
    ['update assignee patch', 'PATCH', `/tasks/updateassign/${malformedUuid}`],
    ['update deadline patch', 'PATCH', `/tasks/update-deadline/${malformedUuid}`],
    ['update project post', 'POST', `/tasks/updateproject/${malformedUuid}`],
    ['update project patch', 'PATCH', `/tasks/updateproject/${malformedUuid}`],
    ['invoice post', 'POST', `/tasks/invoice/${malformedUuid}`],
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

guestTest.describe('TasksController guest restrictions', () => {
  for (const endpoint of ['/tasks', '/tasks/data', `/tasks/create/${malformedUuid}`]) {
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
