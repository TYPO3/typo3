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

import Modal = require('TYPO3/CMS/Backend/Modal');
import Severity = require('TYPO3/CMS/Backend/Severity');

/**
 * Module: TYPO3/CMS/Backend/Wizard/NewContentElement
 * NewContentElement JavaScript
 * @exports TYPO3/CMS/Backend/Wizard/NewContentElement
 */
class NewContentElement {
  public static wizard(url: string, title: string): void {
    Modal.advanced({
      callback: (currentModal: JQuery) => {
        currentModal.find('.t3js-modal-body').addClass('t3-new-content-element-wizard-window');
      },
      content: url,
      severity: Severity.notice,
      size: Modal.sizes.large,
      title,
      type: Modal.types.ajax,
    });
  }
}

export = NewContentElement;
