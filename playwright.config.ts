import { defineConfig } from '@playwright/test';

const defaultBaseUrl = 'http://127.0.0.1:18080';
const externalBaseUrl = process.env.ADMINISTERING_BASE_URL;

export default defineConfig({
  testDir: './tests/Browser',
  fullyParallel: false,
  forbidOnly: true,
  retries: 0,
  reporter: [
    ['list'],
    ['json', { outputFile: 'var/coverage/playwright.json' }],
  ],
  webServer: externalBaseUrl ? undefined : {
    command: 'php -S 127.0.0.1:18080 -t public public/router.php',
    url: defaultBaseUrl + '/administering-health.txt',
    reuseExistingServer: false,
    timeout: 30_000,
  },
  use: {
    baseURL: externalBaseUrl ?? defaultBaseUrl,
    trace: 'retain-on-failure',
  },
});
