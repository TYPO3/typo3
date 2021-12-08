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

import DocumentService = require('TYPO3/CMS/Core/DocumentService');
import DateTimePicker = require('TYPO3/CMS/Backend/DateTimePicker');
import 'TYPO3/CMS/Backend/Input/Clearable';

/**
 * Module: TYPO3/CMS/Belog/BackendLog
 * JavaScript for backend log
 * @exports TYPO3/CMS/Belog/BackendLog
 */
class BackendLog {
  private clearableElements: NodeListOf<HTMLInputElement> = null;
  private dateTimePickerElements: NodeListOf<HTMLInputElement> = null;

  constructor() {
    DocumentService.ready().then((): void => {
      this.clearableElements = document.querySelectorAll('.t3js-clearable');
      this.dateTimePickerElements = document.querySelectorAll('.t3js-datetimepicker');
      this.initializeClearableElements();
      this.initializeDateTimePickerElements();
    });
  }

  private initializeClearableElements(): void {
    this.clearableElements.forEach(
      (clearableField: HTMLInputElement) => clearableField.clearable()
    );
  }

  private initializeDateTimePickerElements(): void {
    this.dateTimePickerElements.forEach(
      (dateTimePickerElement: HTMLInputElement) => DateTimePicker.initialize(dateTimePickerElement)
    );
  }
}

export = new BackendLog();
