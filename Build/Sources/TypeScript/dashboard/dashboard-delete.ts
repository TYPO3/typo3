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

import Modal from '@typo3/backend/modal';
import {SeverityEnum} from '@typo3/backend/enum/severity';
import RegularEvent from '@typo3/core/event/regular-event';

class DashboardDelete {

  private readonly selector: string = '.js-dashboard-delete';

  constructor() {
    this.initialize();
  }

  public initialize(): void {
    new RegularEvent('click', function (this: HTMLElement, e: Event): void {
      e.preventDefault();
      const modal = Modal.confirm(
        this.dataset.modalTitle,
        this.dataset.modalQuestion,
        SeverityEnum.warning, [
          {
            text: this.dataset.modalCancel,
            active: true,
            btnClass: 'btn-default',
            name: 'cancel',
          },
          {
            text: this.dataset.modalOk,
            btnClass: 'btn-warning',
            name: 'delete',
          },
        ]
      );

      modal.addEventListener('button.clicked', (e: Event): void => {
        const target = e.target as HTMLButtonElement;
        if (target.getAttribute('name') === 'delete') {
          window.location.href = this.getAttribute('href');
        }
        Modal.dismiss();
      });
    }).delegateTo(document, this.selector);
  }
}

export default new DashboardDelete();
