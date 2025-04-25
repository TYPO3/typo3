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

import { DedupeAsyncTask } from '@typo3/core/cache/dedupe-async-task.js';
import { expect } from '@open-wc/testing';
import type { } from 'mocha';

interface Cache<T> {
  promise: Promise<T>,
  abortController: AbortController,
  refCount: number,
}

interface DedupeAsyncTaskPublic<T> {
  promises: Record<string, Cache<T>>;
}

describe('@typo3/core/cache/dedupe-async-task', (): void => {
  it('dedupes', async (): Promise<void> => {
    let counter = 0;
    const task = async (): Promise<number> => {
      return ++counter;
    };
    const dedupe = new DedupeAsyncTask<number>();
    const a = await dedupe.get('foo', task);
    const b = await dedupe.get('foo', task);
    expect(a).to.equal(1);
    expect(b).to.equal(1);
  });

  describe('aborts requests', (): void => {
    const boolTask = async (signal: AbortSignal): Promise<boolean> => {
      await new Promise((resolve) => window.setTimeout(resolve, 0));
      return !signal.aborted;
    };

    it('if all requests are aborted', async (): Promise<void> => {
      let wasAborted: boolean;
      const task = async (signal: AbortSignal): Promise<void> => {
        await new Promise((resolve) => window.setTimeout(resolve, 0));
        wasAborted = signal.aborted;
      };

      const dedupe = new DedupeAsyncTask<void>();

      const abortControllerA = new AbortController();
      const aPromise = dedupe.get('foo', task, abortControllerA.signal);

      const abortControllerB = new AbortController();
      const bPromise = dedupe.get('foo', task, abortControllerB.signal);

      const internalPromise = (dedupe as unknown as DedupeAsyncTaskPublic<void>).promises.foo.promise;
      abortControllerA.abort();
      abortControllerB.abort();

      let aHasError = false;
      try {
        await aPromise;
      } catch {
        aHasError = true;
      }
      let bHasError = false;
      try {
        await bPromise;
      } catch {
        bHasError = true;
      }

      expect(aHasError).to.be.true;
      expect(bHasError).to.be.true;
      await internalPromise;
      expect(wasAborted).to.be.true;
    });

    it('unless only first request is aborted', async (): Promise<void> => {
      const dedupe = new DedupeAsyncTask<boolean>();

      const abortControllerA = new AbortController();
      const aPromise = dedupe.get('foo', boolTask, abortControllerA.signal);

      const abortControllerB = new AbortController();
      const bPromise = dedupe.get('foo', boolTask, abortControllerB.signal);

      abortControllerA.abort();

      let error: Error;
      try {
        await aPromise;
      } catch (e) {
        error = e;
      }
      const b = await bPromise;

      expect(error.name).to.equal('AbortError');
      expect(b).to.be.true;
    });

    it('unless only last request is aborted', async (): Promise<void> => {
      const dedupe = new DedupeAsyncTask<boolean>();

      const abortControllerA = new AbortController();
      const aPromise = dedupe.get('foo', boolTask, abortControllerA.signal);

      let a: boolean | null;
      const aInBackground = aPromise.then((v) => a = v).catch((): void => a = null);

      const abortControllerB = new AbortController();
      const bPromise = dedupe.get('foo', boolTask, abortControllerB.signal);

      abortControllerB.abort('stop b!');
      let b: boolean|null;
      try {
        b = await bPromise;
      } catch {
        b = null;
      }

      await aInBackground;

      expect(a).to.be.true;
      expect(b).to.be.null;
    });


    it('if previous request already succeeded', async (): Promise<void> => {
      const dedupe = new DedupeAsyncTask<boolean>();
      const a = await dedupe.get('foo', boolTask);
      const abortControllerB = new AbortController();
      abortControllerB.abort();
      let error: Error;
      try {
        await dedupe.get('foo', boolTask, abortControllerB.signal);
      } catch (e) {
        error = e;
      }

      expect(a).to.be.true;
      expect(error.name).to.equal('AbortError');
    });

  });
});
