import { test, expect } from '@playwright/test';
import { LoginPage } from '../pages/LoginPage';
import { TEST_USERS } from '../../playwright/fixtures/users';

test.describe('Auth', () => {
  for (const [role, user] of Object.entries(TEST_USERS)) {
    test(`${role} can log in and sees dashboard`, async ({ page }) => {
      const loginPage = new LoginPage(page);
      await loginPage.goto();
      await loginPage.login(user.email, user.password);
      await expect(page).toHaveURL(/dashboard|home/i);
    });
  }

  test('logged out user is redirected to /login', async ({ browser }) => {
    const context = await browser.newContext();
    const page = await context.newPage();
    await page.goto('/dashboard');
    await expect(page).toHaveURL(/\/login/);
  });

  test('wrong password shows an error', async ({ page }) => {
    const loginPage = new LoginPage(page);
    await loginPage.goto();
    await loginPage.login(TEST_USERS.owner.email, 'wrong-password');
    await loginPage.assertLoginErrorVisible();
  });
});
