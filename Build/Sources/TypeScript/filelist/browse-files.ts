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

import { MessageUtility } from '@typo3/backend/utility/message-utility';
import ElementBrowser from '@typo3/backend/element-browser';
import NProgress from 'nprogress';
import RegularEvent from '@typo3/core/event/regular-event';
import Icons from '@typo3/backend/icons';
import type { ActionEventDetails } from '@typo3/backend/multi-record-selection-action';
import { FileListActionEvent, type FileListActionDetail, FileListActionSelector, FileListActionUtility } from '@typo3/filelist/file-list-actions';
import InfoWindow from '@typo3/backend/info-window';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import type { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import type { ResourceInterface } from '@typo3/backend/resource/resource';

class BrowseFiles {
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
      if (resource.type === 'file') {
        BrowseFiles.insertElement(resource.name, resource.uid, detail.originalAction === FileListActionEvent.primary);
      }
      if (resource.type === 'folder') {
        this.loadContent(resource);
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

  public static insertElement(fileName: string, fileUid: number, close?: boolean): boolean {
    return ElementBrowser.insertElement(
      'sys_file',
      String(fileUid),
      fileName,
      String(fileUid),
      close,
    );
  }

  private static handleNext(items: ResourceInterface[]): void {
    if (items.length > 0) {
      const item = items.pop();
      BrowseFiles.insertElement(item.name, Number(item.uid));
    }
  }

  private loadContent(resource: ResourceInterface): void
  {
    if (resource.type !== 'folder') {
      return;
    }
    const contentsUrl = document.location.href + '&contentOnly=1&expandFolder=' + resource.identifier;
    (new AjaxRequest(contentsUrl)).get()
      .then((response: AjaxResponse) => response.resolve())
      .then((response) => {
        const contentContainer = document.querySelector('.element-browser-main-content .element-browser-body') as HTMLElement;
        contentContainer.innerHTML = response;
      });
  }

  private readonly importSelection = (event: CustomEvent): void => {
    event.preventDefault();
    const target: HTMLElement = event.target as HTMLElement;
    const items: NodeListOf<HTMLInputElement> = (event.detail as ActionEventDetails).checkboxes;
    if (!items.length) {
      return;
    }

    const selectedItems: ResourceInterface[] = [];
    items.forEach((checkbox: HTMLInputElement) => {
      if (checkbox.checked) {
        const element = checkbox.closest(FileListActionSelector.elementSelector) as HTMLInputElement;
        const resource = FileListActionUtility.getResourceForElement(element);
        if (resource.type === 'file' && resource.uid) {
          selectedItems.unshift(resource);
        }
      }
    });
    if (!selectedItems.length) {
      return;
    }

    Icons.getIcon('spinner-circle', Icons.sizes.small, null, null, Icons.markupIdentifiers.inline).then((icon: string): void => {
      target.classList.add('disabled');
      target.innerHTML = icon;
    });
    NProgress.configure({ parent: '.element-browser-main-content', showSpinner: false });
    NProgress.start();
    const stepping = 1 / selectedItems.length;
    BrowseFiles.handleNext(selectedItems);

    new RegularEvent('message', (event: MessageEvent): void => {
      if (!MessageUtility.verifyOrigin(event.origin)) {
        throw 'Denied message sent by ' + event.origin;
      }

      if (event.data.actionName === 'typo3:foreignRelation:inserted') {
        if (selectedItems.length > 0) {
          NProgress.inc(stepping);
          BrowseFiles.handleNext(selectedItems);
        } else {
          NProgress.done();
          ElementBrowser.focusOpenerAndClose();
        }
      }
    }).bindTo(window);
  };
}

export default new BrowseFiles();
