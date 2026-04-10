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

import { SeverityEnum } from './enum/severity';
import { openerTopWindow, topWindow } from '@typo3/core/utility/top-window';

/**
 * Module: @typo3/backend/severity
 * Severity for the TYPO3 backend
 */
export default class Severity {
  public static notice: number = SeverityEnum.notice;
  public static info: number = SeverityEnum.info;
  public static ok: number = SeverityEnum.ok;
  public static warning: number = SeverityEnum.warning;
  public static error: number = SeverityEnum.error;

  /**
   * Gets the CSS class for the severity
   *
   * @param {SeverityEnum} severity
   * @returns {string}
   */
  public static getCssClass(severity: SeverityEnum): string {
    let severityClass: string;

    switch (severity) {
      case SeverityEnum.notice:
        severityClass = 'notice';
        break;
      case SeverityEnum.ok:
        severityClass = 'success';
        break;
      case SeverityEnum.warning:
        severityClass = 'warning';
        break;
      case SeverityEnum.error:
        severityClass = 'danger';
        break;
      case SeverityEnum.info:
      default:
        severityClass = 'info';
    }

    return severityClass;
  }
}

let severityObject: Severity = topWindow.TYPO3?.Severity ?? openerTopWindow()?.TYPO3?.Severity;

if (!severityObject) {
  severityObject = Severity;

  // attach to global frame
  if (typeof TYPO3 !== 'undefined') {
    TYPO3.Severity = severityObject;
  }
}
