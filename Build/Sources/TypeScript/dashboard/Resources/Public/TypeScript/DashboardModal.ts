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

class DashboardModal {

  private selector: string = '.js-dashboard-modal';

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
        content: $($('#dashboardModal-' + $me.data('modal-identifier')).html()),
        additionalCssClasses: ['dashboard-modal'],
        callback: (currentModal: any): void => {
          currentModal.on('submit', '.dashboardModal-form', (e: JQueryEventObject): void => {
            currentModal.trigger('modal-dismiss');
          });

          currentModal.on('button.clicked', (e: JQueryEventObject): void => {
            if (e.target.getAttribute('name') === 'save') {
              const formElement = currentModal.find('form');
              $('<input type="submit">').hide().appendTo(formElement).click().remove();
            } else {
              currentModal.trigger('modal-dismiss');
            }
          });
        },
        buttons: [
          {
            text: $me.data('button-close-text'),
            btnClass: 'btn-default',
            name: 'cancel',
          },
          {
            text: $me.data('button-ok-text'),
            active: true,
            btnClass: 'btn-warning',
            name: 'save',
          }
        ]
      };
      Modal.advanced(configuration);
    });
  }
}

export = new DashboardModal();
