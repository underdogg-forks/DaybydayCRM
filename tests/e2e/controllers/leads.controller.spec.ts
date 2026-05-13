import { test as guestTest, expect as guestExpect } from '@playwright/test';
import { test as authTest, expect as authExpect } from '../helpers/fixtures';
import { PLAYWRIGHT_BASE_URL } from '../helpers/config';
import { fetchCsrfToken } from '../helpers/csrf';
import { callRouteSmoke } from '../helpers/request-smoke';

const malformedId = 'invalid-@@@';

authTest.describe('LeadsController', () => {
  for (const endpoint of ['/leads', '/leads/data', '/leads/all-leads-data', `/leads/create/${malformedId}`]) {
    authTest(`endpoint loads: ${endpoint}`, async ({ page }) => {
      /* Arrange */
      /* Act */
      const response = await page.goto(`${PLAYWRIGHT_BASE_URL}${endpoint}`);
      /* Assert */
      authExpect(response).not.toBeNull();
      authExpect(response!.status()).toBeLessThan(500);
    });
  }

  authTest('lead update assign patch handles malformed id', async ({ page, request }) => {
    /* Arrange */
    const csrfToken = await fetchCsrfToken(page);
    /* Act */
    const response = await callRouteSmoke(request, 'PATCH', `/leads/updateassign/${malformedId}`, csrfToken);
    /* Assert */
    authExpect(response.status()).toBeLessThan(500);
  });

  authTest('lead update assign post handles malformed id', async ({ page, request }) => {
    /* Arrange */
    const csrfToken = await fetchCsrfToken(page);
    /* Act */
    const response = await callRouteSmoke(request, 'POST', `/leads/updateassign/${malformedId}`, csrfToken);
    /* Assert */
    authExpect(response.status()).toBeLessThan(500);
  });

  authTest('lead update status patch handles malformed id', async ({ page, request }) => {
    /* Arrange */
    const csrfToken = await fetchCsrfToken(page);
    /* Act */
    const response = await callRouteSmoke(request, 'PATCH', `/leads/updatestatus/${malformedId}`, csrfToken);
    /* Assert */
    authExpect(response.status()).toBeLessThan(500);
  });

  authTest('lead update status post handles malformed id', async ({ page, request }) => {
    /* Arrange */
    const csrfToken = await fetchCsrfToken(page);
    /* Act */
    const response = await callRouteSmoke(request, 'POST', `/leads/updatestatus/${malformedId}`, csrfToken);
    /* Assert */
    authExpect(response.status()).toBeLessThan(500);
  });

  authTest('lead update deadline patch handles malformed id', async ({ page, request }) => {
    /* Arrange */
    const csrfToken = await fetchCsrfToken(page);
    /* Act */
    const response = await callRouteSmoke(request, 'PATCH', `/leads/update-deadline/${malformedId}`, csrfToken);
    /* Assert */
    authExpect(response.status()).toBeLessThan(500);
  });

  authTest('lead update followup patch handles malformed id', async ({ page, request }) => {
    /* Arrange */
    const csrfToken = await fetchCsrfToken(page);
    /* Act */
    const response = await callRouteSmoke(request, 'PATCH', `/leads/updatefollowup/${malformedId}`, csrfToken);
    /* Assert */
    authExpect(response.status()).toBeLessThan(500);
  });
});

guestTest.describe('LeadsController guest restrictions', () => {
  for (const endpoint of ['/leads', '/leads/data', '/leads/all-leads-data', `/leads/create/${malformedId}`]) {
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
        const finalUrl = page.url();
        guestExpect(finalUrl).toContain('login');
      } else {
        guestExpect(isAuthDenial).toBe(true);
      }
    });
  }
});
