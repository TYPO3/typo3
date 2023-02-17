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
import { ActionEventDetails } from '@typo3/backend/multi-record-selection-action';
import { FileListActionResource, FileListActionEvent, FileListActionSelector, FileListActionUtility } from '@typo3/filelist/file-list-actions';
import InfoWindow from '@typo3/backend/info-window';

/**
 * Module: @typo3/backend/browse-folders
 * Folder selection
 * @exports @typo3/backend/browse-folders
 */
class BrowseFolders {
  public static insertElement(identifier: string, close?: boolean): boolean {
    return ElementBrowser.insertElement(
      '',
      identifier,
      identifier,
      identifier,
      close,
    );
  }

  constructor() {

    new RegularEvent(FileListActionEvent.primary, (event: CustomEvent): void => {
      event.preventDefault();
      document.dispatchEvent(new CustomEvent(FileListActionEvent.select, { detail: { resource: event.detail.resource } }));
    }).bindTo(document);

    new RegularEvent(FileListActionEvent.select, (event: CustomEvent): void => {
      event.preventDefault();
      const resource = event.detail.resource as FileListActionResource;
      if (resource.type === 'folder') {
        BrowseFolders.insertElement(resource.identifier, true);
      }
    }).bindTo(document);

    new RegularEvent(FileListActionEvent.show, (event: CustomEvent): void => {
      event.preventDefault();
      const resource = event.detail.resource as FileListActionResource;
      InfoWindow.showItem('_' + resource.type.toUpperCase(), resource.identifier);
    }).bindTo(document);

    // Handle import selection event, dispatched from MultiRecordSelection
    new RegularEvent('multiRecordSelection:action:import', this.importSelection).bindTo(document);

  }

  private importSelection = (event: CustomEvent): void => {
    event.preventDefault();
    const items: NodeListOf<HTMLInputElement> = (event.detail as ActionEventDetails).checkboxes;
    if (!items.length) {
      return;
    }
    const selectedItems: FileListActionResource[] = [];
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
    selectedItems.forEach(function (resource: FileListActionResource) {
      BrowseFolders.insertElement(resource.identifier);
    });
    ElementBrowser.focusOpenerAndClose();
  }
}

export default new BrowseFolders();
