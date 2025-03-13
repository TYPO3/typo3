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

import ThrottleEvent from '@typo3/core/event/throttle-event.js';
import { expect } from '@open-wc/testing';
import type { } from 'mocha';

describe('@typo3/core/event/throttle-event', () => {
  it('does not duplicate events', async () => {
    let count = 0;
    const div = document.createElement('div');
    new ThrottleEvent('my-event', () => count++, 1).bindTo(div);
    div.dispatchEvent(new Event('my-event'));
    // await some milliseconds to account for possibly broken implementation that dispatches twice
    await new Promise<void>(resolve => setTimeout(resolve, 5));
    expect(count).to.equal(1);
  });

  it('dispatches most recent event in interval amplitude', async () => {
    let count = 0;
    const seq = [] as number[];
    const div = document.createElement('div');
    new ThrottleEvent('my-event', (e: CustomEvent) => {
      seq.push(e.detail.seq);
      count++;
    }, 10).bindTo(div);
    div.dispatchEvent(new CustomEvent('my-event', { detail: { seq: 1 } }));
    div.dispatchEvent(new CustomEvent('my-event', { detail: { seq: 2 } }));
    div.dispatchEvent(new CustomEvent('my-event', { detail: { seq: 3 } }));
    await new Promise<void>(resolve => setTimeout(resolve, 15));
    div.dispatchEvent(new CustomEvent('my-event', { detail: { seq: 4 } }));
    div.dispatchEvent(new CustomEvent('my-event', { detail: { seq: 5 } }));
    await new Promise<void>(resolve => setTimeout(resolve, 10));
    expect(count).to.equal(3);
    expect(seq).to.eql([1,3,5]);
  });
});
