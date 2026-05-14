import { test as authTest, expect as authExpect } from '../helpers/fixtures';
import { loadWebRouteCases } from '../helpers/route-cases';
import { fetchCsrfToken } from '../helpers/csrf';
import { callRouteSmoke } from '../helpers/request-smoke';
import { expectedAuthMutationStatuses } from '../helpers/route-expectations';

const publicMutationPaths = new Set(['/login', '/register', '/password/email']);
const authMutationRoutes = loadWebRouteCases().filter(
  (routeCase) => routeCase.method !== 'GET' && !publicMutationPaths.has(routeCase.path)
);

authTest.describe('web.php authenticated non-GET routes', () => {
  for (const routeCase of authMutationRoutes) {
    authTest(`authenticated route smoke: ${routeCase.method} ${routeCase.path}`, async ({ page, request }) => {
      const csrfToken = await fetchCsrfToken(page);
      const response = await callRouteSmoke(request, routeCase.method, routeCase.path, csrfToken);

      authExpect(expectedAuthMutationStatuses(routeCase.method), `${routeCase.method} ${routeCase.path}`).toContain(
        response.status()
      );
    });
  }
});
