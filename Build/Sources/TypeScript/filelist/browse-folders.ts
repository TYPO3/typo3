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

import ElementBrowser from '@typo3/backend/element-browser';
import RegularEvent from '@typo3/core/event/regular-event';
import type { ActionEventDetails } from '@typo3/backend/multi-record-selection-action';
import { FileListActionEvent, type FileListActionDetail, FileListActionSelector, FileListActionUtility } from '@typo3/filelist/file-list-actions';
import InfoWindow from '@typo3/backend/info-window';
import type { ResourceInterface } from '@typo3/backend/resource/resource';

/**
 * Module: @typo3/backend/browse-folders
 * Folder selection
 * @exports @typo3/backend/browse-folders
 */
class BrowseFolders {
  constructor() {

    new RegularEvent(FileListActionEvent.primary, (event: CustomEvent): void => {
      event.preventDefault();
      const detail: FileListActionDetail = event.detail;
      detail.originalAction = FileListActionEvent.primary;
      detail.action = FileListActionEvent.select;
      document.dispatchEvent(new CustomEvent(FileListActionEvent.select, { detail: detail }));
    }).bindTo(document);

    new RegularEvent(FileListActionEvent.select, (event: CustomEvent): void => {
      event.preventDefault();
      const detail: FileListActionDetail = event.detail;
      const resource = detail.resources[0];
      if (resource.type === 'folder') {
        BrowseFolders.insertElement(resource.identifier, detail.originalAction === FileListActionEvent.primary);
      }
    }).bindTo(document);

    new RegularEvent(FileListActionEvent.show, (event: CustomEvent): void => {
      event.preventDefault();
      const detail: FileListActionDetail = event.detail;
      const resource = detail.resources[0];
      InfoWindow.showItem('_' + resource.type.toUpperCase(), resource.identifier);
    }).bindTo(document);

    // Handle import selection event, dispatched from MultiRecordSelection
    new RegularEvent('multiRecordSelection:action:import', this.importSelection).bindTo(document);

  }

  public static insertElement(identifier: string, close?: boolean): boolean {
    return ElementBrowser.insertElement(
      '',
      identifier,
      identifier,
      identifier,
      close,
    );
  }

  private readonly importSelection = (event: CustomEvent): void => {
    event.preventDefault();
    const items: NodeListOf<HTMLInputElement> = (event.detail as ActionEventDetails).checkboxes;
    if (!items.length) {
      return;
    }
    const selectedItems: ResourceInterface[] = [];
    items.forEach((checkbox: HTMLInputElement) => {
      if (checkbox.checked) {
        const element = checkbox.closest(FileListActionSelector.elementSelector) as HTMLInputElement;
        const resource = FileListActionUtility.getResourceForElement(element);
        if (resource.type === 'folder' && resource.identifier) {
          selectedItems.unshift(resource);
        }
      }
    });
    if (!selectedItems.length) {
      return;
    }
    selectedItems.forEach(function (resource: ResourceInterface) {
      BrowseFolders.insertElement(resource.identifier);
    });
    ElementBrowser.focusOpenerAndClose();
  };
}

export default new BrowseFolders();
