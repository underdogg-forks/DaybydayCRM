import { test as guestTest, expect as guestExpect } from '@playwright/test';
import { test as authTest, expect as authExpect } from '../helpers/fixtures';
import { PLAYWRIGHT_BASE_URL } from '../helpers/config';
import { fetchCsrfToken } from '../helpers/csrf';
import { interpolateRoutePath, loadPhpUnitHttpCalls } from '../helpers/route-coverage';
import { callRouteSmoke } from '../helpers/request-smoke';

const allPhpUnitHttpCalls = loadPhpUnitHttpCalls();
const publicPaths = new Set(['/login', '/register', '/password/reset']);

function isGuestCase(method: string, path: string): boolean {
  if (method === 'GET' && publicPaths.has(path)) {
    return true;
  }

  return method === 'POST' && ['/login', '/register', '/password/email'].includes(path);
}

guestTest.describe('PHPUnit endpoint parity - guest reachable cases', () => {
  for (const routeCase of allPhpUnitHttpCalls) {
    if (!isGuestCase(routeCase.method, routeCase.path)) {
      continue;
    }

    guestTest(`phpunit parity guest smoke: ${routeCase.method} ${routeCase.path}`, async ({ page, request }) => {
      if (routeCase.method === 'GET') {
        const response = await page.goto(`${PLAYWRIGHT_BASE_URL}${interpolateRoutePath(routeCase.path)}`);
        guestExpect(response).not.toBeNull();
        guestExpect(response!.status()).toBeLessThan(500);
        return;
      }

      const csrfToken = await fetchCsrfToken(page);
      const response = await callRouteSmoke(request, routeCase.method, routeCase.path, csrfToken);
      guestExpect(response.status()).toBeLessThan(500);
    });
  }
});

authTest.describe('PHPUnit endpoint parity - authenticated smoke', () => {
  for (const routeCase of allPhpUnitHttpCalls) {
    if (isGuestCase(routeCase.method, routeCase.path)) {
      continue;
    }

    authTest(`phpunit parity auth smoke: ${routeCase.method} ${routeCase.path}`, async ({ page, request }) => {
      if (routeCase.method === 'GET') {
        const response = await page.goto(`${PLAYWRIGHT_BASE_URL}${interpolateRoutePath(routeCase.path)}`);
        authExpect(response).not.toBeNull();
        authExpect(response!.status()).toBeLessThan(500);
        return;
      }

      const csrfToken = await fetchCsrfToken(page);
      const response = await callRouteSmoke(request, routeCase.method, routeCase.path, csrfToken);
      authExpect(response.status()).toBeLessThan(500);
    });
  }
});
