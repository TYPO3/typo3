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

import { default as Modal, ModalElement } from '@typo3/backend/modal';
import { SeverityEnum } from '@typo3/backend/enum/severity';
import RegularEvent from '@typo3/core/event/regular-event';

class DashboardModal {

  private readonly selector: string = '.js-dashboard-modal';

  constructor() {
    this.initialize();
  }

  public initialize(): void {
    new RegularEvent('click', function (this: HTMLElement, e: Event): void {
      e.preventDefault();

      const modalContent = new DocumentFragment();
      modalContent.append((document.getElementById(`dashboardModal-${this.dataset.modalIdentifier}`) as HTMLTemplateElement).content.cloneNode(true));

      const configuration = {
        type: Modal.types.default,
        title: this.dataset.modalTitle,
        size: Modal.sizes.medium,
        severity: SeverityEnum.notice,
        content: modalContent,
        additionalCssClasses: ['dashboard-modal'],
        callback: (currentModal: ModalElement): void => {
          new RegularEvent('submit', (): void => currentModal.hideModal()).delegateTo(currentModal, '.dashboardModal-form');

          currentModal.addEventListener('button.clicked', (e: Event): void => {
            const button = e.target as HTMLButtonElement;
            if (button.getAttribute('name') === 'save') {
              const formElement = currentModal.querySelector('form') as HTMLFormElement;
              formElement.requestSubmit();
            } else {
              currentModal.hideModal();
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
            btnClass: 'btn-primary',
            name: 'save',
          }
        ]
      };
      Modal.advanced(configuration);
    }).delegateTo(document, this.selector);
  }
}

export default new DashboardModal();
