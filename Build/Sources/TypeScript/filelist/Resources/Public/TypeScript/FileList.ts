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
import Notification = require('TYPO3/CMS/Backend/Notification');
import InfoWindow = require('TYPO3/CMS/Backend/InfoWindow');
import {BroadcastMessage} from 'TYPO3/CMS/Backend/BroadcastMessage';
import {ModalResponseEvent} from 'TYPO3/CMS/Backend/ModalInterface';
import broadcastService = require('TYPO3/CMS/Backend/BroadcastService');
import Tooltip = require('TYPO3/CMS/Backend/Tooltip');
import RegularEvent = require('TYPO3/CMS/Core/Event/RegularEvent');
import {ModuleStateStorage} from 'TYPO3/CMS/Backend/Storage/ModuleStateStorage';
import {ActionConfiguration, ActionEventDetails} from 'TYPO3/CMS/Backend/MultiRecordSelectionAction';

type QueryParameters = {[key: string]: string};

interface EditFileMetadataConfiguration extends ActionConfiguration{
  table: string;
  returnUrl: string;
}

enum Selectors {
  fileListFormSelector = 'form[name="fileListForm"]',
  commandSelector = 'input[name="cmd"]',
  searchFieldSelector = 'input[name="searchTerm"]',
  pointerFieldSelector = 'input[name="pointer"]'
}

/**
 * Module: TYPO3/CMS/Filelist/Filelist
 * @exports TYPO3/CMS/Filelist/Filelist
 */
class Filelist {
  private fileListForm: HTMLFormElement = document.querySelector(Selectors.fileListFormSelector);
  private command: HTMLInputElement = this.fileListForm.querySelector(Selectors.commandSelector)
  private searchField: HTMLInputElement = this.fileListForm.querySelector(Selectors.searchFieldSelector);
  private pointerField: HTMLInputElement = this.fileListForm.querySelector(Selectors.pointerFieldSelector);
  private activeSearch: boolean = (this.searchField.value !== '');

  protected static openInfoPopup(type: string, identifier: string): void {
    InfoWindow.showItem(type, identifier);
  }

  private static processTriggers(): void {
    const mainElement: HTMLElement = document.querySelector('.filelist-main');
    if (mainElement === null) {
      return
    }
    // update ModuleStateStorage to the current folder identifier
    const id = encodeURIComponent(mainElement.dataset.filelistCurrentIdentifier);
    ModuleStateStorage.update('file', id, true, undefined);
    // emit event for currently shown folder so the folder tree gets updated
    Filelist.emitTreeUpdateRequest(
      mainElement.dataset.filelistCurrentIdentifier
    );
  }

  private static emitTreeUpdateRequest(identifier: string): void {
    const message = new BroadcastMessage(
      'filelist',
      'treeUpdateRequested',
      {type: 'folder', identifier: identifier}
    );
    broadcastService.post(message);
  }

  private static parseQueryParameters (location: Location): QueryParameters {
    let queryParameters: QueryParameters = {};
    if (location && Object.prototype.hasOwnProperty.call(location, 'search')) {
      let parameters = location.search.substr(1).split('&');
      for (let i = 0; i < parameters.length; i++) {
        const parameter = parameters[i].split('=');
        queryParameters[decodeURIComponent(parameter[0])] = decodeURIComponent(parameter[1]);
      }
    }
    return queryParameters;
  }

  private static getReturnUrl(returnUrl: string): string {
    if (returnUrl === '') {
      returnUrl = top.list_frame.document.location.pathname + top.list_frame.document.location.search;
    }
    return encodeURIComponent(returnUrl);
  }

  constructor() {
    Filelist.processTriggers();
    DocumentService.ready().then((): void => {
      Tooltip.initialize('.table-fit a[title]');
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
      }).delegateTo(document, 'a.filelist-file-info');

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
      }).delegateTo(document, 'a.filelist-file-copy');

      // clipboard events
      const clipboardCmd = document.querySelector('[data-event-name="filelist:clipboard:cmd"]');
      if (clipboardCmd !== null) {
        new RegularEvent('filelist:clipboard:cmd', (event: ModalResponseEvent, target: HTMLElement): void => {
          if (event.detail.result) {
            this.submitClipboardFormWithCommand(event.detail.payload);
          }
        }).bindTo(clipboardCmd);
      }

      new RegularEvent('click', (event: ModalResponseEvent, target: HTMLElement): void => {
        const cmd = target.dataset.filelistClipboardCmd;
        this.submitClipboardFormWithCommand(cmd);
      }).delegateTo(document, '[data-filelist-clipboard-cmd]:not([data-filelist-clipboard-cmd=""])');
    });

    // Respond to multi record selection action events
    new RegularEvent('multiRecordSelection:action:edit', this.editFileMetadata).bindTo(document);

    // Respond to browser related clearable event
    new RegularEvent('search', (): void => {
      if (this.searchField.value === '' && this.activeSearch) {
        this.fileListForm.submit();
      }
    }).bindTo(this.searchField);
  }

  private editFileMetadata(e: CustomEvent): void {
    e.preventDefault();
    const eventDetails: ActionEventDetails = e.detail;
    const configuration: EditFileMetadataConfiguration = eventDetails.configuration;
    if (!configuration || !configuration.idField || !configuration.table) {
      return;
    }
    const list: Array<string> = [];
    (eventDetails.checkboxes as NodeListOf<HTMLInputElement>).forEach((checkbox: HTMLInputElement) => {
      const checkboxContainer: HTMLElement = checkbox.closest('tr');
      if (checkboxContainer !== null && checkboxContainer.dataset[configuration.idField]) {
        list.push(checkboxContainer.dataset[configuration.idField]);
      }
    });

    if (list.length) {
      window.location.href = top.TYPO3.settings.FormEngine.moduleUrl
        + '&edit[' + configuration.table + '][' + list.join(',') + ']=edit'
        + '&returnUrl=' + Filelist.getReturnUrl(configuration.returnUrl || '');
    } else {
      Notification.warning('The selected elements can not be edited.');
    }
  }

  private submitClipboardFormWithCommand(cmd: string): void {
    this.command.value = cmd;
    // In case we just copy elements to the clipboard, we try to fetch a possible pointer from the query
    // parameters, so after the form submit, we get to the same view as before. This is not done for delete
    // commands, since this may lead to empty sites, in case all elements from the current site are deleted.
    if (cmd === 'setCB') {
      const pointerValue: string = Filelist.parseQueryParameters(document.location).pointer;
      if (pointerValue) {
        this.pointerField.value = pointerValue;
      }
    }
    this.fileListForm.submit();
  }
}

export = new Filelist();
