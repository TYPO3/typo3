import * as path from 'path';
import { defineConfig } from '@playwright/test';
import config from './tests/playwright/config';
import * as os from "node:os";

// Limit concurrency locally to a sane level, 4 workers max for now
let worker = Math.round(os.cpus().length / 2);
if(worker > 4) {
  worker = 4;
}

export default defineConfig({
  testDir: './tests/playwright',
  timeout: 30 * 1000,
  expect: {
    timeout: 10000
  },
  fullyParallel: false,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: process.env.CI ? 1 : worker,
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
      name: 'e2e',
      testMatch: 'e2e/**/*.spec.ts',
      dependencies: ['login'],
      use: {
        storageState: path.join(__dirname, '.auth/login.json'),
      },
    },
    {
      name: 'e2e-install',
      testMatch: 'e2e-install/**/*.spec.ts',
      // Installer mutates state irreversibly. A failed run leaves the instance
      // tainted, so an in-test retry can never succeed. CI rebuilds per job and
      // retries there.
      retries: 0,
      // Full installer flow (folders, schema, default config, BE bootstrap) needs
      // more than the 30s default on loaded CI runners.
      timeout: 180 * 1000,
    },
  ],
  outputDir: '../typo3temp/var/tests/playwright-results'
});
