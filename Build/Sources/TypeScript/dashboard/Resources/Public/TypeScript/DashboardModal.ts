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

class DashboardModal {

  private readonly selector: string = '.js-dashboard-modal';

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
        content: $(document.getElementById(`dashboardModal-${this.dataset.modalIdentifier}`).innerHTML),
        additionalCssClasses: ['dashboard-modal'],
        callback: (currentModal: any): void => {
          currentModal.on('submit', '.dashboardModal-form', (e: JQueryEventObject): void => {
            currentModal.trigger('modal-dismiss');
          });

          currentModal.on('button.clicked', (e: JQueryEventObject): void => {
            if (e.target.getAttribute('name') === 'save') {
              const formElement = currentModal.find('form');
              formElement.trigger('submit');
            } else {
              currentModal.trigger('modal-dismiss');
            }
          });
        },
        buttons: [
          {
            text: this.dataset.buttonCloseText,
            btnClass: 'btn-default',
            name: 'cancel',
          },
          {
            text: this.dataset.buttonOkText,
            active: true,
            btnClass: 'btn-warning',
            name: 'save',
          }
        ]
      };
      Modal.advanced(configuration);
    }).delegateTo(document, this.selector);
  }
}

export = new DashboardModal();
