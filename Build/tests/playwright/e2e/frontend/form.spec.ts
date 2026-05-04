import { test, expect } from '../../fixtures/setup-fixtures';

const formUrl = '/styleguide-demo-242/form-formframework';

test.describe('Frontend form framework (ext:form)', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto(formUrl);
  });

  test('rejects an invalid form with mandatory field errors', async ({ page }) => {
    const form = page.locator('[id^=simpleform]');
    await form.locator('input[placeholder="Email address"]').fill('invalid mail');
    await form.locator('button[type=submit]:not([formnovalidate])').click();

    const mandatory = 'This field is mandatory.';
    const mandatoryEmail = 'You must enter a valid email address.';
    await expect(form.locator('input[placeholder="Name"] + span')).toContainText(mandatory);
    await expect(form.locator('input[placeholder="Subject"] + span')).toContainText(mandatory);
    await expect(form.locator('input[placeholder="Email address"] + span')).toContainText(mandatoryEmail);
    await expect(form.locator('textarea + span')).toContainText(mandatory);
  });

  test('accepts a valid submission and confirms it on the summary page', async ({ page }) => {
    const name = 'Jane Doe';
    const subject = 'Welcome to TYPO3';
    const email = 'jane.doe@example.org';
    const message = 'Happy to have you!';

    const form = page.locator('[id^=simpleform]');
    await form.locator('input[placeholder="Name"]').fill(name);
    await form.locator('input[placeholder="Subject"]').fill(subject);
    await form.locator('input[placeholder="Email address"]').fill(email);
    await form.locator('textarea').fill(message);
    await form.locator('button[type=submit]:not([formnovalidate])').click();

    const summary = form.locator('.summary-list');
    await expect(summary).toContainText(name);
    await expect(summary).toContainText(subject);
    await expect(summary).toContainText(email);

    await form.locator('button[type=submit]:not([formnovalidate])').click();
    await expect(form).toContainText('E-Mail sent');
  });
});
