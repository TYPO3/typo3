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

class WidgetRemover {

  private selector: string = '.js-dashboard-remove-widget';

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

      const $modal = Modal.confirm(
        $me.data('modal-title'),
        $me.data('modal-question'),
        SeverityEnum.warning, [
          {
            text: $me.data('modal-cancel'),
            active: true,
            btnClass: 'btn-default',
            name: 'cancel',
          },
          {
            text: $me.data('modal-ok'),
            btnClass: 'btn-warning',
            name: 'delete',
          },
        ]
      );

      $modal.on('button.clicked', (e: JQueryEventObject): void => {
        if (e.target.getAttribute('name') === 'delete') {
          window.location.href = $me.attr('href');
        }
        Modal.dismiss();
      });
    });
  }
}

export = new WidgetRemover();
