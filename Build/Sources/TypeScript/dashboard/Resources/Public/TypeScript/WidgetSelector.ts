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

import $ from 'jquery';
import Modal = require('TYPO3/CMS/Backend/Modal');
import {SeverityEnum} from 'TYPO3/CMS/Backend/Enum/Severity';
import RegularEvent = require('TYPO3/CMS/Core/Event/RegularEvent');

class WidgetSelector {

  private readonly selector: string = '.js-dashboard-addWidget';

  constructor() {
    this.initialize();
  }

  public initialize(): void {
    new RegularEvent('click', function (this: HTMLElement, e: Event): void {
      e.preventDefault();
      const configuration = {
        type: Modal.types.default,
        title: this.dataset.modalTitle,
        size: Modal.sizes.medium,
        severity: SeverityEnum.notice,
        content: $(document.getElementById('widgetSelector').innerHTML),
        additionalCssClasses: ['dashboard-modal'],
        callback: (currentModal: JQuery): void => {
          currentModal.on('click', 'a.dashboard-modal-item-block', (e: JQueryEventObject): void => {
            currentModal.trigger('modal-dismiss');
          });
        },
      };
      Modal.advanced(configuration);
    }).delegateTo(document, this.selector);
  }
}

export = new WidgetSelector();
