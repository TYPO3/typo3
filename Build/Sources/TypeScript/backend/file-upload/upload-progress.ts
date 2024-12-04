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

import '@typo3/backend/element/progress-bar-element';
import type { ProgressBarElement } from '@typo3/backend/element/progress-bar-element';
import type { SeverityEnum } from '@typo3/backend/enum/severity';

/**
 * Progress of a single file upload, rendered as a table cell of the upload queue
 */
export class UploadProgress {
  public readonly cell: HTMLTableCellElement;
  private readonly element: ProgressBarElement;

  constructor(colspan: number) {
    this.element = document.createElement('typo3-backend-progress-bar');

    this.cell = document.createElement('td');
    this.cell.classList.add('col-progress');
    this.cell.colSpan = colspan;
    this.cell.append(this.element);
  }

  /**
   * Reports a running upload, a percentage of null keeps the bar indeterminate
   */
  public update(percentage: number | null, label: string): void {
    if (percentage !== null) {
      this.element.value = percentage;
    }
    this.element.label = label;
  }

  /**
   * Completes the bar and marks the outcome of the upload
   */
  public finalize(severity: SeverityEnum, label: string): void {
    this.element.value = 100;
    this.element.severity = severity;
    this.element.label = label;
  }

  public remove(): void {
    this.cell.remove();
  }
}
