import { test as guestTest, expect as guestExpect, type Page } from '@playwright/test';
import { test as authTest, expect as authExpect } from '../helpers/fixtures';
import { PLAYWRIGHT_BASE_URL } from '../helpers/config';
import {
  interpolateRoutePath,
  loadWebRouteCases,
  malformedInterpolatedRoutePath,
  type HttpMethod,
} from '../helpers/route-coverage';

const allWebRoutes = loadWebRouteCases();
const allGetWebRoutes = allWebRoutes.filter((routeCase) => routeCase.method === 'GET');
const guestAccessible = new Set(['/login', '/register', '/password/reset']);
const authRequiredGetRoutes = allGetWebRoutes.filter((routeCase) => !guestAccessible.has(routeCase.path));
const nonGetRoutes = allWebRoutes.filter((routeCase) => routeCase.method !== 'GET');
const dynamicGetRoutes = allGetWebRoutes.filter((routeCase) => routeCase.dynamic);

function isPublicRoute(method: HttpMethod, routePath: string): boolean {
  if (method === 'GET' && guestAccessible.has(routePath)) {
    return true;
  }

  if (method === 'POST' && ['/login', '/register', '/password/email'].includes(routePath)) {
    return true;
  }

  return false;
}

async function fetchCsrfToken(page: Page): Promise<string> {
  await page.goto(`${PLAYWRIGHT_BASE_URL}/login`);
  return (await page.locator('meta[name="csrf-token"]').first().getAttribute('content')) ?? '';
}

guestTest.describe('web.php route coverage - guest', () => {
  for (const routePath of guestAccessible) {
    guestTest(`guest can load ${routePath}`, async ({ page }) => {
      await page.goto(`${PLAYWRIGHT_BASE_URL}${routePath}`);
      await guestExpect(page).toHaveURL(new RegExp(`${routePath.replace('/', '\\/')}`));
    });
  }
});

authTest.describe('web.php route coverage - authenticated GET routes', () => {
  for (const routeCase of authRequiredGetRoutes) {
    authTest(`authenticated route smoke: ${routeCase.path}`, async ({ page }) => {
      const response = await page.goto(`${PLAYWRIGHT_BASE_URL}${interpolateRoutePath(routeCase.path)}`);
      authExpect(response, `Expected response for ${routeCase.path}`).not.toBeNull();
      authExpect(response!.status(), `Unexpected status for ${routeCase.path}`).toBeLessThan(500);
    });
  }
});

authTest.describe('web.php route coverage - non-GET routes', () => {
  for (const routeCase of nonGetRoutes) {
    authTest(`authenticated route smoke: ${routeCase.method} ${routeCase.path}`, async ({ page, request }) => {
      const csrfToken = await fetchCsrfToken(page);
      const response = await request.fetch(`${PLAYWRIGHT_BASE_URL}${interpolateRoutePath(routeCase.path)}`, {
        method: routeCase.method,
        failOnStatusCode: false,
        headers: {
          Accept: 'application/json, text/plain, */*',
          'X-CSRF-TOKEN': csrfToken,
        },
        data: {
          _token: csrfToken,
        },
      });

      authExpect(response.status(), `Unexpected status for ${routeCase.method} ${routeCase.path}`).toBeLessThan(500);
    });
  }
});

guestTest.describe('web.php route edge cases - malformed route params', () => {
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
