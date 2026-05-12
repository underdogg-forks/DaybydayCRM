import { test as guestTest, expect as guestExpect } from '@playwright/test';
import { test as authTest, expect as authExpect } from '../helpers/fixtures';
import { PLAYWRIGHT_BASE_URL } from '../helpers/config';
import fs from 'node:fs';
import path from 'node:path';

function extractStaticGetRoutesFromWebPhp(): string[] {
  const webPhp = fs.readFileSync(path.join(process.cwd(), 'routes/web.php'), 'utf8');
  const matches = webPhp.matchAll(/Route::get\(\s*'([^']+)'/g);

  const routes = new Set<string>();
  for (const match of matches) {
    const rawPath = match[1];
    if (!rawPath || rawPath.includes('{')) {
      continue;
    }

    const normalized = rawPath.startsWith('/') ? rawPath : `/${rawPath}`;
    routes.add(normalized);
  }

  routes.add('/login');
  routes.add('/register');
  routes.add('/password/reset');

  return [...routes].sort();
}

const allStaticGetRoutes = extractStaticGetRoutesFromWebPhp();
const guestAccessible = ['/login', '/register', '/password/reset'];
const authRequiredRoutes = allStaticGetRoutes.filter((routePath) => !guestAccessible.includes(routePath));

guestTest.describe('web.php route coverage - guest', () => {
  for (const routePath of guestAccessible) {
    guestTest(`guest can load ${routePath}`, async ({ page }) => {
      await page.goto(`${PLAYWRIGHT_BASE_URL}${routePath}`);
      await guestExpect(page).toHaveURL(new RegExp(`${routePath.replace('/', '\\/')}`));
    });
  }
});

authTest.describe('web.php route coverage - authenticated', () => {
  for (const routePath of authRequiredRoutes) {
    authTest(`authenticated route smoke: ${routePath}`, async ({ page }) => {
      const response = await page.goto(`${PLAYWRIGHT_BASE_URL}${routePath}`);
      authExpect(response, `Expected response for ${routePath}`).not.toBeNull();
      authExpect(response!.status(), `Unexpected status for ${routePath}`).toBeLessThan(500);
    });
  }
});
