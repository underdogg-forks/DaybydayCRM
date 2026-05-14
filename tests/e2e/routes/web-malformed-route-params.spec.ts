import { test as guestTest, expect as guestExpect } from '@playwright/test';
import { PLAYWRIGHT_BASE_URL } from '../helpers/config';
import { loadWebRouteCases, type HttpMethod } from '../helpers/route-cases';
import { malformedInterpolatedRoutePath } from '../helpers/route-paths';

const guestAccessible = new Set(['/login', '/register', '/password/reset']);
const dynamicGetRoutes = loadWebRouteCases().filter((routeCase) => routeCase.method === 'GET' && routeCase.dynamic);

function isPublicRoute(method: HttpMethod, routePath: string): boolean {
  if (method === 'GET' && guestAccessible.has(routePath)) {
    return true;
  }

  if (method === 'POST' && ['/login', '/register', '/password/email'].includes(routePath)) {
    return true;
  }

  return false;
}

guestTest.describe('web.php malformed dynamic route params', () => {
  for (const routeCase of dynamicGetRoutes) {
    if (isPublicRoute(routeCase.method, routeCase.path)) {
      continue;
    }

    guestTest(`malformed guest path does not 500: ${routeCase.path}`, async ({ page }) => {
      const response = await page.goto(`${PLAYWRIGHT_BASE_URL}${malformedInterpolatedRoutePath(routeCase.path)}`);
      guestExpect(response, `Expected response for malformed path ${routeCase.path}`).not.toBeNull();
      guestExpect(response!.status(), `Unexpected status for malformed path ${routeCase.path}`).toBeLessThan(500);
    });
  }
});
