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
import broadcastService = require('TYPO3/CMS/Backend/BroadcastService');
import Tooltip = require('TYPO3/CMS/Backend/Tooltip');
import RegularEvent = require('TYPO3/CMS/Core/Event/RegularEvent');
import {ModuleStateStorage} from 'TYPO3/CMS/Backend/Storage/ModuleStateStorage';
import {ActionConfiguration, ActionEventDetails} from 'TYPO3/CMS/Backend/MultiRecordSelectionAction';
import Modal = require('TYPO3/CMS/Backend/Modal');
import {SeverityEnum} from 'TYPO3/CMS/Backend/Enum/Severity';
import Severity = require('TYPO3/CMS/Backend/Severity');

type QueryParameters = {[key: string]: string};

interface EditFileMetadataConfiguration extends ActionConfiguration{
  table: string;
  returnUrl: string;
}

interface DeleteFileMetadataConfiguration {
  ok: string;
  title: string;
  content: string;
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
  public static submitClipboardFormWithCommand(cmd: string, target: HTMLButtonElement): void {
    const fileListForm: HTMLFormElement = target.closest(Selectors.fileListFormSelector);
    if (!fileListForm) {
      return;
    }
    const commandField: HTMLInputElement = fileListForm.querySelector(Selectors.commandSelector);
    if (!commandField) {
      return;
    }
    commandField.value = cmd;
    // In case we just copy elements to the clipboard, we try to fetch a possible pointer from the query
    // parameters, so after the form submit, we get to the same view as before. This is not done for delete
    // commands, since this may lead to empty sites, in case all elements from the current site are deleted.
    if (cmd === 'setCB') {
      const pointerField: HTMLInputElement = fileListForm.querySelector(Selectors.pointerFieldSelector);
      const pointerValue: string = Filelist.parseQueryParameters(document.location).pointer;
      if (pointerField && pointerValue) {
        pointerField.value = pointerValue;
      }
    }
    fileListForm.submit();
  }

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
    });

    // Respond to multi record selection action events
    new RegularEvent('multiRecordSelection:action:edit', this.editFileMetadata).bindTo(document);
    new RegularEvent('multiRecordSelection:action:delete', this.deleteMultiple).bindTo(document);
    new RegularEvent('multiRecordSelection:action:setCB', (event: CustomEvent): void => {
      Filelist.submitClipboardFormWithCommand('setCB', event.target as HTMLButtonElement)
    }).bindTo(document);

    // Respond to browser related clearable event
    const activeSearch: boolean = (document.querySelector([Selectors.fileListFormSelector, Selectors.searchFieldSelector].join(' ')) as HTMLInputElement)?.value !== '';
    new RegularEvent('search', (event: Event): void => {
      const searchField: HTMLInputElement = event.target as HTMLInputElement;
      if (searchField.value === '' && activeSearch) {
        (searchField.closest(Selectors.fileListFormSelector) as HTMLFormElement)?.submit();
      }
    }).delegateTo(document, Selectors.searchFieldSelector);
  }

  private deleteMultiple(e: CustomEvent): void {
    e.preventDefault();
    const eventDetails: ActionEventDetails = e.detail as ActionEventDetails;
    const configuration: DeleteFileMetadataConfiguration = eventDetails.configuration;
    Modal.advanced({
      title: configuration.title || 'Delete',
      content: configuration.content || 'Are you sure you want to delete those files and folders?',
      severity: SeverityEnum.warning,
      buttons: [
        {
          text: TYPO3.lang['button.close'] || 'Close',
          active: true,
          btnClass: 'btn-default',
          trigger: (): JQuery => Modal.currentModal.trigger('modal-dismiss')
        },
        {
          text: configuration.ok || TYPO3.lang['button.ok'] || 'OK',
          btnClass: 'btn-' + Severity.getCssClass(SeverityEnum.warning),
          trigger: (): void => {
            Filelist.submitClipboardFormWithCommand('delete', e.target as HTMLButtonElement)
            Modal.currentModal.trigger('modal-dismiss');
          }
        }
      ]
    });
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
}

export = new Filelist();
