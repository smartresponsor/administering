import { test, expect } from '@playwright/test';

test('standalone admin dashboard fails closed when host authentication is unavailable', async ({ page }) => {
  const response = await page.goto('/ea/administration');

  expect(response).not.toBeNull();
  expect(response?.status()).toBe(401);
  await expect(page.getByText('Authentication is required.')).toBeVisible();
});
