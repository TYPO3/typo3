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
import {MessageUtility} from 'TYPO3/CMS/Backend/Utility/MessageUtility';
import ElementBrowser = require('./ElementBrowser');
import NProgress = require('nprogress');
// Yes we really need this import, because Tree... is used in inline markup...
import Tree = require('TYPO3/CMS/Backend/LegacyTree');
import RegularEvent = require('TYPO3/CMS/Core/Event/RegularEvent');
import Icons = TYPO3.Icons;

interface LinkElement {
  fileExt: string;
  fileIcon: string;
  fileName: string;
  filePath: any;
  table: string;
  type: string;
  uid: number;
}

interface LinkElementStorage {
  [s: string]: LinkElement;
}

class BrowseFiles {
  public static elements: LinkElementStorage;

  public static File: File;
  public static Selector: Selector;

  constructor() {
    // as long we use onclick attributes, we need the Tree component
    Tree.noop();
    BrowseFiles.File = new File();
    BrowseFiles.Selector = new Selector();

    $((): void => {
      BrowseFiles.elements = $('body').data('elements');

      $('[data-close]').on('click', (e: JQueryEventObject): void => {
        e.preventDefault();
        BrowseFiles.File.insertElement(
          'file_' + $(e.currentTarget).data('fileIndex'),
          parseInt($(e.currentTarget).data('close'), 10) === 1,
        );
      });

      new RegularEvent('change', (): void => {
        BrowseFiles.Selector.toggleImportButton();
      }).delegateTo(document, '.typo3-bulk-item');

      $('#t3js-importSelection').on('click', BrowseFiles.Selector.handle);
      $('#t3js-toggleSelection').on('click', BrowseFiles.Selector.toggle);
    });
  }
}

class File {
  /**
   * @param {String} index
   * @param  {Boolean} close
   */
  public insertElement(index: string, close?: boolean): boolean {
    let result = false;
    if (typeof BrowseFiles.elements[index] !== 'undefined') {
      const element: LinkElement = BrowseFiles.elements[index];
      result = ElementBrowser.insertElement(
        element.table,
        element.uid,
        element.type,
        element.fileName,
        element.filePath,
        element.fileExt,
        element.fileIcon,
        '',
        close,
      );
    }
    return result;
  }
}

class Selector {
  /**
   * Toggle selection button is pressed
   *
   * @param {JQueryEventObject} e
   */
  public toggle = (e: JQueryEventObject): void => {
    e.preventDefault();
    const items = this.getItems();
    if (items.length) {
      items.each((position: number, item: any): void => {
        item.checked = (item.checked ? null : 'checked');
      });
    }
    this.toggleImportButton();
  }

  /**
   * Import selection button is pressed
   *
   * @param {JQueryEventObject} e
   */
  public handle = (e: JQueryEventObject): void => {
    e.preventDefault();
    const items: JQuery = this.getItems();
    const selectedItems: Array<string> = [];
    if (items.length) {
      items.each((position: number, item: any): void => {
        if (item.checked && item.name) {
          selectedItems.unshift(item.name);
        }
      });
      Icons.getIcon('spinner-circle', Icons.sizes.small, null, null, Icons.markupIdentifiers.inline).then((icon: string): void => {
        e.currentTarget.classList.add('disabled');
        e.currentTarget.innerHTML = icon;
      });
      this.handleSelection(selectedItems);
    }
  }

  public getItems(): JQuery {
    return $('#typo3-filelist').find('.typo3-bulk-item');
  }

  public toggleImportButton(): void {
    const hasCheckedElements = document.querySelectorAll('#typo3-filelist .typo3-bulk-item:checked').length > 0;
    document.getElementById('t3js-importSelection').classList.toggle('disabled', !hasCheckedElements);
  }

  private handleSelection(items: string[]): void {
    NProgress.configure({parent: '#typo3-filelist', showSpinner: false});
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

  private handleNext(items: string[]): void {
    if (items.length > 0) {
      const item = items.pop();
      BrowseFiles.File.insertElement(item);
    }
  }
}

export = new BrowseFiles();
