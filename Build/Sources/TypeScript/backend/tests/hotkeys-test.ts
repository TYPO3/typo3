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

import { HotkeyDispatchedEvent } from '@typo3/backend/hotkeys/events.js';
import { expect } from '@open-wc/testing';
import type { } from 'mocha';

describe('@typo3/backend/hotkeys', (): void => {
  let originalTypo3: typeof window.TYPO3;
  let hotkeys: typeof import('@typo3/backend/hotkeys.js');

  before(async (): Promise<void> => {
    originalTypo3 = window.TYPO3;
    window.TYPO3 = {} as typeof window.TYPO3;
    hotkeys = await import('@typo3/backend/hotkeys.js');
  });

  after((): void => {
    window.TYPO3 = originalTypo3;
  });

  it('handles a registered hotkey and cancels its dispatch', (): void => {
    let handled = false;
    hotkeys.default.register([hotkeys.ModifierKeys.ALT, 'k'], (): void => {
      handled = true;
    }, { allowOnEditables: true });

    const event = new HotkeyDispatchedEvent(new KeyboardEvent('keydown', { key: 'k', altKey: true }));
    document.dispatchEvent(event);

    expect(handled).to.be.true;
    expect(event.defaultPrevented).to.be.true;
  });

  it('leaves the dispatch of an unregistered hotkey to other documents', (): void => {
    const event = new HotkeyDispatchedEvent(new KeyboardEvent('keydown', { key: 'j', altKey: true }));
    document.dispatchEvent(event);

    expect(event.defaultPrevented).to.be.false;
  });
});
