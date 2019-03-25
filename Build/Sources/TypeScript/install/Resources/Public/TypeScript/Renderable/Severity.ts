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
 * Module: TYPO3/CMS/Install/Module/Severity
 */
class Severity {
  public readonly loading: number = -3;
  public readonly notice: number = -2;
  public readonly info: number = -1;
  public readonly ok: number = 0;
  public readonly warning: number = 1;
  public readonly error: number = 2;

  public getCssClass(severity: number): string {
    let severityClass;
    switch (severity) {
      case this.loading:
        severityClass = 'notice alert-loading';
        break;
      case this.notice:
        severityClass = 'notice';
        break;
      case this.ok:
        severityClass = 'success';
        break;
      case this.warning:
        severityClass = 'warning';
        break;
      case this.error:
        severityClass = 'danger';
        break;
      case this.info:
      default:
        severityClass = 'info';
    }
    return severityClass;
  }
}

export = new Severity();
