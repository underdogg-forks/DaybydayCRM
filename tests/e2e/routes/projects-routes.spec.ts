import { test as guestTest, expect as guestExpect } from '@playwright/test';
import { test as authTest, expect as authExpect } from '../helpers/fixtures';
import { PLAYWRIGHT_BASE_URL } from '../helpers/config';
import { fetchCsrfToken } from '../helpers/csrf';
import { callRouteSmoke } from '../helpers/request-smoke';
import { interpolateRoutePath, loadWebRouteCases } from '../helpers/route-coverage';

type WebRouteCase = ReturnType<typeof loadWebRouteCases>[number];

type RouteCoverageCache = typeof globalThis & {
  __daybydaycrmWebRouteCases?: WebRouteCase[];
};

function getCachedWebRouteCases(): WebRouteCase[] {
  const cache = globalThis as RouteCoverageCache;
  cache.__daybydaycrmWebRouteCases ??= loadWebRouteCases();

  return cache.__daybydaycrmWebRouteCases;
}

const projectRoutes = getCachedWebRouteCases().filter((routeCase) => routeCase.path.startsWith('/projects'));

for (const routeCase of projectRoutes) {
  const routePath = interpolateRoutePath(routeCase.path);

  guestTest.describe(`${routeCase.method} ${routeCase.path}`, () => {
    guestTest('guest: does not produce a server error', async ({ page, request }) => {
      if (routeCase.method === 'GET') {
        const response = await page.goto(`${PLAYWRIGHT_BASE_URL}${routePath}`);
        guestExpect(response).not.toBeNull();
        guestExpect(response!.status()).toBeLessThan(500);
        return;
      }

      const csrfToken = await fetchCsrfToken(page);
      const response = await callRouteSmoke(request, routeCase.method, routeCase.path, csrfToken);
      guestExpect(response.status()).toBeLessThan(500);
    });
  });

  authTest.describe(`${routeCase.method} ${routeCase.path}`, () => {
    authTest('authenticated: does not produce a server error', async ({ page, request }) => {
      if (routeCase.method === 'GET') {
        const response = await page.goto(`${PLAYWRIGHT_BASE_URL}${routePath}`);
        authExpect(response).not.toBeNull();
        authExpect(response!.status()).toBeLessThan(500);
        return;
      }

      const csrfToken = await fetchCsrfToken(page);
      const response = await callRouteSmoke(request, routeCase.method, routeCase.path, csrfToken);
      authExpect(response.status()).toBeLessThan(500);
    });
  });
}
