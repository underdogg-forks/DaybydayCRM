import { test as authTest, expect as authExpect } from '../helpers/fixtures';
import { PLAYWRIGHT_BASE_URL } from '../helpers/config';
import { loadWebRouteCases } from '../helpers/route-cases';
import { expectedAuthGetStatuses, isLikelyJsonPath } from '../helpers/route-expectations';
import { interpolateRoutePath } from '../helpers/route-paths';

const guestAccessible = new Set(['/login', '/register', '/password/reset']);
const authRequiredGetRoutes = loadWebRouteCases().filter(
  (routeCase) => routeCase.method === 'GET' && !guestAccessible.has(routeCase.path)
);

authTest.describe('web.php authenticated GET routes', () => {
  for (const routeCase of authRequiredGetRoutes) {
    authTest(`authenticated can load ${routeCase.path}`, async ({ page, request }) => {
      const targetUrl = `${PLAYWRIGHT_BASE_URL}${interpolateRoutePath(routeCase.path)}`;

      if (isLikelyJsonPath(routeCase.path)) {
        const response = await request.get(targetUrl, { failOnStatusCode: false });
        authExpect(expectedAuthGetStatuses().includes(response.status()), `${routeCase.method} ${routeCase.path}`).toBe(true);

        if (response.status() === 200) {
          authExpect(response.headers()['content-type'] ?? '').toContain('application/json');
          const payload = await response.json();
          authExpect(payload, `${routeCase.method} ${routeCase.path} json payload`).not.toBeNull();
        }

        return;
      }

      const response = await page.goto(targetUrl);
      authExpect(response, `Expected response for ${routeCase.path}`).not.toBeNull();
      authExpect(response!.status(), `Unexpected status for ${routeCase.path}`).toBeLessThan(500);
      authExpect(new URL(page.url()).pathname, `Expected ${routeCase.path} to stay out of the login screen`).not.toContain('/login');
    });
  }
});
