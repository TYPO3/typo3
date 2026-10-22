import { Page } from '@playwright/test';

/**
 * Returns a waiter for the next module loaded into the content frame.
 * Awaiting the call installs the listener; the returned waiter resolves
 * once the module is ready to be interacted with. Both awaits are needed
 * so the listener is in place before the triggering action runs:
 *
 *   const loaded = await moduleLoaded(page);
 *   await action();
 *   await loaded();
 *
 * The `typo3-module-loaded` event is dispatched with the load event of the
 * content frame. JavaScript modules invoked by the document may still be
 * loading at that point, so their event handlers are not attached yet and
 * an interaction would get lost. The waiter therefore also waits until the
 * JavaScript items of the document have been processed.
 */
export async function moduleLoaded(page: Page): Promise<() => Promise<void>> {
  const initial = await page.evaluate(() => {
    const w = window as Window & { __typo3ModuleLoadedCounter?: number };
    if (typeof w.__typo3ModuleLoadedCounter !== 'number') {
      w.__typo3ModuleLoadedCounter = 0;
      document.addEventListener('typo3-module-loaded', () => {
        w.__typo3ModuleLoadedCounter = (w.__typo3ModuleLoadedCounter ?? 0) + 1;
      });
    }
    return w.__typo3ModuleLoadedCounter;
  });
  return async () => {
    await page.waitForFunction(
      (initial) => {
        const w = window as Window & { __typo3ModuleLoadedCounter?: number };
        return (w.__typo3ModuleLoadedCounter ?? 0) > initial;
      },
      initial,
    );
    await javaScriptItemsProcessed(page);
  };
}

async function javaScriptItemsProcessed(page: Page): Promise<void> {
  const frame = page.frame({ name: 'list_frame' });
  if (frame === null) {
    return;
  }
  // Passed as string, so the dynamic import is resolved through the import
  // map of the frame and not rewritten by the test transpiler. A document
  // that can not resolve the processor can not have JavaScript items either:
  // the item handler loads the processor the very same way.
  await frame.evaluate(`
    import('@typo3/core/java-script-item-processor.js').then(
      (processor) => processor.whenItemsProcessed(),
      () => undefined
    )
  `);
}
