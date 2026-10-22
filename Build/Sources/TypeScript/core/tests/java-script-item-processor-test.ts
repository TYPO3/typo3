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

import { JavaScriptItemProcessor, whenItemsProcessed, type JavaScriptItem } from '@typo3/core/java-script-item-processor.js';
import { expect } from '@open-wc/testing';
import type { } from 'mocha';

const FLAG_USE_IMPORTMAP = 2;

function registerPendingItems(): { promise: Promise<void>, resolve: () => void } {
  let resolve: () => void;
  const promise = new Promise<void>((r) => {
    resolve = r;
  });
  const registry = window as typeof window & { [key: symbol]: Set<Promise<void>> };
  const key = Symbol.for('@typo3/core/java-script-item-processor/pending-items');
  registry[key] ??= new Set();
  registry[key].add(promise);
  return { promise, resolve };
}

function settledState(promise: Promise<void>): { settled: boolean } {
  const state = { settled: false };
  promise.then(() => {
    state.settled = true;
  });
  return state;
}

function nextMacrotask(): Promise<void> {
  return new Promise((resolve) => window.setTimeout(resolve, 0));
}

describe('@typo3/core/java-script-item-processor', (): void => {
  it('resolves processItems once all items are processed', async (): Promise<void> => {
    const items: JavaScriptItem[] = [
      { type: 'globalAssignment', payload: { typo3JavaScriptItemProcessorTest: 'assigned' } as any },
    ];
    await (new JavaScriptItemProcessor()).processItems(items);
    expect((window as any).typo3JavaScriptItemProcessorTest).to.equal('assigned');
  });

  it('rejects processItems after all items settled if an item failed', async (): Promise<void> => {
    const items: JavaScriptItem[] = [
      { type: 'javaScriptModuleInstruction', payload: { name: '@typo3/core/does-not-exist.js', flags: FLAG_USE_IMPORTMAP, items: [] } },
    ];
    let error: unknown = null;
    try {
      await (new JavaScriptItemProcessor()).processItems(items);
    } catch (e) {
      error = e;
    }
    expect(error).to.be.instanceOf(Error);
  });

  it('waits for registered items before resolving whenItemsProcessed', async (): Promise<void> => {
    const first = registerPendingItems();
    const state = settledState(whenItemsProcessed());

    await nextMacrotask();
    expect(state.settled).to.equal(false);

    // Items registered while waiting are awaited as well.
    const second = registerPendingItems();
    first.resolve();
    await nextMacrotask();
    expect(state.settled).to.equal(false);

    second.resolve();
    await nextMacrotask();
    expect(state.settled).to.equal(true);
  });
});
