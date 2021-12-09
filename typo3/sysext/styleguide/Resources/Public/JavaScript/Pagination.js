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
 * Javascript functions regarding the Pagination module
 */

define([
  'require',
  'exports',
  'TYPO3/CMS/Core/DocumentService',
  'TYPO3/CMS/Backend/DateTimePicker'
], function (require, exports, DocumentService, DateTimePicker) {
  "use strict";

  class Pagination {
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

  return new Pagination();
});
