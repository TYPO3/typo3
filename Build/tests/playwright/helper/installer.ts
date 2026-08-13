import { expect, type Page } from '@playwright/test';

/**
 * Open the installer and wait for its first step to be rendered.
 *
 * The installer entry point ships an empty body and renders the first step
 * client side, so the headline only exists once its ES module graph has been
 * evaluated. A network reconfiguration in the container environment aborts
 * in-flight requests with `ERR_NETWORK_CHANGED`, and the browser does not retry
 * a module request that failed that way: the module graph never resolves, the
 * body stays empty and every later step of the installation is unreachable.
 *
 * Reload once in that case. The first step is idempotent, the installer keeps
 * its `FIRST_INSTALL` marker until the last step, so a reload simply renders it
 * again.
 */
export async function openInstaller(page: Page): Promise<void> {
  const headline = page.getByText('Installing TYPO3');

  await page.goto('/');
  try {
    await expect(headline).toBeVisible({ timeout: 30000 });
  } catch {
    await page.reload();
    await expect(headline).toBeVisible({ timeout: 30000 });
  }
}
