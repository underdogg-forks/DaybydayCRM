import { test as guestTest, expect as guestExpect } from '@playwright/test';
import { test as authTest, expect as authExpect } from '../helpers/fixtures';
import { PLAYWRIGHT_BASE_URL } from '../helpers/config';
import { fetchCsrfToken } from '../helpers/csrf';
import { interpolateRoutePath, loadPhpUnitHttpCalls } from '../helpers/route-coverage';
import { callRouteSmoke } from '../helpers/request-smoke';

const allPhpUnitHttpCalls = loadPhpUnitHttpCalls();
const publicPaths = new Set(['/login', '/register', '/password/reset']);
const controllerPrefixes = ['/clients', '/leads', '/projects', '/roles', '/tasks', '/users'];
const jsonPathMatchers = ['/data', '/users/users', '/calendar-users'];

function isGuestCase(method: string, path: string): boolean {
  if (method === 'GET' && publicPaths.has(path)) {
    return true;
  }

  return method === 'POST' && ['/login', '/register', '/password/email'].includes(path);
}

function isLikelyJsonPath(path: string): boolean {
  return jsonPathMatchers.some((matcher) => path.includes(matcher) || path.endsWith(matcher));
}

guestTest.describe('PHPUnit route extraction', () => {
  guestTest('includes routes from all primary controller domains', async () => {
    const coveredPrefixes = new Set(
      allPhpUnitHttpCalls
        .map((routeCase) => controllerPrefixes.find((prefix) => routeCase.path.startsWith(prefix)))
        .filter((prefix): prefix is string => Boolean(prefix))
    );

    guestExpect(allPhpUnitHttpCalls.length).toBeGreaterThan(0);
    guestExpect([...coveredPrefixes].sort()).toEqual([...controllerPrefixes].sort());
  });
});

guestTest.describe('PHPUnit endpoint parity - guest reachable cases', () => {
  for (const routeCase of allPhpUnitHttpCalls) {
    if (!isGuestCase(routeCase.method, routeCase.path)) {
      continue;
    }

    guestTest(`phpunit parity guest smoke: ${routeCase.method} ${routeCase.path}`, async ({ page, request }) => {
      if (routeCase.method === 'GET') {
        const targetUrl = `${PLAYWRIGHT_BASE_URL}${interpolateRoutePath(routeCase.path)}`;
        const response = await page.goto(targetUrl);
        guestExpect(response).not.toBeNull();
        guestExpect(response!.status(), `${routeCase.method} ${routeCase.path}`).toBe(200);
        await guestExpect(page).toHaveURL(new RegExp(routeCase.path.replace('/', '\\/')));

        if (routeCase.path === '/login') {
          await guestExpect(page.getByLabel(/email/i)).toBeVisible();
          await guestExpect(page.getByLabel(/password/i)).toBeVisible();
        }

        if (routeCase.path === '/register') {
          await guestExpect(page.getByRole('button', { name: /register/i })).toBeVisible();
        }
        return;
      }

      const csrfToken = await fetchCsrfToken(page);
      const response = await callRouteSmoke(request, routeCase.method, routeCase.path, csrfToken);
      guestExpect([200, 302, 303, 422], `${routeCase.method} ${routeCase.path}`).toContain(response.status());
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
        const targetUrl = `${PLAYWRIGHT_BASE_URL}${interpolateRoutePath(routeCase.path)}`;

        if (isLikelyJsonPath(routeCase.path)) {
          const response = await request.get(targetUrl, { failOnStatusCode: false });
          authExpect([200, 401, 403, 404, 422], `${routeCase.method} ${routeCase.path}`).toContain(response.status());

          if (response.status() === 200) {
            authExpect(response.headers()['content-type'] ?? '').toContain('application/json');
            const payload = await response.json();
            authExpect(payload, `${routeCase.method} ${routeCase.path} json payload`).not.toBeNull();
          }

          return;
        }

        const response = await page.goto(targetUrl);
        authExpect(response).not.toBeNull();
        authExpect([200, 302, 303, 403, 404], `${routeCase.method} ${routeCase.path}`).toContain(response!.status());
        authExpect(page.url().toLowerCase(), `${routeCase.method} ${routeCase.path}`).not.toContain('/login');
        return;
      }

      const csrfToken = await fetchCsrfToken(page);
      const response = await callRouteSmoke(request, routeCase.method, routeCase.path, csrfToken);
      authExpect([200, 201, 302, 303, 400, 401, 403, 404, 405, 419, 422], `${routeCase.method} ${routeCase.path}`).toContain(response.status());
    });
  }
});
