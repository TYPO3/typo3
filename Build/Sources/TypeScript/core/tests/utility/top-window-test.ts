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

import { openerTopWindow, topWindow, topDocument } from '@typo3/core/utility/top-window.js';
import { expect } from '@open-wc/testing';
import type { } from 'mocha';

/**
 * topWindow is resolved once, when the module loads into the window the test
 * runs in, which nothing embeds. A test cannot assign `parent` on that window
 * either. openerTopWindow takes its start window from `window.opener`, which
 * is assignable, so the walk both share is covered through the opener.
 */
describe('@typo3/core/utility/top-window', () => {
  let originalOpener: Window | null;

  const someTypo3 = (): Window['TYPO3'] => ({}) as Window['TYPO3'];
  const asWindow = (value: object): Window => value as unknown as Window;

  beforeEach((): void => {
    originalOpener = window.opener;
    window.opener = null;
  });

  afterEach((): void => {
    window.opener = originalOpener;
  });

  describe('topWindow', () => {
    it('is the current window where no frame embeds it', (): void => {
      expect(topWindow).to.equal(window);
    });
  });

  describe('topDocument', () => {
    it('is the document of topWindow', (): void => {
      expect(topDocument).to.equal(topWindow.document);
    });
  });

  describe('openerTopWindow', () => {
    // A frame that nothing embeds is its own parent, which is what ends the walk.
    const hierarchy = (...levels: object[]): Window[] => {
      const windows = levels.map((level) => asWindow({ ...level }));
      windows.forEach((current, index): void => {
        Object.assign(current, { parent: windows[index + 1] ?? current });
      });

      return windows;
    };

    it('returns null without an opener', (): void => {
      expect(openerTopWindow()).to.equal(null);
    });

    it('returns the window carrying TYPO3 in the opener hierarchy', (): void => {
      const [inner, outer] = hierarchy({}, { TYPO3: someTypo3() });
      window.opener = inner;

      expect(openerTopWindow()).to.equal(outer);
    });

    it('returns the outermost window rather than the nearest one', (): void => {
      const [inner, middle, outer] = hierarchy(
        { TYPO3: someTypo3() },
        { TYPO3: someTypo3() },
        { TYPO3: someTypo3() },
      );
      window.opener = inner;

      expect(openerTopWindow()).to.equal(outer);
      expect(openerTopWindow()).to.not.equal(middle);
    });

    it('returns the opener itself where nothing above it carries TYPO3', (): void => {
      const [inner] = hierarchy({ TYPO3: someTypo3() }, {});
      window.opener = inner;

      expect(openerTopWindow()).to.equal(inner);
    });

    it('returns null for an opener hierarchy carrying no TYPO3', (): void => {
      const [inner] = hierarchy({}, {});
      window.opener = inner;

      expect(openerTopWindow()).to.equal(null);
    });

    it('returns null for an opener of a foreign origin', (): void => {
      window.opener = asWindow({
        get TYPO3(): Window['TYPO3'] {
          throw new DOMException('Blocked a frame from accessing a cross-origin frame.', 'SecurityError');
        }
      });

      expect(openerTopWindow()).to.equal(null);
    });

    it('stops at a window of a foreign origin and keeps what it found below', (): void => {
      const [inner, foreign] = hierarchy({ TYPO3: someTypo3() }, {});
      Object.defineProperty(foreign, 'TYPO3', {
        get(): Window['TYPO3'] {
          throw new DOMException('Blocked a frame from accessing a cross-origin frame.', 'SecurityError');
        }
      });
      window.opener = inner;

      expect(openerTopWindow()).to.equal(inner);
    });
  });
});
