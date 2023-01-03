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

/**
 * Javascript functions regarding the filter module
 */

import DocumentService from '@typo3/core/document-service.js';
import DateTimePicker from '@typo3/backend/date-time-picker.js';

class Filter {
  constructor() {
    DocumentService.ready().then(() => {
      this.initializeDateTimePickers();
    });
  }

  initializeDateTimePickers() {
    document.querySelectorAll('.t3js-datetimepicker')?.forEach((element) => {
      DateTimePicker.initialize(element);
    })
  }
}

export default new Filter();
