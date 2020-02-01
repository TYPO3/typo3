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

import * as $ from 'jquery';
import Modal = require('TYPO3/CMS/Backend/Modal');
import {SeverityEnum} from 'TYPO3/CMS/Backend/Enum/Severity';

class WidgetSelector {

  private selector: string = '.js-dashboard-addWidget';

  constructor() {
    $((): void => {
      this.initialize();
    });
  }

  public initialize(): void {
    const me = this;
    $(document).on('click', me.selector, (e: JQueryEventObject): void => {
      e.preventDefault();
      const $me = $(e.currentTarget);

      const configuration = {
        type: Modal.types.default,
        title: $me.data('modal-title'),
        size: Modal.sizes.medium,
        severity: SeverityEnum.notice,
        content: $($('#widgetSelector').html()),
        additionalCssClasses: ['dashboard-modal'],
        callback: (currentModal: any): void => {
          currentModal.on('click', 'a.dashboard-modal-item-block', (e: JQueryEventObject): void => {
            currentModal.trigger('modal-dismiss');
          });
        },
      };
      Modal.advanced(configuration);
    });
  }
}

export = new WidgetSelector();
