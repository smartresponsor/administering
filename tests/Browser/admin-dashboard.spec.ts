import { test, expect } from '@playwright/test';

test('standalone admin dashboard fails closed when host authentication is unavailable', async ({ page }) => {
  const response = await page.goto('/ea/administration', { waitUntil: 'commit' });

  expect(response).not.toBeNull();
  expect(response?.status()).toBe(401);
  expect(response?.headers()['cache-control']).toContain('no-store');
  expect(response?.headers()['cache-control']).toContain('private');
  await expect(page.getByText('Authentication is required.')).toBeVisible();
});

for (const path of [
  '/ea/administration/section/configuration/tool/example',
  '/ea/administration/tool/example/open',
  '/ea/administration/tool/example/control',
]) {
  test(`standalone admin operation ${path} fails closed`, async ({ page }) => {
    const response = await page.goto(path, { waitUntil: 'commit' });
    expect(response).not.toBeNull();
    expect(response?.status()).toBe(401);
    expect(response?.headers()['cache-control']).toContain('no-store');
    await expect(page.getByText('Authentication is required.')).toBeVisible();
  });
}

for (const path of [
  '/ea/administration/tool/example/open',
  '/ea/administration/tool/example/control',
]) {
  test(`standalone admin mutation ${path} rejects anonymous POST`, async ({ request }) => {
    const response = await request.post(path, { form: { confirmation: 'true' } });
    expect(response.status()).toBe(401);
    expect(response.headers()['cache-control']).toContain('no-store');
    expect(await response.text()).toContain('Authentication is required.');
  });
}

test('standalone admin section detail rejects unauthenticated navigation', async ({ page }) => {
  const response = await page.goto('/ea/administration/section/configuration/tool/example', { waitUntil: 'commit' });
  expect(response?.status()).toBe(401);
  expect(response?.headers()['cache-control']).toContain('no-store');
  await expect(page.getByText('Authentication is required.')).toBeVisible();
});

test('standalone admin tools cannot be opened without host authentication', async ({ page }) => {
  const response = await page.goto('/ea/administration/section/configuration/tool', { waitUntil: 'commit' });

  expect(response).not.toBeNull();
  expect(response?.status()).toBe(401);
  expect(response?.headers()['cache-control']).toContain('no-store');
  await expect(page.getByText('Authentication is required.')).toBeVisible();
});
