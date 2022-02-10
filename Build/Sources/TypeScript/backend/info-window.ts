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

import {SeverityEnum} from './enum/severity';
import Modal from './modal';

/**
 * Module: @typo3/backend/info-window
 * @exports @typo3/backend/info-window
 */
class InfoWindow {
  /**
   * Shows the info modal
   *
   * @param {string} table
   * @param {string | number} uid
   */
  public static showItem(table: string, uid: string|number): void {
    Modal.advanced({
      type: Modal.types.iframe,
      size: Modal.sizes.large,
      content: top.TYPO3.settings.ShowItem.moduleUrl
        + '&table=' + encodeURIComponent(table)
        + '&uid=' + (typeof uid === 'number' ? uid : encodeURIComponent(uid)),
      severity: SeverityEnum.notice,
    });
  }
}

if (!top.TYPO3.InfoWindow) {
  top.TYPO3.InfoWindow = InfoWindow;
}

// expose as global object
TYPO3.InfoWindow = InfoWindow;
export default InfoWindow;
