import { test, expect } from '@playwright/test';
import { TEST_USERS } from '../../../playwright/fixtures/users';
import { LoginPage } from '../../pages/LoginPage';
import { DepartmentsPage } from '../../pages/DepartmentsPage';

test.describe('DepartmentsController', () => {
  test.beforeEach(async ({ page }) => {
    const loginPage = new LoginPage(page);
    await loginPage.goto();
    await loginPage.login(TEST_USERS.owner.email, TEST_USERS.owner.password);
  });

  test('it can create department', async ({ page }) => {
    /* Arrange */
    const departmentsPage = new DepartmentsPage(page);
    const name = `PW Department Create ${Date.now()}`;
    await departmentsPage.goto();

    /* Act */
    await departmentsPage.create({ name, description: 'Created by Playwright' });

    /* Assert */
    await departmentsPage.assertRowVisible(name);
  });

  test('it can delete department', async ({ page }) => {
    /* Arrange */
    const departmentsPage = new DepartmentsPage(page);
    const name = `PW Department Delete ${Date.now()}`;
    await departmentsPage.goto();
    await departmentsPage.create({ name, description: 'Delete me' });
    await departmentsPage.assertRowVisible(name);

    /* Act */
    await departmentsPage.delete(name);

    /* Assert */
    await departmentsPage.assertRowNotVisible(name);
  });

  test('it cant delete department if user is associated', async ({ page }) => {
    /* Arrange */
    const departmentsPage = new DepartmentsPage(page);
    const name = 'Management';
    await departmentsPage.goto();
    await departmentsPage.assertRowVisible(name);

    /* Act */
    await departmentsPage.delete(name);

    /* Assert */
    await expect(page.getByText(/cannot|associated|warning/i)).toBeVisible();
    await departmentsPage.assertRowVisible(name);
  });
});
