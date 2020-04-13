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

import {SeverityEnum} from '../Enum/Severity';
import Modal = require('../Modal');
import NewContentElementWizard from 'TYPO3/CMS/Backend/NewContentElementWizard';

/**
 * Module: TYPO3/CMS/Backend/Wizard/NewContentElement
 * NewContentElement JavaScript
 * @exports TYPO3/CMS/Backend/Wizard/NewContentElement
 */
class NewContentElement {
  public static wizard(url: string, title: string): void {
    const $modal = Modal.advanced({
      callback: (currentModal: JQuery) => {
        currentModal.find('.t3js-modal-body').addClass('t3-new-content-element-wizard-window');
      },
      content: url,
      severity: SeverityEnum.notice,
      size: Modal.sizes.medium,
      title,
      type: Modal.types.ajax,
    }).on('modal-loaded', (): void => {
      // This rather works in local environments only
      $modal.on('shown.bs.modal', (): void => {
        const wizard = new NewContentElementWizard($modal);
        wizard.focusSearchField();
      });
    }).on('shown.bs.modal', (): void => {
      // This is the common case with any latency that the modal is rendered before the content is loaded
      $modal.on('modal-loaded', (): void => {
        const wizard = new NewContentElementWizard($modal);
        wizard.focusSearchField();
      });
    });
  }
}

export = NewContentElement;
