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

import * as $ from 'jquery';
import ElementBrowser = require('./ElementBrowser');
// Yes we really need this import, because Tree... is used in inline markup...
import Tree = require('TYPO3/CMS/Backend/LegacyTree');

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

  /**
   * @param {Array} list
   */
  public insertElementMultiple(list: Array<any>): void {
    for (let i = 0, n = list.length; i < n; i++) {
      if (typeof BrowseFiles.elements[list[i]] !== 'undefined') {
        const element: LinkElement = BrowseFiles.elements[list[i]];
        ElementBrowser.insertMultiple('sys_file', element.uid);
      }
    }
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
          selectedItems.push(item.name);
        }
      });
      if (selectedItems.length > 0) {
        if (ElementBrowser.hasActionMultipleCode) {
          BrowseFiles.File.insertElementMultiple(selectedItems);
        } else {
          for (let i = 0; i < selectedItems.length; i++) {
            BrowseFiles.File.insertElement(selectedItems[i]);
          }
        }
      }
      ElementBrowser.focusOpenerAndClose();
    }
  }

  public getItems(): JQuery {
    return $('#typo3-filelist').find('.typo3-bulk-item');
  }
}

export = new BrowseFiles();
