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

import {MessageUtility} from 'TYPO3/CMS/Backend/Utility/MessageUtility';
import ElementBrowser = require('./ElementBrowser');
import NProgress = require('nprogress');
import RegularEvent = require('TYPO3/CMS/Core/Event/RegularEvent');
import Icons = TYPO3.Icons;

enum Selectors {
  bulkItemSelector = '.typo3-list-check',
  importSelectionSelector = 'button[data-action="import"]',
  selectionToggleSelector = '.typo3-selection-toggle',
  listContainer = '[data-list-container="files"]'
}

interface LinkElement {
  fileName: string;
  uid: string;
}

class BrowseFiles {
  public static Selector: Selector;

  public static insertElement(fileName: string, fileUid: number, close?: boolean): boolean {
    return ElementBrowser.insertElement(
      'sys_file',
      String(fileUid),
      fileName,
      String(fileUid),
      close,
    );
  }

  constructor() {
    BrowseFiles.Selector = new Selector();

    new RegularEvent('click', (evt: MouseEvent, targetEl: HTMLElement): void => {
      evt.preventDefault();
      BrowseFiles.insertElement(
        targetEl.dataset.fileName,
        Number(targetEl.dataset.fileUid),
        parseInt(targetEl.dataset.close || '0', 10) === 1,
      );
    }).delegateTo(document, '[data-close]');

    new RegularEvent('change', BrowseFiles.Selector.toggleImportButton).delegateTo(document, Selectors.bulkItemSelector);
    new RegularEvent('click', BrowseFiles.Selector.handle).delegateTo(document, Selectors.importSelectionSelector);
    new RegularEvent('click', BrowseFiles.Selector.toggle).delegateTo(document, Selectors.selectionToggleSelector);
    new RegularEvent('change', BrowseFiles.Selector.toggle).delegateTo(document, Selectors.bulkItemSelector);
  }

}

class Selector {
  /**
   * Either a toggle button (all/none/toggle) button was pressed, or a checkbox was switched
   */
  public toggle = (e: MouseEvent): void => {
    e.preventDefault();
    const element = e.target as HTMLInputElement;
    const action = element.dataset.action;
    const items = this.getItems();

    switch (action) {
      case 'select-toggle':
        items.forEach((item: HTMLInputElement) => {
          item.checked = !item.checked;
          item.closest('tr').classList.toggle('success');
        });
        break;
      case 'select-all':
        items.forEach((item: HTMLInputElement) => {
          item.checked = true;
          item.closest('tr').classList.add('success');
        });
        break;
      case 'select-none':
        items.forEach((item: HTMLInputElement) => {
          item.checked = false;
          item.closest('tr').classList.remove('success');
        });
        break;
      default:
        // the button itself was checked
        if (element.classList.contains('typo3-list-check')) {
          element.closest('tr').classList.toggle('success');
        }
    }
    this.toggleImportButton();
  }

  /**
   * Import selection button is pressed
   */
  public handle = (e: MouseEvent, targetEl: HTMLElement): void => {
    e.preventDefault();
    const items = this.getItems();
    const selectedItems: Array<LinkElement> = [];
    if (items.length) {
      items.forEach((item: HTMLInputElement) => {
        if (item.checked && item.name && item.dataset.fileName && item.dataset.fileUid) {
          selectedItems.unshift({uid: item.dataset.fileUid, fileName: item.dataset.fileName});
        }
      });
      Icons.getIcon('spinner-circle', Icons.sizes.small, null, null, Icons.markupIdentifiers.inline).then((icon: string): void => {
        targetEl.classList.add('disabled');
        targetEl.innerHTML = icon;
      });
      this.handleSelection(selectedItems);
    }
  }

  public getItems(): NodeList {
    return document.querySelector(Selectors.listContainer).querySelectorAll(Selectors.bulkItemSelector);
  }

  public toggleImportButton(): void {
    const hasCheckedElements = document.querySelector(Selectors.listContainer)?.querySelectorAll(Selectors.bulkItemSelector + ':checked').length > 0;
    document.querySelector(Selectors.importSelectionSelector).classList.toggle('disabled', !hasCheckedElements);
  }

  private handleSelection(items: LinkElement[]): void {
    NProgress.configure({parent: '.element-browser-main-content', showSpinner: false});
    NProgress.start();
    const stepping = 1 / items.length;
    this.handleNext(items);

    new RegularEvent('message', (e: MessageEvent): void => {
      if (!MessageUtility.verifyOrigin(e.origin)) {
        throw 'Denied message sent by ' + e.origin;
      }

      if (e.data.actionName === 'typo3:foreignRelation:inserted') {
        if (items.length > 0) {
          NProgress.inc(stepping);
          this.handleNext(items);
        } else {
          NProgress.done();
          ElementBrowser.focusOpenerAndClose();
        }
      }
    }).bindTo(window);
  }

  private handleNext(items: LinkElement[]): void {
    if (items.length > 0) {
      const item = items.pop();
      BrowseFiles.insertElement(item.fileName, Number(item.uid));
    }
  }
}

export = new BrowseFiles();
