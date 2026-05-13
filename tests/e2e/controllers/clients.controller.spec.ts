import { test as guestTest, expect as guestExpect } from '@playwright/test';
import { test as authTest, expect as authExpect } from '../helpers/fixtures';
import { PLAYWRIGHT_BASE_URL } from '../helpers/config';
import { fetchCsrfToken } from '../helpers/csrf';
import { callRouteSmoke } from '../helpers/request-smoke';

const malformedId = 'invalid-@@@';

authTest.describe('ClientsController', () => {
  for (const endpoint of ['/clients', '/clients/data', '/clients/create']) {
    authTest(`page endpoint loads: ${endpoint}`, async ({ page }) => {
      /* Arrange */
      /* Act */
      const response = await page.goto(`${PLAYWRIGHT_BASE_URL}${endpoint}`);
      /* Assert */
      authExpect(response).not.toBeNull();
      authExpect(response!.status()).toBeLessThan(500);
    });
  }

  authTest('store client with invalid payload is handled', async ({ page, request }) => {
    /* Arrange */
    const csrfToken = await fetchCsrfToken(page);
    /* Act */
    const response = await callRouteSmoke(request, 'POST', '/clients', csrfToken);
    /* Assert */
    authExpect([200, 302, 400, 401, 403, 422]).toContain(response.status());
  });

  authTest('cvrapi create endpoint handles invalid payload', async ({ page, request }) => {
    /* Arrange */
    const csrfToken = await fetchCsrfToken(page);
    /* Act */
    const response = await callRouteSmoke(request, 'POST', '/clients/create/cvrapi', csrfToken);
    /* Assert */
    authExpect(response.status()).toBeLessThan(500);
  });

  authTest('update assignee patch handles malformed external id', async ({ page, request }) => {
    /* Arrange */
    const csrfToken = await fetchCsrfToken(page);
    /* Act */
    const response = await callRouteSmoke(request, 'PATCH', `/clients/updateassignee/${malformedId}`, csrfToken);
    /* Assert */
    authExpect(response.status()).toBeLessThan(500);
  });

  authTest('update assign patch handles malformed external id', async ({ page, request }) => {
    /* Arrange */
    const csrfToken = await fetchCsrfToken(page);
    /* Act */
    const response = await callRouteSmoke(request, 'PATCH', `/clients/updateassign/${malformedId}`, csrfToken);
    /* Assert */
    authExpect(response.status()).toBeLessThan(500);
  });

  authTest('update assign post handles malformed external id', async ({ page, request }) => {
    /* Arrange */
    const csrfToken = await fetchCsrfToken(page);
    /* Act */
    const response = await callRouteSmoke(request, 'POST', `/clients/updateassign/${malformedId}`, csrfToken);
    /* Assert */
    authExpect(response.status()).toBeLessThan(500);
  });

  for (const endpoint of [
    `/clients/taskdata/${malformedId}`,
    `/clients/projectdata/${malformedId}`,
    `/clients/leaddata/${malformedId}`,
    `/clients/invoicedata/${malformedId}`,
  ]) {
    authTest(`dynamic endpoint handles malformed external id: ${endpoint}`, async ({ page }) => {
      /* Arrange */
      /* Act */
      const response = await page.goto(`${PLAYWRIGHT_BASE_URL}${endpoint}`);
      /* Assert */
      authExpect(response).not.toBeNull();
      authExpect(response!.status()).toBeLessThan(500);
    });
  }
});

guestTest.describe('ClientsController guest restrictions', () => {
  for (const endpoint of ['/clients', '/clients/create', '/clients/data']) {
    guestTest(`guest access is restricted: ${endpoint}`, async ({ page }) => {
      /* Arrange */
      const originalUrl = `${PLAYWRIGHT_BASE_URL}${endpoint}`;
      /* Act */
      const response = await page.goto(originalUrl);
      /* Assert */
      guestExpect(response).not.toBeNull();
      const status = response!.status();
      const isAuthDenial = status === 401 || status === 403;
      const isRedirect = status === 302 || status === 301;
      if (isRedirect) {
        guestExpect(page.url()).not.toBe(originalUrl);
      } else {
        guestExpect(isAuthDenial).toBe(true);
      }
    });
  }
});
