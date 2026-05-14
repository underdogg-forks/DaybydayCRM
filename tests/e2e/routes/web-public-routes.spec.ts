import { test as guestTest, expect as guestExpect } from '@playwright/test';
import { PLAYWRIGHT_BASE_URL } from '../helpers/config';
import { loadWebRouteCases } from '../helpers/route-cases';
import { fetchCsrfToken } from '../helpers/csrf';
import { callRouteSmoke } from '../helpers/request-smoke';

const guestAccessible = new Set(['/login', '/register', '/password/reset']);
const guestMutationPaths = new Set(['/login', '/register', '/password/email']);
const guestGetRoutes = loadWebRouteCases().filter((routeCase) => routeCase.method === 'GET' && guestAccessible.has(routeCase.path));
const guestMutationRoutes = loadWebRouteCases().filter(
  (routeCase) => routeCase.method !== 'GET' && guestMutationPaths.has(routeCase.path)
);

guestTest.describe('web.php public GET routes', () => {
  for (const routeCase of guestGetRoutes) {
    guestTest(`guest can load ${routeCase.path}`, async ({ page }) => {
      const response = await page.goto(`${PLAYWRIGHT_BASE_URL}${routeCase.path}`);
      await guestExpect(page).toHaveURL(new RegExp(routeCase.path.replace('/', '\\/')));
      guestExpect(response).not.toBeNull();
      guestExpect(response!.status()).toBe(200);
    });
  }
});

guestTest.describe('web.php public mutation routes', () => {
  for (const routeCase of guestMutationRoutes) {
    guestTest(`guest mutation smoke: ${routeCase.method} ${routeCase.path}`, async ({ page, request }) => {
      const csrfToken = await fetchCsrfToken(page);
      const response = await callRouteSmoke(request, routeCase.method, routeCase.path, csrfToken);

      guestExpect([200, 302, 303, 422], `${routeCase.method} ${routeCase.path}`).toContain(response.status());
    });
  }
});
