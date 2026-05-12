import { test as authTest, expect } from '../helpers/fixtures';
import { PLAYWRIGHT_BASE_URL } from '../helpers/config';
import { interpolateRoutePath, loadWebRouteCases } from '../helpers/route-coverage';

const filamentGetRoutes = loadWebRouteCases().filter(
  (routeCase) => routeCase.method === 'GET' && routeCase.path.startsWith('/admin')
);

for (const routeCase of filamentGetRoutes) {
  const routePath = interpolateRoutePath(routeCase.path);

  authTest.describe(`GET ${routeCase.path}`, () => {
    authTest('authenticated: page loads without server error', async ({ page }) => {
      const response = await page.goto(`${PLAYWRIGHT_BASE_URL}${routePath}`);
      expect(response).not.toBeNull();
      expect(response!.status()).toBeLessThan(500);
    });
  });
}
