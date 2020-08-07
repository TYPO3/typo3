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
import InfoWindow = require('TYPO3/CMS/Backend/InfoWindow');
import {BroadcastMessage} from 'TYPO3/CMS/Backend/BroadcastMessage';
import {ModalResponseEvent} from 'TYPO3/CMS/Backend/ModalInterface';
import broadcastService = require('TYPO3/CMS/Backend/BroadcastService');
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
    // update recentIds (for whatever reason)
    if (top.fsMod && top.fsMod.recentIds instanceof Object) {
      top.fsMod.recentIds.file = encodeURIComponent(mainElement.dataset.filelistCurrentIdentifier);
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
    const $form = $('form[name="dblistForm"]');
    $form.find('input[name="cmd"]').val(cmd);
    $form.trigger('submit');
  }

  constructor() {
    Filelist.processTriggers();
    $((): void => {
      Filelist.registerTreeUpdateEvents();
      // file index events
      $('[data-filelist-show-item-identifier][data-filelist-show-item-type]').on('click', (evt: JQueryEventObject): void => {
        const $element = $(evt.currentTarget);
        evt.preventDefault();
        Filelist.openInfoPopup(
          $element.data('filelistShowItemType'),
          $element.data('filelistShowItemIdentifier')
        );
      });
      // file search events
      $('a.btn.filelist-file-info').on('click', (event: JQueryEventObject): void => {
        event.preventDefault();
        Filelist.openInfoPopup('_FILE', $(event.currentTarget).attr('data-identifier'));
      });
      $('a.filelist-file-references').on('click', (event: JQueryEventObject): void => {
        event.preventDefault();
        Filelist.openInfoPopup('_FILE', $(event.currentTarget).attr('data-identifier'));
      });
      $('a.btn.filelist-file-copy').on('click', (event: JQueryEventObject): void => {
        event.preventDefault();
        const $element = $(event.currentTarget);
        const url = $element.attr('href');
        let redirectUrl = (url)
          ? encodeURIComponent(url)
          : encodeURIComponent(top.list_frame.document.location.pathname + top.list_frame.document.location.search);
        top.list_frame.location.href = url + '&redirect=' + redirectUrl;
      });
      // clipboard events
      $('[data-event-name="filelist:clipboard:cmd"]').on('filelist:clipboard:cmd', (evt: JQueryEventObject) => {
        const originalEvent = evt.originalEvent as ModalResponseEvent;
        if (originalEvent.detail.result) {
          Filelist.submitClipboardFormWithCommand(originalEvent.detail.payload);
        }
      });
      $('[data-filelist-clipboard-cmd]:not([data-filelist-clipboard-cmd=""])').on('click', (evt: JQueryEventObject): void => {
        const cmd = $(evt.currentTarget).data('filelistClipboardCmd');
        Filelist.submitClipboardFormWithCommand(cmd);
      });
    });
  }
}

export = new Filelist();
