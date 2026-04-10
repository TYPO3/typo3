import { test, expect } from '../../fixtures/setup-fixtures';

test('the backend and its modules read the user settings the backend window received', async ({ page, backend }) => {
  const loads: string[] = [];
  page.on('request', (request) => {
    if (request.url().includes('/ajax/usersettings/process') && request.url().includes('action=getAll')) {
      loads.push(request.frame().url());
    }
  });

  await backend.gotoModule('system_log');
  await expect(backend.contentFrame.locator('input[data-date-type]').first()).toBeAttached();

  expect(loads).toEqual([]);
});
