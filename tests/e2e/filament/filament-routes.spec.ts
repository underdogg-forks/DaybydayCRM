import { test as authTest, expect } from '../helpers/fixtures';
import { PLAYWRIGHT_BASE_URL } from '../helpers/config';
import { interpolateRoutePath, loadWebRouteCases } from '../helpers/route-coverage';

const filamentGetRoutes = loadWebRouteCases().filter(
  (routeCase) => routeCase.method === 'GET' && routeCase.path.startsWith('/admin')
);

authTest.describe('Filament page and resource coverage', () => {
  for (const routeCase of filamentGetRoutes) {
    authTest(`filament get route loads: ${routeCase.path}`, async ({ page }) => {
      /* Arrange */
      const routePath = interpolateRoutePath(routeCase.path);

      /* Act */
      const response = await page.goto(`${PLAYWRIGHT_BASE_URL}${routePath}`);

      /* Assert */
      expect(response).not.toBeNull();
      expect(response!.status()).toBeLessThan(500);
    });
  }
});
