import { defineConfig, devices } from '@playwright/test';

const baseURL = process.env.APP_URL ?? 'http://localhost';

export default defineConfig({
  testDir: './tests/specs',
  fullyParallel: false,
  retries: 1,
  use: {
    baseURL,
    trace: 'on-first-retry',
    ...devices['Desktop Chrome'],
  },
  globalSetup: './tests/globalSetup.ts',
  projects: [
    {
      name: 'owner',
      use: {
        storageState: './tests/fixtures/auth/owner.json',
      },
    },
    {
      name: 'admin',
      use: {
        storageState: './tests/fixtures/auth/admin.json',
      },
    },
    {
      name: 'manager',
      use: {
        storageState: './tests/fixtures/auth/manager.json',
      },
    },
    {
      name: 'employee',
      use: {
        storageState: './tests/fixtures/auth/employee.json',
      },
    },
  ],
});
