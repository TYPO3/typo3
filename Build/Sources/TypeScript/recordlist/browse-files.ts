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

import {MessageUtility} from '@typo3/backend/utility/message-utility';
import ElementBrowser from './element-browser';
import NProgress from 'nprogress';
import RegularEvent from '@typo3/core/event/regular-event';
import Icons = TYPO3.Icons;
import {ActionEventDetails} from '@typo3/backend/multi-record-selection-action';

interface LinkElement {
  fileName: string;
  uid: string;
}

class BrowseFiles {
  public static insertElement(fileName: string, fileUid: number, close?: boolean): boolean {
    return ElementBrowser.insertElement(
      'sys_file',
      String(fileUid),
      fileName,
      String(fileUid),
      close,
    );
  }

  private static handleNext(items: LinkElement[]): void {
    if (items.length > 0) {
      const item = items.pop();
      BrowseFiles.insertElement(item.fileName, Number(item.uid));
    }
  }

  constructor() {
    new RegularEvent('click', (evt: MouseEvent, targetEl: HTMLElement): void => {
      evt.preventDefault();
      BrowseFiles.insertElement(
        targetEl.dataset.fileName,
        Number(targetEl.dataset.fileUid),
        parseInt(targetEl.dataset.close || '0', 10) === 1,
      );
    }).delegateTo(document, '[data-close]');

    // Handle import selection event, dispatched from MultiRecordSelection
    new RegularEvent('multiRecordSelection:action:import', this.importSelection).bindTo(document);
  }

  private importSelection = (e: CustomEvent): void => {
    e.preventDefault();
    const targetEl: HTMLElement = e.target as HTMLElement;
    const items: NodeListOf<HTMLInputElement> = (e.detail as ActionEventDetails).checkboxes;
    if (!items.length) {
      return;
    }

    const selectedItems: Array<LinkElement> = [];
    items.forEach((item: HTMLInputElement) => {
      if (item.checked && item.name && item.dataset.fileName && item.dataset.fileUid) {
        selectedItems.unshift({uid: item.dataset.fileUid, fileName: item.dataset.fileName});
      }
    });

    Icons.getIcon('spinner-circle', Icons.sizes.small, null, null, Icons.markupIdentifiers.inline).then((icon: string): void => {
      targetEl.classList.add('disabled');
      targetEl.innerHTML = icon;
    });
    NProgress.configure({parent: '.element-browser-main-content', showSpinner: false});
    NProgress.start();
    const stepping = 1 / selectedItems.length;
    BrowseFiles.handleNext(selectedItems);

    new RegularEvent('message', (e: MessageEvent): void => {
      if (!MessageUtility.verifyOrigin(e.origin)) {
        throw 'Denied message sent by ' + e.origin;
      }

      if (e.data.actionName === 'typo3:foreignRelation:inserted') {
        if (selectedItems.length > 0) {
          NProgress.inc(stepping);
          BrowseFiles.handleNext(selectedItems);
        } else {
          NProgress.done();
          ElementBrowser.focusOpenerAndClose();
        }
      }
    }).bindTo(window);
  }
}

export default new BrowseFiles();
