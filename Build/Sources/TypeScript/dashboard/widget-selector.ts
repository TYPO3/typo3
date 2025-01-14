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

class WidgetSelector {

  private readonly selector: string = '.js-dashboard-addWidget';

  private modal: ModalElement|null = null;

  constructor() {
    this.initialize();
  }

  public initialize(): void {
    new RegularEvent('click', (e: Event, trigger: HTMLElement): void => {
      e.preventDefault();

      const modalContent = new DocumentFragment();
      modalContent.append((document.getElementById('widgetSelector') as HTMLTemplateElement).content.cloneNode(true));

      const configuration = {
        type: Modal.types.default,
        title: trigger.dataset.modalTitle,
        size: Modal.sizes.medium,
        severity: SeverityEnum.notice,
        content: modalContent,
        buttons: [{
          text: TYPO3?.lang?.['button.cancel'] || 'Cancel',
          active: false,
          btnClass: 'btn-default',
          name: 'cancel',
        }],
        additionalCssClasses: ['dashboard-modal'],
        callback: (modal: ModalElement): void => {
          new RegularEvent('click', (): void => modal.hideModal()).delegateTo(modal, 'button.dashboard-modal-item-block');
        },
      };
      const modal = Modal.advanced(configuration);

      modal.addEventListener('button.clicked', (e: Event): void => {
        const button = e.target as HTMLButtonElement;
        if (button.getAttribute('name') === 'cancel') {
          modal.hideModal();
        }
      });
      this.modal = modal;
    }).delegateTo(document, this.selector);

    new RegularEvent('typo3.dashboard.addWidgetDone', (): void => {
      this.modal?.hideModal();
      this.modal = null;
      location.reload();
    }).bindTo(top.document);

    // Display button only if all initialized
    document.querySelectorAll(this.selector).forEach((item) => {
      item.classList.remove('hide');
    });
  }
}

export default new WidgetSelector();
