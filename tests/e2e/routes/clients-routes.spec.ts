import { test as guestTest, expect as guestExpect } from '@playwright/test';
import { test as authTest, expect as authExpect } from '../helpers/fixtures';
import { PLAYWRIGHT_BASE_URL } from '../helpers/config';
import { fetchCsrfToken } from '../helpers/csrf';
import { callRouteSmoke } from '../helpers/request-smoke';
import { interpolateRoutePath, loadWebRouteCases } from '../helpers/route-coverage';

function getCachedWebRouteCases() {
  const routeCoverageCache = globalThis as typeof globalThis & {
    __daybydaycrmWebRouteCases?: ReturnType<typeof loadWebRouteCases>;
  };

  if (!routeCoverageCache.__daybydaycrmWebRouteCases) {
    routeCoverageCache.__daybydaycrmWebRouteCases = loadWebRouteCases();
  }

  return routeCoverageCache.__daybydaycrmWebRouteCases;
}

const clientRoutes = getCachedWebRouteCases().filter((routeCase) => routeCase.path.startsWith('/clients'));

for (const routeCase of clientRoutes) {
  guestTest(`clients guest behavior: ${routeCase.method} ${routeCase.path}`, async ({ page, request }) => {
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
    guestExpect(response.status()).toBeLessThan(500);});
  });

  authTest(`clients auth behavior: ${routeCase.method} ${routeCase.path}`, async ({ page, request }) => {
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
    authExpect(response.status()).toBeLessThan(500);});
  });
}
