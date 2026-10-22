/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
/**
 * This handler is used as client-side counterpart of `\TYPO3\CMS\Core\Page\JavaScriptRenderer`.
 *
 * @module @typo3/core/java-script-item-handler
 * @internal Use in TYPO3 core only, API can change at any time!
 */
if (document.currentScript) {
  const scriptElement = document.currentScript;
  // extracts JSON payload from `/* [JSON] */` content
  const textContent = scriptElement.textContent.replace(/^\s*\/\*\s*|\s*\*\/\s*/g, '');
  const items = JSON.parse(textContent);

  const processed = import('@typo3/core/java-script-item-processor').then(({ JavaScriptItemProcessor }) => {
    const processor = new JavaScriptItemProcessor();
    return processor.processItems(items);
  });

  // Registered for `whenItemsProcessed()` of `@typo3/core/java-script-item-processor`.
  // The registered promise only settles along with `processed`: a rejection stays
  // unhandled in the chain created by `finally()` and is reported as before.
  const pendingItemsKey = Symbol.for('@typo3/core/java-script-item-processor/pending-items');
  const registry = window as typeof window & { [pendingItemsKey]?: Set<Promise<void>> };
  registry[pendingItemsKey] ??= new Set();
  registry[pendingItemsKey].add(new Promise<void>((resolve) => {
    processed.finally(resolve);
  }));
}
