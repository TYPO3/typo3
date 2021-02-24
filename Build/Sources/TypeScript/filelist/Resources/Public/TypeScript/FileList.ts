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

import DocumentService = require('TYPO3/CMS/Core/DocumentService');
import InfoWindow = require('TYPO3/CMS/Backend/InfoWindow');
import {BroadcastMessage} from 'TYPO3/CMS/Backend/BroadcastMessage';
import {ModalResponseEvent} from 'TYPO3/CMS/Backend/ModalInterface';
import broadcastService = require('TYPO3/CMS/Backend/BroadcastService');
import Tooltip = require('TYPO3/CMS/Backend/Tooltip');
import RegularEvent = require('TYPO3/CMS/Core/Event/RegularEvent');

/**
 * Module: TYPO3/CMS/Filelist/Filelist
 * @exports TYPO3/CMS/Filelist/Filelist
 */
class Filelist {
  protected static openInfoPopup(type: string, identifier: string): void {
    InfoWindow.showItem(type, identifier);
  }

  private static processTriggers(): void {
    const mainElement: HTMLElement = document.querySelector('.filelist-main');
    if (mainElement === null) {
      return
    }
    // emit event for currently shown folder
    Filelist.emitTreeUpdateRequest(
      mainElement.dataset.filelistCurrentFolderHash
    );
    // update recentIds so the current id will be used on accessing the FileList module again
    if (top.fsMod) {
      const id = encodeURIComponent(mainElement.dataset.filelistCurrentIdentifier);
      // top.fsMod.recentIds should always be set by BackendController::generateJavascript(),
      // however let's check the type to prevent unnecessary type errors.
      if (typeof top.fsMod.recentIds !== 'object') {
        top.fsMod.recentIds = {file: id};
      } else {
        top.fsMod.recentIds.file = id;
      }
    }
  }

  private static registerTreeUpdateEvents(): void {
    // listen potential change of folder
    new RegularEvent('click', function (this: HTMLElement): void {
      Filelist.emitTreeUpdateRequest(
        this.dataset.treeUpdateRequest
      );
    }).delegateTo(document.body, '[data-tree-update-request]');
  }

  private static emitTreeUpdateRequest(identifier: string): void {
    const message = new BroadcastMessage(
      'filelist',
      'treeUpdateRequested',
      {type: 'folder', identifier: identifier}
    );
    broadcastService.post(message);
  }

  private static submitClipboardFormWithCommand(cmd: string): void {
    const form = document.querySelector('form[name="dblistForm"]') as HTMLFormElement
    (form.querySelector('input[name="cmd"]') as HTMLInputElement).value = cmd;
    form.submit();
  }

  constructor() {
    Filelist.processTriggers();
    DocumentService.ready().then((): void => {
      Tooltip.initialize('.table-fit a[title]');
      Filelist.registerTreeUpdateEvents();
      // file index events
      new RegularEvent('click', (event: Event, target: HTMLElement): void => {
        event.preventDefault();
        Filelist.openInfoPopup(
          target.dataset.filelistShowItemType,
          target.dataset.filelistShowItemIdentifier
        );
      }).delegateTo(document, '[data-filelist-show-item-identifier][data-filelist-show-item-type]');

      // file search events
      new RegularEvent('click', (event: Event, target: HTMLElement): void => {
        event.preventDefault();
        Filelist.openInfoPopup('_FILE', target.dataset.identifier);
      }).delegateTo(document, 'a.btn.filelist-file-info');

      new RegularEvent('click', (event: Event, target: HTMLElement): void => {
        event.preventDefault();
        Filelist.openInfoPopup('_FILE', target.dataset.identifier);
      }).delegateTo(document, 'a.filelist-file-references');

      new RegularEvent('click', (event: Event, target: HTMLElement): void => {
        event.preventDefault();
        const url = target.getAttribute('href');
        let redirectUrl = (url)
          ? encodeURIComponent(url)
          : encodeURIComponent(top.list_frame.document.location.pathname + top.list_frame.document.location.search);
        top.list_frame.location.href = url + '&redirect=' + redirectUrl;
      }).delegateTo(document, 'a.btn.filelist-file-copy');

      // clipboard events
      const clipboardCmd = document.querySelector('[data-event-name="filelist:clipboard:cmd"]');
      if (clipboardCmd !== null) {
        new RegularEvent('filelist:clipboard:cmd', (event: ModalResponseEvent, target: HTMLElement): void => {
          if (event.detail.result) {
            Filelist.submitClipboardFormWithCommand(event.detail.payload);
          }
        }).bindTo(clipboardCmd);
      }

      new RegularEvent('click', (event: ModalResponseEvent, target: HTMLElement): void => {
        const cmd = target.dataset.filelistClipboardCmd;
        Filelist.submitClipboardFormWithCommand(cmd);
      }).delegateTo(document, '[data-filelist-clipboard-cmd]:not([data-filelist-clipboard-cmd=""])');
    });
  }
}

export = new Filelist();
