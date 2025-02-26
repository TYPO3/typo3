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

import DocumentService from '@typo3/core/document-service';
import Notification from '@typo3/backend/notification';
import { ProgressBarElement } from '@typo3/backend/element/progress-bar-element';
import RegularEvent from '@typo3/core/event/regular-event';
import SortableTable from '@typo3/backend/sortable-table';

enum Selectors {
  actionButtonSelectorCheck = '.t3js-linkvalidator-action-button-check',
  actionButtonSelectorReport = '.t3js-linkvalidator-action-button-report',
  reportTable = '.t3js-linkvalidator-report-table',
}

enum Identifier {
  brokenLinksTableIdReport = 'typo3-broken-links-table'
}

/**
 * Module: @typo3/linkvalidator/linkvalidator
 */
class Linkvalidator {
  private progressBar: ProgressBarElement | null = null;

  constructor() {
    DocumentService.ready().then((): void => {
      const linkList = document.getElementById(Identifier.brokenLinksTableIdReport);
      if (linkList !== null) {
        if (linkList instanceof HTMLTableElement) {
          new SortableTable(linkList);
        }
      }
    });

    this.initializeEvents();
  }

  private getProgress(): ProgressBarElement {
    if (!this.progressBar || !this.progressBar.isConnected) {
      this.progressBar = document.createElement('typo3-backend-progress-bar');
      document.querySelector(Selectors.reportTable).prepend(this.progressBar);
    }
    return this.progressBar;
  }

  private initializeEvents(): void {
    new RegularEvent('click', (e: PointerEvent, actionButton: HTMLInputElement): void => {
      Notification.success(actionButton.dataset.notificationMessage || 'Event triggered', '', 2);
    }).delegateTo(document, Selectors.actionButtonSelectorCheck);

    new RegularEvent('click', (): void => {
      this.getProgress().start();
    }).delegateTo(document, Selectors.actionButtonSelectorReport);
  }
}

export default new Linkvalidator();
