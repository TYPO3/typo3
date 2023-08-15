import * as path from 'path';
import { defineConfig } from '@playwright/test';
import config from './tests/playwright/config';

export default defineConfig({
  testDir: './tests/playwright',
  timeout: 30 * 1000,
  expect: {
    timeout: 5000
  },
  fullyParallel: false,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: process.env.CI ? 1 : undefined,
  reporter: [
    [
      'list'
    ],
    [
      'html',
      {
        outputFolder: '../typo3temp/var/tests/playwright-reports'
      }
    ]
  ],
  use: {
    ignoreHTTPSErrors: true,
    baseURL: config.baseUrl,
    trace: 'on-first-retry',
  },
  projects: [
    {
      name: 'login',
      testMatch: 'helper/login.setup.ts',
    },
    {
      name: 'accessibility',
      testMatch: 'accessibility/**/*.spec.ts',
      dependencies: ['login'],
      use: {
        storageState: path.join(__dirname, '.auth/login.json'),
      },
    },
  ],
  outputDir: '../typo3temp/var/tests/playwright-results'
});
