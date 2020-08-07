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

/**
 * Module: TYPO3/CMS/Recordlist/FieldSelectBox
 * Check-all / uncheck-all for the Database Recordlist fieldSelectBox
 * @exports TYPO3/CMS/Recordlist/FieldSelectBox
 */
class FieldSelectBox {
  constructor() {
    $(() => {
      $('.fieldSelectBox .checkAll').on('change', (event: JQueryEventObject): void => {
        const checked = $(event.currentTarget).prop('checked');
        const $checkboxes = $('.fieldSelectBox tbody').find(':checkbox');
        $checkboxes.each((index: number, elem: Element): void => {
          if (!$(elem).prop('disabled')) {
            $(elem).prop('checked', checked);
          }
        });
      });
    });
  }
}

export = new FieldSelectBox();
