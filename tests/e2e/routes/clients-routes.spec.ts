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

function expectedAuthMutationStatuses(method: string): number[] {
  switch (method) {
    case 'POST':
      return [200, 201, 302, 303, 400, 401, 403, 404, 419, 422];
    case 'PUT':
    case 'PATCH':
      return [200, 302, 303, 400, 401, 403, 404, 405, 419, 422];
    case 'DELETE':
      return [200, 202, 204, 302, 303, 400, 401, 403, 404, 405, 419];
    default:
      return [200, 302, 303, 400, 401, 403, 404, 405, 419, 422];
  }
}

for (const routeCase of clientRoutes) {
  guestTest(`clients guest behavior: ${routeCase.method} ${routeCase.path}`, async ({ page, request }) => {
    /* Arrange */
    const routePath = interpolateRoutePath(routeCase.path);
    const routeUrl = `${PLAYWRIGHT_BASE_URL}${routePath}`;

    /* Act */
    if (routeCase.method === 'GET') {
      const response = await request.get(routeUrl, { failOnStatusCode: false, maxRedirects: 0 });
      const status = response.status();

      /* Assert */
      guestExpect([302, 303, 401, 403]).toContain(status);
      if (status === 302 || status === 303) {
        guestExpect(response.headers()['location'] ?? '').toContain('/login');
      }
      return;
    }

    const csrfToken = await fetchCsrfToken(page);
    const response = await callRouteSmoke(request, routeCase.method, routeCase.path, csrfToken);

    /* Assert */
    guestExpect([302, 303, 401, 403]).toContain(response.status());
  });

  authTest(`clients auth behavior: ${routeCase.method} ${routeCase.path}`, async ({ page, request }) => {
    /* Arrange */
    const routePath = interpolateRoutePath(routeCase.path);
    const routeUrl = `${PLAYWRIGHT_BASE_URL}${routePath}`;
    const expectsJson = routeCase.path.includes('/data');

    /* Act */
    if (routeCase.method === 'GET' && expectsJson) {
      const response = await request.get(routeUrl, { failOnStatusCode: false });

      /* Assert */
      authExpect([200, 401, 403, 404]).toContain(response.status());
      if (response.status() === 200) {
        authExpect(response.headers()['content-type'] ?? '').toContain('application/json');
      }
      return;
    }

    if (routeCase.method === 'GET') {
      const response = await page.goto(routeUrl);
      authExpect(response).not.toBeNull();
      authExpect([200, 302, 303, 403, 404]).toContain(response!.status());
      authExpect(page.url().toLowerCase()).not.toContain('/login');
      return;
    }

    const csrfToken = await fetchCsrfToken(page);
    const response = await callRouteSmoke(request, routeCase.method, routeCase.path, csrfToken);

    /* Assert */
    authExpect(expectedAuthMutationStatuses(routeCase.method)).toContain(response.status());
  });
}
