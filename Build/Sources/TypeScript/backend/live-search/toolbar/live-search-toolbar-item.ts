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

import RegularEvent from '@typo3/core/event/regular-event';

class LiveSearchToolbarItem {
  constructor() {
    new RegularEvent('click', (): void => {
      document.dispatchEvent(new CustomEvent('typo3:live-search:trigger-open'));
    }).delegateTo(document, '.t3js-topbar-button-search');
  }
}

export default new LiveSearchToolbarItem();
