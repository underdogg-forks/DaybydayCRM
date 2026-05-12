import { test as guestTest, expect as guestExpect } from '@playwright/test';
import { test as authTest, expect as authExpect } from '../helpers/fixtures';
import { PLAYWRIGHT_BASE_URL } from '../helpers/config';
import { fetchCsrfToken } from '../helpers/csrf';
import { callRouteSmoke } from '../helpers/request-smoke';
import { interpolateRoutePath, loadWebRouteCases } from '../helpers/route-coverage';

const taskRoutes = loadWebRouteCases().filter((routeCase) => routeCase.path.startsWith('/tasks'));

for (const routeCase of taskRoutes) {
  guestTest(`tasks guest behavior: ${routeCase.method} ${routeCase.path}`, async ({ page, request }) => {
    /* Arrange */
    const routePath = interpolateRoutePath(routeCase.path);

    /* Act */
    if (routeCase.method === 'GET') {
      const response = await page.goto(`${PLAYWRIGHT_BASE_URL}${routePath}`);

      /* Assert */
      guestExpect(response).not.toBeNull();
      guestExpect(response!.status()).toBeLessThan(500);
      return;
    }

    const csrfToken = await fetchCsrfToken(page);
    const response = await callRouteSmoke(request, routeCase.method, routeCase.path, csrfToken);

    /* Assert */
    guestExpect(response.status()).toBeLessThan(500);
  });

  authTest(`tasks auth behavior: ${routeCase.method} ${routeCase.path}`, async ({ page, request }) => {
    /* Arrange */
    const routePath = interpolateRoutePath(routeCase.path);

    /* Act */
    if (routeCase.method === 'GET') {
      const response = await page.goto(`${PLAYWRIGHT_BASE_URL}${routePath}`);

      /* Assert */
      authExpect(response).not.toBeNull();
      authExpect(response!.status()).toBeLessThan(500);
      return;
    }

    const csrfToken = await fetchCsrfToken(page);
    const response = await callRouteSmoke(request, routeCase.method, routeCase.path, csrfToken);

    /* Assert */
    authExpect(response.status()).toBeLessThan(500);
  });
}
