import { test as guestTest, expect as guestExpect } from '@playwright/test';
import { test as authTest, expect as authExpect } from '../helpers/fixtures';
import { PLAYWRIGHT_BASE_URL } from '../helpers/config';
import { fetchCsrfToken } from '../helpers/csrf';
import { callRouteSmoke } from '../helpers/request-smoke';
import { loadWebRouteCases } from '../helpers/route-cases';
import { interpolateRoutePath } from '../helpers/route-paths';

let cachedTaskRoutes: ReturnType<typeof loadWebRouteCases> | null = null;

function getTaskRoutes() {
  if (cachedTaskRoutes === null) {
    cachedTaskRoutes = loadWebRouteCases().filter((routeCase) => routeCase.path.startsWith('/tasks'));
  }

  return cachedTaskRoutes;
}

guestTest.describe('tasks routes', () => {
  guestTest('guest: does not produce a server error', async ({ page, request }) => {
    for (const routeCase of getTaskRoutes()) {
      const routePath = interpolateRoutePath(routeCase.path);

      if (routeCase.method === 'GET') {
        const response = await page.goto(`${PLAYWRIGHT_BASE_URL}${routePath}`);
        guestExpect(response, `${routeCase.method} ${routeCase.path}`).not.toBeNull();
        guestExpect(response!.status(), `${routeCase.method} ${routeCase.path}`).toBeLessThan(500);
        continue;
      }

      const csrfToken = await fetchCsrfToken(page);
      const response = await callRouteSmoke(request, routeCase.method, routeCase.path, csrfToken);
      guestExpect(response.status(), `${routeCase.method} ${routeCase.path}`).toBeLessThan(500);
    }
  });
});

authTest.describe('tasks routes', () => {
  authTest('authenticated: does not produce a server error', async ({ page, request }) => {
    for (const routeCase of getTaskRoutes()) {
      const routePath = interpolateRoutePath(routeCase.path);

      if (routeCase.method === 'GET') {
        const response = await page.goto(`${PLAYWRIGHT_BASE_URL}${routePath}`);
        authExpect(response, `${routeCase.method} ${routeCase.path}`).not.toBeNull();
        authExpect(response!.status(), `${routeCase.method} ${routeCase.path}`).toBeLessThan(500);
        continue;
      }

      const csrfToken = await fetchCsrfToken(page);
      const response = await callRouteSmoke(request, routeCase.method, routeCase.path, csrfToken);
      authExpect(response.status(), `${routeCase.method} ${routeCase.path}`).toBeLessThan(500);
    }
  });
});
